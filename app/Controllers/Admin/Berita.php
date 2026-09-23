<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\R2Storage;
use App\Models\BeritaFotoModel;
use App\Models\BeritaModel;

class Berita extends BaseController
{
    private const MIN_FOTO = 1;
    private const MAX_FOTO = 5;

    protected $beritaModel;
    protected $beritaFotoModel;
    protected $r2Storage;

    public function __construct()
    {
        $this->beritaModel     = new BeritaModel();
        $this->beritaFotoModel = new BeritaFotoModel();
        $this->r2Storage       = new R2Storage();
    }

    /**
     * Valid, unmoved UploadedFile instances from a multi-file input.
     *
     * @return \CodeIgniter\HTTP\Files\UploadedFile[]
     */
    private function validFiles(?array $files): array
    {
        return array_values(array_filter(
            $files ?? [],
            static fn ($f) => $f && $f->isValid() && !$f->hasMoved()
        ));
    }

    /**
     * Uploads to R2; falls back to local disk (today's behavior) if the R2
     * call fails, so a Cloudflare outage doesn't block publishing berita.
     * Fallback rows are bare filenames just like pre-R2 legacy rows, so
     * `spark berita:migrate-to-r2` picks them up on its next run.
     */
    private function storeFoto($foto): string
    {
        try {
            return $this->r2Storage->upload($foto, 'berita');
        } catch (\Throwable $e) {
            log_message('error', 'R2 upload failed for berita foto, falling back to local disk: ' . $e->getMessage());

            $newName = $foto->getRandomName();
            $foto->move(FCPATH . 'public/berita', $newName);

            return $newName;
        }
    }

    /**
     * Deletes a berita's previous foto, routing to R2 or local disk based
     * on the stored value's own format (full URL vs bare filename).
     */
    private function deleteFoto(?string $oldFoto): void
    {
        if (empty($oldFoto)) {
            return;
        }

        if (str_starts_with($oldFoto, 'http://') || str_starts_with($oldFoto, 'https://')) {
            $this->r2Storage->delete($oldFoto);
        } elseif (file_exists(FCPATH . 'public/berita/' . $oldFoto)) {
            unlink(FCPATH . 'public/berita/' . $oldFoto);
        }
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Berita';
        $data['beritas'] = $this->beritaModel->all();
        return $this->loadViews('admin/berita', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Berita';
        return $this->loadViews('admin/tambah_berita', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $fotos = $this->validFiles($this->request->getFileMultiple('foto'));

        if (empty($fotos)) {
            setFlashData('error', 'Error karna tidak ada file');
            return redirect()->to(back());
        }

        if (count($fotos) > self::MAX_FOTO) {
            setFlashData('error', 'Maksimal ' . self::MAX_FOTO . ' gambar');
            return redirect()->to(back());
        }

        $data = [
            'judul'      => $this->request->getPost('judul'),
            'slug'       => url_title($this->request->getPost('judul'), '-', true),
            'deskripsi'  => $this->request->getPost('deskripsi'),
            'lampiran'   => $this->request->getPost('lampiran'),
            'kategori'   => $this->request->getPost('kategori'),
            'created_by' => auth()->user() ? auth()->user()->id : 0,
            'id_rt'      => current_rt_id(),
        ];

        // Cover defaults to the first selected file; picking a specific
        // cover among several is only offered on edit (once images have
        // URLs to preview), not at upload time.
        $urls = array_map(fn ($f) => $this->storeFoto($f), $fotos);

        $data['foto']      = $urls[0];
        $data['is_status'] = 0;

        $idBerita = $this->beritaModel->insert($data);

        foreach ($urls as $i => $url) {
            $this->beritaFotoModel->insert([
                'id_berita' => $idBerita,
                'foto'      => $url,
                'urutan'    => $i + 1,
                'is_cover'  => $i === 0 ? 1 : 0,
                'id_rt'     => current_rt_id(),
            ]);
        }

        setFlashData('success', 'Success uploading File');
        return redirect()->to('admin/berita');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah berita';

        $berita = $this->beritaModel->detail($id);
        if ($berita === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Pre-gallery berita (single `foto`, no berita_foto rows) get their
        // existing photo lazily adopted into the gallery the first time
        // they're opened for editing, so the gallery UI always has at
        // least one manageable entry.
        if ($this->beritaFotoModel->countForBerita($id) === 0 && !empty($berita->foto)) {
            $this->beritaFotoModel->insert([
                'id_berita' => $id,
                'foto'      => $berita->foto,
                'urutan'    => 1,
                'is_cover'  => 1,
                'id_rt'     => current_rt_id(),
            ]);
        }

        $data['berita']  = $berita;
        $data['fotos']   = $this->beritaFotoModel->forBerita($id);
        $data['maxFoto'] = self::MAX_FOTO;

        return $this->loadViews('admin/ubah_berita', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // BeritaModel::update() is the base Model method - it only
        // filters by primary key, not id_rt. detail() is tenant-scoped,
        // so a mismatched or nonexistent id resolves to null here.
        $existing = $this->beritaModel->detail($id);
        if ($existing === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $photos = $this->beritaFotoModel->forBerita($id);

        $deleteIds = array_map('intval', (array) $this->request->getPost('delete_foto'));
        $kept      = array_values(array_filter($photos, static fn ($p) => !in_array((int) $p->id_berita_foto, $deleteIds, true)));
        $removed   = array_values(array_filter($photos, static fn ($p) => in_array((int) $p->id_berita_foto, $deleteIds, true)));

        $newFiles = $this->validFiles($this->request->getFileMultiple('foto'));

        $finalCount = count($kept) + count($newFiles);

        if ($finalCount < self::MIN_FOTO || $finalCount > self::MAX_FOTO) {
            setFlashData('error', 'Berita harus punya ' . self::MIN_FOTO . '-' . self::MAX_FOTO . ' gambar');
            return redirect()->to(back());
        }

        // Remove deleted photos from storage, then their rows.
        foreach ($removed as $photo) {
            $this->deleteFoto($photo->foto);
            $this->beritaFotoModel->deleteForBerita($id, (int) $photo->id_berita_foto);
        }

        // Upload and insert new photos, continuing the urutan sequence
        // after whatever was kept.
        $nextUrutan = 1;
        foreach ($kept as $photo) {
            $nextUrutan = max($nextUrutan, (int) $photo->urutan + 1);
        }

        $newIds  = [];
        $urlById = [];
        foreach ($kept as $photo) {
            $urlById[(int) $photo->id_berita_foto] = $photo->foto;
        }

        foreach ($newFiles as $file) {
            $url   = $this->storeFoto($file);
            $newId = $this->beritaFotoModel->insert([
                'id_berita' => $id,
                'foto'      => $url,
                'urutan'    => $nextUrutan++,
                'is_cover'  => 0,
                'id_rt'     => current_rt_id(),
            ]);
            $newIds[]           = $newId;
            $urlById[(int) $newId] = $url;
        }

        // Cover choice: "existing:<id_berita_foto>" or "new:<index>" (into
        // $newFiles/$newIds, in upload order). Falls back to the first
        // kept photo, then the first new one, if the choice didn't survive
        // deletion or wasn't provided.
        $coverChoice = (string) $this->request->getPost('cover');
        $coverId     = null;

        if (str_starts_with($coverChoice, 'existing:')) {
            $candidate = (int) substr($coverChoice, 9);
            if (array_key_exists($candidate, $urlById) && in_array($candidate, array_map(static fn ($p) => (int) $p->id_berita_foto, $kept), true)) {
                $coverId = $candidate;
            }
        } elseif (str_starts_with($coverChoice, 'new:')) {
            $index   = (int) substr($coverChoice, 4);
            $coverId = $newIds[$index] ?? null;
        }

        if ($coverId === null) {
            $coverId = $kept[0]->id_berita_foto ?? ($newIds[0] ?? null);
        }

        $data = [
            'judul'     => $this->request->getPost('judul'),
            'slug'      => url_title($this->request->getPost('judul'), '-', true),
            'deskripsi' => $this->request->getPost('deskripsi'),
            'lampiran'  => $this->request->getPost('lampiran'),
            'is_status' => $this->request->getPost('is_status'),
            'kategori'  => $this->request->getPost('kategori'),
        ];

        if ($coverId !== null) {
            $this->beritaFotoModel->setCover($id, (int) $coverId);
            $data['foto'] = $urlById[(int) $coverId] ?? $existing->foto;
        }

        $this->beritaModel->update($id, $data);
        setFlashData('success', 'Success update data berita');
        return redirect()->to('admin/berita');
    }
}

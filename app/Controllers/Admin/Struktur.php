<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StrukturOrganisasiModel;

class Struktur extends BaseController
{
    protected $strukturModel;

    public function __construct()
    {
        $this->strukturModel = new StrukturOrganisasiModel();
    }

    private function rules(bool $isCreate = false): array
    {
        $rules = [
            'id_warga'  => 'required|is_natural_no_zero',
            'jabatan'   => 'required|max_length[100]',
            'id_atasan' => 'permit_empty|is_natural_no_zero',
            'urutan'    => 'permit_empty|is_natural|less_than[10000]',
        ];

        if ($isCreate) {
            $rules['level'] = 'required|in_list[rt,rw]';
        }

        return $rules;
    }

    private function validationMessages(): array
    {
        return [
            'id_warga' => [
                'required'           => 'Warga wajib dipilih.',
                'is_natural_no_zero' => 'Warga tidak valid.',
            ],
            'jabatan' => [
                'required'   => 'Nama jabatan wajib diisi.',
                'max_length' => 'Nama jabatan maksimal 100 karakter.',
            ],
            'id_atasan' => [
                'is_natural_no_zero' => 'Atasan tidak valid.',
            ],
            'urutan' => [
                'is_natural'    => 'Urutan harus berupa angka 0 atau lebih.',
                'less_than'     => 'Urutan maksimal 9999.',
            ],
            'level' => [
                'required' => 'Level struktur tidak valid.',
                'in_list'  => 'Level struktur harus RT atau RW.',
            ],
        ];
    }

    private function formData(string $level, ?int $selfId = null): array
    {
        return [
            'level'   => $level,
            'warga'   => $this->strukturModel->wargaOptions($level),
            'atasan'  => $this->strukturModel->atasanOptions($level, $selfId),
        ];
    }

    private function payload(): array
    {
        $idAtasan = $this->request->getPost('id_atasan');

        return [
            'id_warga'  => (int) $this->request->getPost('id_warga'),
            'jabatan'   => trim((string) $this->request->getPost('jabatan')),
            'id_atasan' => ($idAtasan === null || $idAtasan === '') ? null : (int) $idAtasan,
            'urutan'    => (int) $this->request->getPost('urutan'),
        ];
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Struktur Organisasi';

        $data['rt']       = $this->strukturModel->daftar('rt');
        $data['rw']       = $this->strukturModel->daftar('rw');
        $data['hasRw']    = $this->strukturModel->rwId() !== null;
        $data['rtNama']   = current_rt()->nama ?? 'RT';
        $data['tree']     = $this->strukturModel->tree();

        return $this->loadViews('admin/struktur', $this->global, $data);
    }

    public function add(string $level)
    {
        if (! in_array($level, StrukturOrganisasiModel::LEVELS, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($level === 'rw' && $this->strukturModel->rwId() === null) {
            setFlashData('error', 'RT ini belum terhubung ke RW.');
            return redirect()->to('admin/struktur');
        }

        $this->global['pageTitle'] = 'Tambah Pengurus ' . strtoupper($level);

        return $this->loadViews('admin/tambah_struktur', $this->global, $this->formData($level));
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules($this->rules(true), $this->validationMessages());

        if (! $validation->withRequest($this->request)->run()) {
            setFlashData('error', implode('<br>', $validation->getErrors()));
            return redirect()->back()->withInput();
        }

        $level = (string) $this->request->getPost('level');
        $data  = $this->payload();

        if ($level === 'rw' && $this->strukturModel->rwId() === null) {
            setFlashData('error', 'RT ini belum terhubung ke RW.');
            return redirect()->to('admin/struktur');
        }

        if (! $this->strukturModel->wargaValid($level, $data['id_warga'])) {
            setFlashData('error', 'Warga tidak valid untuk struktur ' . strtoupper($level) . '!');
            return redirect()->back()->withInput();
        }

        if (! $this->strukturModel->atasanValid($level, $data['id_atasan'])) {
            setFlashData('error', 'Atasan tidak valid!');
            return redirect()->back()->withInput();
        }

        $data['level']      = $level;
        $data['id_rt']      = $level === 'rt' ? current_rt_id() : null;
        $data['id_rw']      = $level === 'rw' ? $this->strukturModel->rwId() : null;
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->strukturModel->insert($data);
        setFlashData('success', 'Pengurus berhasil ditambahkan!');
        return redirect()->to('admin/struktur');
    }

    public function edit($id)
    {
        $item = $this->strukturModel->detail($id);
        if (empty($item)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Ubah Pengurus ' . strtoupper($item->level);
        $data                      = $this->formData($item->level, (int) $item->id_struktur);
        $data['item']              = $item;

        return $this->loadViews('admin/ubah_struktur', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // StrukturOrganisasiModel::update() only filters by primary key;
        // detail() is tenant-scoped, so a foreign id resolves to empty.
        $item = $this->strukturModel->detail($id);
        if (empty($item)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules($this->rules(false), $this->validationMessages());

        if (! $validation->withRequest($this->request)->run()) {
            setFlashData('error', implode('<br>', $validation->getErrors()));
            return redirect()->back()->withInput();
        }

        $data = $this->payload();

        // An unchanged warga stays valid even if they have since left the
        // active list (e.g. moved out), so only re-check on change.
        if ($data['id_warga'] !== (int) $item->id_warga
            && ! $this->strukturModel->wargaValid($item->level, $data['id_warga'])) {
            setFlashData('error', 'Warga tidak valid untuk struktur ' . strtoupper($item->level) . '!');
            return redirect()->back()->withInput();
        }

        if (! $this->strukturModel->atasanValid($item->level, $data['id_atasan'], (int) $item->id_struktur)) {
            setFlashData('error', 'Atasan tidak valid!');
            return redirect()->back()->withInput();
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->strukturModel->update($id, $data);
        setFlashData('success', 'Pengurus berhasil diubah!');
        return redirect()->to('admin/struktur');
    }

    public function delete($id)
    {
        $item = $this->strukturModel->detail($id);
        if (! empty($item)) {
            $this->strukturModel->hapus($item);
            setFlashData('success', 'Pengurus berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }

        return redirect()->to('admin/struktur');
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\RwModel;

class Tenants extends BaseController
{
    private const RESERVED_SUBDOMAINS = ['www', 'admin', 'api', 'mail', 'ftp', 'localhost', 'static', 'assets', 'cdn'];
    private const SUBDOMAIN_REGEX = '/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/';

    protected $rtModel;
    protected $rwModel;

    public function __construct()
    {
        $this->rtModel = new RtModel();
        $this->rwModel = new RwModel();
    }

    /**
     * Validates and normalizes a submitted subdomain. Returns the
     * normalized value on success, or null (with a flash error already
     * set) on failure. Uniqueness is checked across BOTH rt and rw
     * since subdomains share one DNS namespace even though each column
     * only has a per-table unique index.
     */
    private function validateSubdomain(string $subdomain, string $excludeTable = '', $excludeId = null): ?string
    {
        $subdomain = strtolower(trim($subdomain));

        if ($subdomain === '' || !preg_match(self::SUBDOMAIN_REGEX, $subdomain)) {
            setFlashData('error', 'Subdomain wajib diisi dan hanya boleh berisi huruf kecil, angka, dan tanda hubung!');
            return null;
        }

        if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
            setFlashData('error', 'Subdomain tersebut dicadangkan sistem, gunakan yang lain!');
            return null;
        }

        $rtQuery = $this->rtModel->where('subdomain', $subdomain);
        if ($excludeTable === 'rt' && $excludeId !== null) {
            $rtQuery->where('id_rt !=', $excludeId);
        }

        $rwQuery = $this->rwModel->where('subdomain', $subdomain);
        if ($excludeTable === 'rw' && $excludeId !== null) {
            $rwQuery->where('id_rw !=', $excludeId);
        }

        if ($rtQuery->countAllResults() > 0 || $rwQuery->countAllResults() > 0) {
            setFlashData('error', 'Subdomain sudah digunakan!');
            return null;
        }

        return $subdomain;
    }

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Guard: Only superadmin allowed
        $user = auth()->user();
        if ($user === null || !$user->inGroup('superadmin')) {
            throw new \CodeIgniter\HTTP\Exceptions\RedirectException('admin/dashboard');
        }
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Tenant';

        $data['rts'] = $this->rtModel->orderBy('nama')->findAll();
        $data['rws'] = $this->rwModel->orderBy('nama')->findAll();

        return $this->loadViews('admin/tenants/index', $this->global, $data);
    }

    public function addRt()
    {
        $this->global['pageTitle'] = 'Tambah RT';
        $data['rws'] = $this->rwModel->aktif();
        return $this->loadViews('admin/tenants/tambah_rt', $this->global, $data);
    }

    public function storeRt()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama'      => 'required',
            'id_rw'     => 'required|numeric',
            'subdomain' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            setFlashData('error', 'Nama RT, RW, dan subdomain wajib diisi dengan benar!');
            return redirect()->to(back());
        }

        $nama = $this->request->getPost('nama');
        $slug = url_title($nama, '-', true);

        // Check if slug exists
        if ($this->rtModel->where('slug', $slug)->countAllResults() > 0) {
            setFlashData('error', 'Nama RT sudah digunakan!');
            return redirect()->to(back());
        }

        $subdomain = $this->validateSubdomain($this->request->getPost('subdomain'));
        if ($subdomain === null) {
            return redirect()->to(back());
        }

        $data = [
            'nama'      => $nama,
            'slug'      => $slug,
            'subdomain' => $subdomain,
            'id_rw'     => (int) $this->request->getPost('id_rw'),
            'is_aktif'  => (int) ($this->request->getPost('is_aktif') ?? 1),
        ];

        $this->rtModel->insert($data);
        setFlashData('success', 'RT berhasil ditambahkan!');
        return redirect()->to('admin/tenants');
    }

    public function editRt($id)
    {
        $rt = $this->rtModel->find($id);
        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Ubah RT';
        $data['rt']  = $rt;
        $data['rws'] = $this->rwModel->aktif();

        return $this->loadViews('admin/tenants/ubah_rt', $this->global, $data);
    }

    public function updateRt($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rt = $this->rtModel->find($id);
        if ($rt === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama'      => 'required',
            'id_rw'     => 'required|numeric',
            'subdomain' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            setFlashData('error', 'Semua data wajib diisi dengan benar!');
            return redirect()->to(back());
        }

        $nama = $this->request->getPost('nama');
        $slug = url_title($nama, '-', true);

        // Check if slug exists in other records
        if ($this->rtModel->where('slug', $slug)->where('id_rt !=', $id)->countAllResults() > 0) {
            setFlashData('error', 'Nama RT sudah digunakan!');
            return redirect()->to(back());
        }

        $subdomain = $this->validateSubdomain($this->request->getPost('subdomain'), 'rt', $id);
        if ($subdomain === null) {
            return redirect()->to(back());
        }

        $data = [
            'nama'      => $nama,
            'slug'      => $slug,
            'subdomain' => $subdomain,
            'id_rw'     => (int) $this->request->getPost('id_rw'),
            'is_aktif'  => (int) ($this->request->getPost('is_aktif') ?? 1),
        ];

        $this->rtModel->update($id, $data);
        setFlashData('success', 'RT berhasil diubah!');
        return redirect()->to('admin/tenants');
    }

    public function addRw()
    {
        $this->global['pageTitle'] = 'Tambah RW';
        return $this->loadViews('admin/tenants/tambah_rw', $this->global);
    }

    public function storeRw()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama'      => 'required',
            'subdomain' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            setFlashData('error', 'Nama RW dan subdomain wajib diisi dengan benar!');
            return redirect()->to(back());
        }

        $nama = $this->request->getPost('nama');
        $slug = url_title($nama, '-', true);

        // Check if slug exists
        if ($this->rwModel->where('slug', $slug)->countAllResults() > 0) {
            setFlashData('error', 'Nama RW sudah digunakan!');
            return redirect()->to(back());
        }

        $subdomain = $this->validateSubdomain($this->request->getPost('subdomain'));
        if ($subdomain === null) {
            return redirect()->to(back());
        }

        $data = [
            'nama'      => $nama,
            'slug'      => $slug,
            'subdomain' => $subdomain,
            'is_aktif'  => (int) ($this->request->getPost('is_aktif') ?? 1),
        ];

        $this->rwModel->insert($data);
        setFlashData('success', 'RW berhasil ditambahkan!');
        return redirect()->to('admin/tenants');
    }

    public function editRw($id)
    {
        $rw = $this->rwModel->find($id);
        if ($rw === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->global['pageTitle'] = 'Ubah RW';
        $data['rw'] = $rw;

        return $this->loadViews('admin/tenants/ubah_rw', $this->global, $data);
    }

    public function updateRw($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rw = $this->rwModel->find($id);
        if ($rw === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama'      => 'required',
            'subdomain' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            setFlashData('error', 'Semua data wajib diisi dengan benar!');
            return redirect()->to(back());
        }

        $nama = $this->request->getPost('nama');
        $slug = url_title($nama, '-', true);

        // Check if slug exists in other records
        if ($this->rwModel->where('slug', $slug)->where('id_rw !=', $id)->countAllResults() > 0) {
            setFlashData('error', 'Nama RW sudah digunakan!');
            return redirect()->to(back());
        }

        $subdomain = $this->validateSubdomain($this->request->getPost('subdomain'), 'rw', $id);
        if ($subdomain === null) {
            return redirect()->to(back());
        }

        $data = [
            'nama'      => $nama,
            'slug'      => $slug,
            'subdomain' => $subdomain,
            'is_aktif'  => (int) ($this->request->getPost('is_aktif') ?? 1),
        ];

        $this->rwModel->update($id, $data);
        setFlashData('success', 'RW berhasil diubah!');
        return redirect()->to('admin/tenants');
    }
}

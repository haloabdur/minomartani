<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InventarisModel;

class Inventaris extends BaseController
{
    protected $inventarisModel;

    public function __construct()
    {
        $this->inventarisModel = new InventarisModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Inventaris RT';
        $data['inventaris'] = $this->inventarisModel->all();
        return $this->loadViews('admin/inventaris', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Inventaris';
        return $this->loadViews('admin/tambah_inventaris', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_barang' => 'required',
            'stok'        => 'required|numeric'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->add();
        }

        $data = [
            'nama_barang' => $this->request->getPost('nama_barang'),
            'stok'        => $this->request->getPost('stok'),
            'created_at'  => date('Y-m-d H:i:s'),
            'id_rt'       => current_rt_id(),
        ];

        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = 'item-' . date('ymd') . '-' . substr(md5(rand()), 0, 10) . '.' . $foto->getExtension();

            $path = FCPATH . 'public/inventaris';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $foto->move($path, $newName);
            $data['foto'] = 'public/inventaris/' . $newName;
        }

        $this->inventarisModel->insert($data);
        setFlashData('success', 'Data inventaris berhasil ditambahkan!');
        return redirect()->to('admin/inventaris');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Inventaris';
        $data['item'] = $this->inventarisModel->detail($id);

        if (empty($data['item'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->loadViews('admin/ubah_inventaris', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'nama_barang' => 'required',
            'stok'        => 'required|numeric'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->edit($id);
        }

        $data = [
            'nama_barang' => $this->request->getPost('nama_barang'),
            'stok'        => $this->request->getPost('stok'),
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        $foto = $this->request->getFile('foto');

        if ($foto && $foto->isValid() && !$foto->hasMoved()) {
            $newName = 'item-' . date('ymd') . '-' . substr(md5(rand()), 0, 10) . '.' . $foto->getExtension();

            $path = FCPATH . 'public/inventaris';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $foto->move($path, $newName);
            $data['foto'] = 'public/inventaris/' . $newName;

            // Delete old photo
            $oldItem = $this->inventarisModel->detail($id);
            if (!empty($oldItem->foto) && file_exists(FCPATH . $oldItem->foto)) {
                unlink(FCPATH . $oldItem->foto);
            }
        }

        $this->inventarisModel->update($id, $data);
        setFlashData('success', 'Data inventaris berhasil diubah!');
        return redirect()->to('admin/inventaris');
    }

    public function delete($id)
    {
        $item = $this->inventarisModel->detail($id);
        if (!empty($item)) {
            if (!empty($item->foto) && file_exists(FCPATH . $item->foto)) {
                unlink(FCPATH . $item->foto);
            }
            $this->inventarisModel->hapus($id);
            setFlashData('success', 'Data inventaris berhasil dihapus!');
        } else {
            setFlashData('error', 'Data tidak ditemukan!');
        }
        return redirect()->to('admin/inventaris');
    }
}

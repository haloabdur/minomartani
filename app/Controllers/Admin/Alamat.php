<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AlamatModel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Alamat extends BaseController
{
    protected $alamatModel;

    public function __construct()
    {
        $this->alamatModel = new AlamatModel();
    }

    public function index()
    {
        $this->global['pageTitle'] = 'Kelola Alamat';
        $data['alamats'] = $this->alamatModel->all();
        return $this->loadViews('admin/alamat', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah Alamat';
        return $this->loadViews('admin/tambah_alamat', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $cek = $this->alamatModel->cek_alamat($this->request->getPost('jalan'), $this->request->getPost('nomor'));

        if ($cek) {
            setFlashData('error', 'Alamat telah digunakan!');
            return redirect()->to(back());
        }

        $kode = str_replace(" ", "", $this->request->getPost('jalan') . $this->request->getPost('nomor'));
        $slug = current_rt()->slug ?? 'rt29';

        // Generate QR Code image
        $this->createQrCodeImage($kode, base_url($slug . '/detail/' . $kode));

        $data = [
            'jalan'      => $this->request->getPost('jalan'),
            'nomor'      => $this->request->getPost('nomor'),
            'kode_rumah' => $kode,
            'qrcode'     => $kode,
            'id_rt'      => current_rt_id(),
        ];

        $this->alamatModel->insert($data);
        setFlashData('success', 'Berhasil menambahkan alamat');
        return redirect()->to('admin/alamat');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah Alamat';
        $data['alamat'] = $this->alamatModel->detail($id);
        return $this->loadViews('admin/ubah_alamat', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'alamat' => $this->request->getPost('alamat')
        ];

        $this->alamatModel->update($id, $data);
        setFlashData('success', 'Data alamat berhasil diubah!');
        return redirect()->to('admin/alamat');
    }

    public function generate_qrcode($id)
    {
        $alamat = $this->alamatModel->detail($id);
        $kode   = "mino_" . $alamat->id_alamat;
        $slug   = current_rt()->slug ?? 'rt29';

        // Generate QR Code image
        $this->createQrCodeImage($kode, base_url($slug . '/detail/' . $kode));

        $data = [
            'qrcode' => $kode
        ];

        $this->alamatModel->update($id, $data);
        setFlashData('success', 'QR Code berhasil di-generate!');
        return redirect()->to('admin/alamat');
    }

    /**
     * Generate QR Code PNG file and save to public/public/qrcode/
     */
    private function createQrCodeImage(string $kode, string $url): void
    {
        $qrDir = FCPATH . 'public/qrcode';

        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'scale'        => 10,
            'eccLevel'     => QRCode::ECC_H,
            'imageBase64'  => false,
        ]);

        $qrcode  = new QRCode($options);
        $qrImage = $qrcode->render($url);

        file_put_contents($qrDir . '/' . $kode . '.png', $qrImage);
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Users extends BaseController
{
    public function index()
    {
        $this->global['pageTitle'] = 'Kelola User';

        // Using Shield's user provider
        $users = auth()->getProvider();
        $data['users'] = $users->findAll();

        return $this->loadViews('admin/users', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah User';
        return $this->loadViews('admin/tambah_user', $this->global);
    }

    public function store()
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $email    = $this->request->getPost('email');
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $cpassword = $this->request->getPost('cpassword');

        if ($password != $cpassword) {
            setFlashData('error', 'Password masih belum sama, silahkan ulangi kembali!');
            return redirect()->to(back());
        }

        // Using Shield's user provider
        $users = auth()->getProvider();

        // Check if email already exists
        $existingUser = $users->findByCredentials(['email' => $email]);
        if ($existingUser) {
            setFlashData('error', 'Data user gagal ditambahkan! Sudah ada user dengan email ' . $email);
            return redirect()->to(back());
        }

        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        $users->save($user);

        // Get the inserted user to add to group
        $user = $users->findById($users->getInsertID());
        $user->addGroup('user');

        setFlashData('success', 'Data user berhasil ditambahkan!');
        return redirect()->to('admin/users');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah user';

        $users = auth()->getProvider();
        $data['user'] = $users->findById($id);

        return $this->loadViews('admin/ubah_user', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $users = auth()->getProvider();
        $user = $users->findById($id);

        $user->username = $this->request->getPost('username');

        $password = $this->request->getPost('password');

        if (!empty($password)) {
            $cpassword = $this->request->getPost('cpassword');
            if ($password != $cpassword) {
                setFlashData('error', 'Password masih belum sama, silahkan ulangi kembali!');
                return redirect()->to(back());
            }
            $user->password = $password;
        }

        $users->save($user);

        setFlashData('success', 'Data user berhasil diubah!');
        return redirect()->to('admin/users');
    }

    public function delete($id)
    {
        $users = auth()->getProvider();
        $user = $users->findById($id);

        if ($user) {
            // Soft-disable by banning
            $user->ban('Disabled by admin');

            setFlashData('success', 'Data user berhasil di non aktifkan!');
        }

        return redirect()->to('admin/users');
    }
}

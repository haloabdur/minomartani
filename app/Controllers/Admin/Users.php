<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\RwModel;
use CodeIgniter\Shield\Entities\User;

class Users extends BaseController
{
    public function index()
    {
        $this->global['pageTitle'] = 'Kelola User';

        // Using Shield's user provider
        $users = auth()->getProvider();

        // Display users except superadmin
        $query = $users->where("id NOT IN (SELECT user_id FROM auth_groups_users WHERE `group` = 'superadmin')");

        // Defense in depth: non-superadmin viewers only ever see their
        // own RT's users, even if this route's filter is ever relaxed
        // from group:superadmin to group:admin.
        if (! auth()->user()->inGroup('superadmin')) {
            $query = $query->where('id_rt', current_rt_id());
        }

        $data['users'] = $query->findAll();

        return $this->loadViews('admin/users', $this->global, $data);
    }

    public function add()
    {
        $this->global['pageTitle'] = 'Tambah User';
        $data['rts'] = model(RtModel::class)->aktif();
        $data['rws'] = model(RwModel::class)->aktif();
        return $this->loadViews('admin/tambah_user', $this->global, $data);
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

        // Check if username already exists
        if ($users->findByCredentials(['username' => $username])) {
            setFlashData('error', 'Data user gagal ditambahkan! Username ' . $username . ' sudah digunakan.');
            return redirect()->to(back());
        }

        // Determine tenant binding and Shield group
        $isSuperadmin = auth()->user()->inGroup('superadmin');

        if ($isSuperadmin) {
            $idRt = $this->request->getPost('id_rt');
            $idRw = $this->request->getPost('id_rw');

            $targetGroup = 'superadmin';
            $rtVal = null;
            $rwVal = null;

            if (!empty($idRt)) {
                $targetGroup = 'admin';
                $rtVal = (int) $idRt;
            } elseif (!empty($idRw)) {
                $targetGroup = 'rw';
                $rwVal = (int) $idRw;
            }
        } else {
            // RT admins can only create fellow admins for their own RT.
            $targetGroup = 'admin';
            $rtVal = current_rt_id();
            $rwVal = null;
        }

        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
        ]);

        $users->save($user);
        $userId = $users->getInsertID();

        // Update tenant columns directly
        db_connect()->table('users')
            ->where('id', $userId)
            ->update([
                'id_rt' => $rtVal,
                'id_rw' => $rwVal,
            ]);

        // Get the inserted user to add to group
        $user = $users->findById($userId);
        $user->syncGroups($targetGroup);

        setFlashData('success', 'Data user berhasil ditambahkan!');
        return redirect()->to('admin/users');
    }

    public function edit($id)
    {
        $this->global['pageTitle'] = 'Ubah user';

        $users = auth()->getProvider();
        $data['user'] = $users->findById($id);
        $data['rts'] = model(RtModel::class)->aktif();
        $data['rws'] = model(RwModel::class)->aktif();

        return $this->loadViews('admin/ubah_user', $this->global, $data);
    }

    public function update($id)
    {
        if (empty($this->request->getPost())) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $users = auth()->getProvider();
        $user = $users->findById($id);

        $username = $this->request->getPost('username');

        if ($username !== $user->username) {
            $existingUser = $users->findByCredentials(['username' => $username]);
            if ($existingUser && (int) $existingUser->id !== (int) $id) {
                setFlashData('error', 'Data user gagal diubah! Username ' . $username . ' sudah digunakan.');
                return redirect()->to(back());
            }
        }

        $user->username = $username;

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

        // Determine tenant binding and Shield group
        $isSuperadmin = auth()->user()->inGroup('superadmin');

        if ($isSuperadmin) {
            $idRt = $this->request->getPost('id_rt');
            $idRw = $this->request->getPost('id_rw');

            $targetGroup = 'superadmin';
            $rtVal = null;
            $rwVal = null;

            if (!empty($idRt)) {
                $targetGroup = 'admin';
                $rtVal = (int) $idRt;
            } elseif (!empty($idRw)) {
                $targetGroup = 'rw';
                $rwVal = (int) $idRw;
            }

            // Update tenant columns directly
            db_connect()->table('users')
                ->where('id', $id)
                ->update([
                    'id_rt' => $rtVal,
                    'id_rw' => $rwVal,
                ]);

            $user->syncGroups($targetGroup);
        }

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

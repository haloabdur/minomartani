<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RtModel;
use App\Models\RwModel;
use CodeIgniter\Shield\Entities\User;

class Users extends BaseController
{
    /**
     * Menus exclusive to the 'admin' group, mapped permission key =>
     * checkbox label. Enforced both here (via User::syncPermissions())
     * and at the route level (see Config\Routes' 'menuaccess' filter).
     */
    private const ADMIN_MENU_PERMISSIONS = [
        'menu.warga'  => 'Warga',
        'menu.alamat' => 'Alamat',
        'menu.berita' => 'Berita',
    ];

    /**
     * Menu exclusive to the 'rw' group.
     */
    private const RW_MENU_PERMISSIONS = [
        'menu.rekap' => 'Rekap RW',
    ];

    /**
     * Relevant to - and independently assignable for - both 'admin' and
     * 'rw' target groups. Superadmin can freely toggle this per user in
     * either role; it's no longer an automatic group-wide bypass.
     */
    private const SHARED_MENU_PERMISSIONS = [
        'menu.kesehatan' => 'Kesehatan Lansia',
    ];

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
        $data['menuOptions'] = self::ADMIN_MENU_PERMISSIONS;
        $data['rwMenuOptions'] = self::RW_MENU_PERMISSIONS;
        $data['sharedMenuOptions'] = self::SHARED_MENU_PERMISSIONS;
        // Checked by default: leaving every box ticked preserves today's
        // behavior (full access for a new admin/rw) unless the creator
        // deliberately unchecks something to restrict this user.
        $data['userPermissions'] = array_merge(
            array_keys(self::ADMIN_MENU_PERMISSIONS),
            array_keys(self::RW_MENU_PERMISSIONS),
            array_keys(self::SHARED_MENU_PERMISSIONS)
        );
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

        if (! $this->hasAtLeastOneMenu($targetGroup)) {
            setFlashData('error', 'Pilih minimal 1 menu akses untuk user ini!');
            return redirect()->to(back());
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
        $this->syncMenuPermissions($user, $targetGroup);

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
        $data['menuOptions'] = self::ADMIN_MENU_PERMISSIONS;
        $data['rwMenuOptions'] = self::RW_MENU_PERMISSIONS;
        $data['sharedMenuOptions'] = self::SHARED_MENU_PERMISSIONS;
        $data['userPermissions'] = $data['user']->getPermissions() ?? [];

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

            if (! $this->hasAtLeastOneMenu($targetGroup)) {
                setFlashData('error', 'Pilih minimal 1 menu akses untuk user ini!');
                return redirect()->to(back());
            }

            // Update tenant columns directly
            db_connect()->table('users')
                ->where('id', $id)
                ->update([
                    'id_rt' => $rtVal,
                    'id_rw' => $rwVal,
                ]);

            $user->syncGroups($targetGroup);
            $this->syncMenuPermissions($user, $targetGroup);
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

    /**
     * Syncs the posted menu_akses[] checkboxes onto the user as explicit
     * 'menu.*' permissions. Only meaningful for the 'admin'/'rw' groups -
     * a superadmin target already gets full menu access from the group
     * matrix wildcard, so any stale per-user menu permissions are
     * cleared instead.
     */
    private function syncMenuPermissions(User $user, string $targetGroup): void
    {
        $allowed = $this->assignableMenusFor($targetGroup);

        if ($allowed === null) {
            $user->syncPermissions();
            return;
        }

        $posted = (array) $this->request->getPost('menu_akses');
        $toSync = array_values(array_intersect($posted, array_keys($allowed)));

        $user->syncPermissions(...$toSync);
    }

    /**
     * Neither 'admin' nor 'rw' has a guaranteed fallback menu anymore -
     * Kesehatan Lansia is a normal per-user toggle for both, same as
     * every other menu - so each must have at least one menu_akses[]
     * checkbox checked or the account would be created with zero
     * reachable menus. Other target groups (superadmin) don't use
     * per-user menu permissions at all.
     */
    private function hasAtLeastOneMenu(string $targetGroup): bool
    {
        $allowed = $this->assignableMenusFor($targetGroup);

        if ($allowed === null) {
            return true;
        }

        $posted = (array) $this->request->getPost('menu_akses');

        return array_intersect($posted, array_keys($allowed)) !== [];
    }

    private function assignableMenusFor(string $targetGroup): ?array
    {
        return match ($targetGroup) {
            'admin' => self::ADMIN_MENU_PERMISSIONS + self::SHARED_MENU_PERMISSIONS,
            'rw'    => self::RW_MENU_PERMISSIONS + self::SHARED_MENU_PERMISSIONS,
            default => null,
        };
    }
}

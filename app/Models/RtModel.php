<?php

namespace App\Models;

use CodeIgniter\Model;

class RtModel extends Model
{
    protected $table         = 'rt';
    protected $primaryKey    = 'id_rt';
    protected $returnType    = 'object';
    protected $allowedFields = ['id_rw', 'nama', 'slug', 'is_aktif'];

    public function bySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->where('is_aktif', 1)->first();
    }

    /** @return list<object> */
    public function aktif(): array
    {
        return $this->where('is_aktif', 1)->orderBy('nama')->findAll();
    }

    /** @return list<object> */
    public function byRw(int $idRw): array
    {
        return $this->where('id_rw', $idRw)->where('is_aktif', 1)->orderBy('nama')->findAll();
    }
}

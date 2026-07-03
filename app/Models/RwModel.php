<?php

namespace App\Models;

use CodeIgniter\Model;

class RwModel extends Model
{
    protected $table         = 'rw';
    protected $primaryKey    = 'id_rw';
    protected $returnType    = 'object';
    protected $allowedFields = ['nama', 'slug', 'is_aktif'];

    /** @return list<object> */
    public function aktif(): array
    {
        return $this->where('is_aktif', 1)->orderBy('nama')->findAll();
    }
}

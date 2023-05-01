<?php

namespace App\Controllers;

class Kelas extends BaseController
{
    public function getIndex()
    {
        return $this->respond($this->db->query(
            'SELECT * FROM kelass WHERE IS_DELETED_KELAS IS NULL;'
            )->getResultArray(), 200);
    }
}
<?php

namespace App\Controllers;

class Promo extends BaseController
{
    public function getIndex()
    {
        return $this->respond($this->db->query('SELECT * FROM promos WHERE IS_DELETED_PROMO IS NULL')->getResultArray(), 200);
    }
}
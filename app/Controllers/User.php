<?php

namespace App\Controllers;

class User extends BaseController
{
    public function getInformasi()
    {
        $data = [];
        $data['JADWAL_HARIAN'] = $this->db->query(
            'SELECT * FROM jadwal_harians NATURAL JOIN jadwals NATURAL JOIN kelass NATURAL JOIN users WHERE IS_DELETED_JADWAL_HARIAN IS NULL AND IS_DELETED_JADWAL IS NULL;'
            )->getResultArray();
        $data['PROMO'] = $this->db->query(
            'SELECT * FROM promos WHERE IS_DELETED_PROMO IS NULL;'
            )->getResultArray();
        $data['KELAS'] = $this->db->query(
            'SELECT * FROM kelass WHERE IS_DELETED_KELAS IS NULL;'
            )->getResultArray();
        return $this->respond($data, 200);
    }
}
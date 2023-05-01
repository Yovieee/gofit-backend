<?php

namespace App\Controllers;

class Jadwalpaket extends BaseController
{
    public function getIndex()
    {
        $data = [[]];
        for($i = 0; $i < 7; $i++) {
            for($j = 0; $j < 18; $j++) {
                $data[$i][$j] = $this->db->query(
                    'SELECT * FROM jadwal_pakets NATURAL JOIN jadwals NATURAL JOIN kelass NATURAL JOIN users WHERE IS_DELETED_JADWAL_PAKET IS NULL AND IS_DELETED_JADWAL IS NULL AND HARI_JADWAL_PAKET = "' . $i . '" AND SESI_JADWAL = "' . $j . '";'
                    )->getResultArray();  
            }
        }
        return $this->respond($data, 200);
    }
    public function postUpdate()
    {
        $data = $this->request->getJSON();
        if(!$this->db->query(
            'SELECT ID_INSTRUKTUR, ID_JADWAL_PAKET FROM jadwal_pakets NATURAL JOIN jadwals
            WHERE HARI_JADWAL_PAKET = "' . $data->HARI_JADWAL_PAKET . '" AND SESI_JADWAL = "' . $data->SESI_JADWAL . '" AND ID_INSTRUKTUR = "' . $data->ID_INSTRUKTUR . '" AND ID_JADWAL_PAKET != "' . $data->ID_JADWAL_PAKET . '";')
            ->getResultArray())
        {
            $this->db->query(
                'UPDATE jadwals J INNER JOIN jadwal_pakets JP ON J.ID_JADWAL = JP.ID_JADWAL
                SET J.ID_USER = "' . $data->ID_USER . '",
                    J.ID_INSTRUKTUR = "' . $data->ID_INSTRUKTUR . '",
                    J.ID_KELAS = "' . $data->ID_KELAS . '",
                    J.SESI_JADWAL = "' . $data->SESI_JADWAL . '",
                    JP.HARI_JADWAL_PAKET = "' . $data->HARI_JADWAL_PAKET . '"
                WHERE J.ID_JADWAL = "' . $data->ID_JADWAL . '" AND JP.ID_JADWAL_PAKET = "' . $data->ID_JADWAL_PAKET . '";'
            );
            return $this->respond($data, 200);
        }
        else
        {
            return $this->respond($data, 400);
        }
    }
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $this->db->transStart();
        $this->db->query(
            'INSERT INTO jadwals (
                ID_JADWAL,
                ID_KELAS,
                ID_USER,
                ID_INSTRUKTUR,
                SESI_JADWAL)
            VALUES ("'
                . $data->ID_JADWAL . '", "'
                . $data->ID_KELAS . '", "'
                . $data->ID_USER . '", "'
                . $data->ID_INSTRUKTUR . '", "'
                . $data->SESI_JADWAL
                . '");');
        $this->db->query(
            'INSERT INTO jadwal_pakets (
                ID_JADWAL,
                ID_JADWAL_PAKET,
                HARI_JADWAL_PAKET)
            VALUES ("'
                . $data->ID_JADWAL . '", "'
                . $data->ID_JADWAL_PAKET . '", "'
                . $data->HARI_JADWAL_PAKET
                . '");'
            );
        $this->db->transComplete();
        return $this->respondCreated($data);
    }
    public function postDelete()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE jadwals J INNER JOIN jadwal_pakets JP ON J.ID_JADWAL = JP.ID_JADWAL
            SET J.IS_DELETED_JADWAL = 1,
                JP.IS_DELETED_JADWAL_PAKET = 1
            WHERE J.ID_JADWAL = "' . $data->ID_JADWAL . '" AND JP.ID_JADWAL_PAKET = "' . $data->ID_JADWAL_PAKET . '";'
        );
        return $this->respondDeleted($data);
    }
    public function postUnavailableInstructors()
    {   
        $data = $this->request->getJSON();
        return $this->respond(
            $this->db->query(
            'SELECT * FROM instrukturs NATURAL JOIN users
            WHERE ID_INSTRUKTUR IN (
                SELECT ID_INSTRUKTUR FROM jadwal_pakets NATURAL JOIN jadwals
                WHERE HARI_JADWAL_PAKET = "' . $data->HARI_JADWAL_PAKET . '" AND SESI_JADWAL = "' . $data->SESI_JADWAL . '"
                AND IS_DELETED_JADWAL IS NULL AND IS_DELETED_JADWAL_PAKET IS NULL)
                    ORDER BY RAND();')
            ->getResultArray(), 200);
    }
}
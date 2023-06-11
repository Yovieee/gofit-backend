<?php

namespace App\Controllers;

class Depositkelas extends BaseController
{
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $NO_STRUK_DEPOSIT_KELAS = date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
            'SELECT COUNT(NO_STRUK_DEPOSIT_KELAS)+1 FROM deposit_kelass
            WHERE NO_STRUK_DEPOSIT_KELAS LIKE "' . date("y") . '.' . date("m") . '.%";'
            )->getResultArray()[0]['COUNT(NO_STRUK_DEPOSIT_KELAS)+1']);
        $this->db->query(
            'INSERT INTO deposit_kelass (
                NO_STRUK_DEPOSIT_KELAS,
                MEM_ID_USER,
                ID_MEMBER,
                ID_KELAS,
                PEG_ID_USER,
                ID_PEGAWAI,
                TANGGAL_DEPOSIT_KELAS,
                TANGGAL_KADALUARSA_DEPOSIT_KELAS,
                JUMLAH_DEPOSIT_KELAS,
                JUMLAH_BAYAR_DEPOSIT_KELAS,
                BONUS_DEPOSIT_KELAS)
            VALUES ("'
                . $NO_STRUK_DEPOSIT_KELAS . '", "'
                . $data->MEM_ID_USER . '", "'
                . $data->ID_MEMBER . '", "'
                . $data->ID_KELAS . '", "'
                . $data->PEG_ID_USER . '", "'
                . $data->ID_PEGAWAI . '", "'
                . date("Y-m-d H:i:s") . '", "'
                . date("Y-m-d H:i:s",strtotime("+1 month")) . '", "'
                . $data->JUMLAH_DEPOSIT_KELAS . '", "'
                . $data->JUMLAH_BAYAR_DEPOSIT_KELAS . '", "'
                . $data->BONUS_DEPOSIT_KELAS
                . '");'
            );
        return $this->respondCreated($data);
    }
}
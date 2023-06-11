<?php

namespace App\Controllers;

class Deposituang extends BaseController
{
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $NO_STRUK_DEPOSIT_UANG = date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
            'SELECT COUNT(NO_STRUK_DEPOSIT_UANG)+1 FROM deposit_uangs
            WHERE NO_STRUK_DEPOSIT_UANG LIKE "' . date("y") . '.' . date("m") . '.%";'
            )->getResultArray()[0]['COUNT(NO_STRUK_DEPOSIT_UANG)+1']);
        $SISA_DEPOSIT_UANG_SAAT_INI = $this->db->query(
            'SELECT SISA_DEPOSIT_UANG FROM deposit_uangs
            WHERE ID_MEMBER = "' . $data->ID_MEMBER . '" AND IS_DELETED_DEPOSIT_UANG IS NULL ORDER BY TANGGAL_DEPOSIT_UANG DESC LIMIT 1;'
            )->getResultArray();
        if(count($SISA_DEPOSIT_UANG_SAAT_INI) == 0) {
            $SISA_DEPOSIT_UANG_SAAT_INI = 0;
        } else {
            $SISA_DEPOSIT_UANG_SAAT_INI = $SISA_DEPOSIT_UANG_SAAT_INI[0]['SISA_DEPOSIT_UANG'];
        }
        $SISA_DEPOSIT_UANG = $SISA_DEPOSIT_UANG_SAAT_INI + $data->JUMLAH_DEPOSIT_UANG + $data->BONUS_DEPOSIT_UANG;
        $this->db->query(
            'INSERT INTO deposit_uangs (
                NO_STRUK_DEPOSIT_UANG,
                MEM_ID_USER,
                ID_MEMBER,
                PEG_ID_USER,
                ID_PEGAWAI,
                JUMLAH_DEPOSIT_UANG,
                BONUS_DEPOSIT_UANG,
                SISA_DEPOSIT_UANG,
                TANGGAL_DEPOSIT_UANG)
            VALUES ("'
                . $NO_STRUK_DEPOSIT_UANG . '", "'
                . $data->MEM_ID_USER . '", "'
                . $data->ID_MEMBER . '", "'
                . $data->PEG_ID_USER . '", "'
                . $data->ID_PEGAWAI . '", "'
                . $data->JUMLAH_DEPOSIT_UANG . '", "'
                . $data->BONUS_DEPOSIT_UANG . '", "'
                . $SISA_DEPOSIT_UANG . '", "'
                . date("Y-m-d H:i:s")
                . '");'
            );
        return $this->respondCreated($data);
    }
}
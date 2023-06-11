<?php

namespace App\Controllers;

class Membership extends BaseController
{
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $ID_MEMBERSHIP = date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
            'SELECT COUNT(ID_MEMBERSHIP)+1 FROM memberships
            WHERE ID_MEMBERSHIP LIKE "' . date("y") . '.' . date("m") . '.%";'
            )->getResultArray()[0]['COUNT(ID_MEMBERSHIP)+1']);
        $this->db->query(
            'INSERT INTO memberships (
                ID_MEMBERSHIP,
                MEM_ID_USER,
                ID_MEMBER,
                PEG_ID_USER,
                ID_PEGAWAI,
                TANGGAL_AKTIVASI_MEMBERSHIP,
                TANGGAL_KADALUARSA_MEMBERSHIP)
            VALUES ("'
                . $ID_MEMBERSHIP . '", "'
                . $data->MEM_ID_USER . '", "'
                . $data->ID_MEMBER . '", "'
                . $data->PEG_ID_USER . '", "'
                . $data->ID_PEGAWAI . '", "'
                . $data->TANGGAL_AKTIVASI_MEMBERSHIP . '", "'
                . $data->TANGGAL_KADALUARSA_MEMBERSHIP
                . '");');
        return $this->respond(array('ID_MEMBERSHIP'=>$ID_MEMBERSHIP), 200);
    }
}
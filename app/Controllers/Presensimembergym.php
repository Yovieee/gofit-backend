<?php

namespace App\Controllers;

class Presensimembergym extends BaseController
{
    public function getIndex()
    {
        $data = $this->db->query(
            'SELECT * FROM presensi_member_gyms PMG LEFT OUTER JOIN users U ON PMG.ID_USER = U.ID_USER WHERE IS_DELETED_PRESENSI_MEMBER_GYM IS NULL;'
            )->getResultArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i]['BOOKING'] = $this->db->query(
                'SELECT * FROM booking_gyms NATURAL JOIN users WHERE ID_BOOKING_GYM = "' . $data[$i]['ID_BOOKING_GYM'] . '";'
                )->getResultArray()[0];
        }
        return $this->respond($data, 200);
    }
    public function postHadirkan()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_member_gyms
            SET STATUS_PRESENSI_MEMBER_GYM = "1",
                ID_USER = "' . $data->ID_USER . '",
                ID_PEGAWAI = "' . $data->ID_PEGAWAI . '",
                TANGGAL_PRESENSI_MEMBER_GYM = "' . date('Y-m-d') . '"
            WHERE NO_STRUK_PRESENSI_MEMBER_GYM = "' . $data->NO_STRUK_PRESENSI_MEMBER_GYM . '";'
        );
        return $this->respond(null, 200);
    }
    public function postAbsenkan()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_member_gyms
            SET STATUS_PRESENSI_MEMBER_GYM = "0",
                ID_USER = "' . $data->ID_USER . '",
                ID_PEGAWAI = "' . $data->ID_PEGAWAI . '",
                TANGGAL_PRESENSI_MEMBER_GYM = "' . date('Y-m-d') . '"
            WHERE NO_STRUK_PRESENSI_MEMBER_GYM = "' . $data->NO_STRUK_PRESENSI_MEMBER_GYM . '";'
        );
        return $this->respond(null, 200);
    }
}
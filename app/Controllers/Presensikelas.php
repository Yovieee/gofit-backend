<?php

namespace App\Controllers;

class Presensikelas extends BaseController
{
    public function getIndex()
    {
        $data = $this->db->query(
            'SELECT * FROM presensi_kelass WHERE IS_DELETED_PRESENSI_KELAS IS NULL;'
            )->getResultArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i]['BOOKING'] = $this->db->query(
                'SELECT * FROM booking_kelass NATURAL JOIN users WHERE ID_BOOKING_KELAS = "' . $data[$i]['ID_BOOKING_KELAS'] . '";'
                )->getResultArray()[0];
            $data[$i]['BOOKING']['JADWAL'] = $this->db->query(
                'SELECT * FROM jadwals NATURAL JOIN jadwal_harians NATURAL JOIN users NATURAL JOIN kelass WHERE ID_JADWAL = "' . $data[$i]['BOOKING']['ID_JADWAL'] . '";'
                )->getResultArray()[0];
        }
        return $this->respond($data, 200);
    }
    public function postHadirkan()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_kelass
            SET STATUS_PRESENSI_KELAS = "1",
                TANGGAL_PRESENSI_KELAS = "' . date('Y-m-d') . '"
            WHERE NO_STRUK_PRESENSI_KELAS = "' . $data->NO_STRUK_PRESENSI_KELAS . '";'
        );
        return $this->respond(null, 200);
    }
    public function postAbsenkan()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_kelass
            SET STATUS_PRESENSI_KELAS = NULL,
                TANGGAL_PRESENSI_KELAS = "' . date('Y-m-d') . '"
            WHERE NO_STRUK_PRESENSI_KELAS = "' . $data->NO_STRUK_PRESENSI_KELAS . '";'
        );
        return $this->respond(null, 200);
    }
}
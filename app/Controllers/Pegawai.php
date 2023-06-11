<?php

namespace App\Controllers;

class Pegawai extends BaseController
{
    public function getRetrieveKelasHariIni()
    {
        $data = [];
        for($j = 0; $j < 18; $j++) {
            $data[$j] = $this->db->query(
                'SELECT * FROM jadwal_harians NATURAL JOIN jadwals NATURAL JOIN users NATURAL JOIN kelass NATURAL JOIN presensi_instrukturs
                WHERE SESI_JADWAL = "' . $j . '" AND TANGGAL_JADWAL_HARIAN = "' . date('Y-m-d') . '" AND IS_DELETED_JADWAL_HARIAN IS NULL AND IS_DELETED_JADWAL IS NULL;'
                )->getResultArray();
            for($k = 0; $k < count($data[$j]); $k++) {
                if($data[$j][$k]['JAD_ID_JADWAL'] != null) {
                    $data[$j][$k]['NAMA_INSTRUKTUR_SEBELUMNYA'] = $this->db->query('SELECT NAMA_USER FROM users NATURAL JOIN jadwals WHERE ID_JADWAL = "' . $data[$j][$k]['JAD_ID_JADWAL'] . '";')->getResultArray()[0]['NAMA_USER'];
                }
                $data[$j][$k]['SISA_KUOTA'] = $this->db->query('SELECT 10-COUNT(ID_BOOKING_KELAS) FROM booking_kelass WHERE ID_JADWAL = "'. $data[$j][$k]['ID_JADWAL'] .'" AND IS_DELETED_BOOKING_KELAS IS NULL;')->getResultArray()[0]['10-COUNT(ID_BOOKING_KELAS)'];
            }
        }
        return $this->respond($data, 200);
    }
}
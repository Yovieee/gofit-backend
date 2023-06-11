<?php

namespace App\Controllers;

class Bookingkelas extends BaseController
{
    public function getIndex()
    {
        return $this->respond($this->db->query(
            'SELECT * FROM booking_kelass'
            )->getResultArray(), 200);
    }
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $ID_BOOKING_KELAS = base64_encode(random_bytes(24));
        $NO_STRUK_PRESENSI_KELAS =date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
            'SELECT COUNT(NO_STRUK_PRESENSI_KELAS)+1 FROM presensi_kelass
            WHERE NO_STRUK_PRESENSI_KELAS LIKE "' . date("y") . '.' . date("m") . '.%";'
            )->getResultArray()[0]['COUNT(NO_STRUK_PRESENSI_KELAS)+1']);
        $this->db->transStart();
        $this->db->query(
            'INSERT INTO booking_kelass (
                ID_BOOKING_KELAS,
                ID_JADWAL,
                ID_USER,
                ID_MEMBER,
                TANGGAL_BOOKING_KELAS)
            VALUES ("'
                . $ID_BOOKING_KELAS . '", "'
                . $data->ID_JADWAL . '", "'
                . $data->ID_USER . '", "'
                . $data->ID_MEMBER . '", "'
                . date('Y-m-d')
                . '");'
            );
        $this->db->query(
            'INSERT INTO presensi_kelass (
                NO_STRUK_PRESENSI_KELAS,
                ID_BOOKING_KELAS)
            VALUES ("'
                . $NO_STRUK_PRESENSI_KELAS . '", "'
                . $ID_BOOKING_KELAS
                . '");'
            );
        $this->db->query(
            'UPDATE booking_kelass
            SET NO_STRUK_PRESENSI_KELAS = "' . $NO_STRUK_PRESENSI_KELAS . '"
            WHERE ID_BOOKING_KELAS = "' . $ID_BOOKING_KELAS . '";'
            );
        $this->db->transComplete();
        return $this->respondCreated(null);
    }
    public function postDelete()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE booking_kelass
            SET IS_DELETED_BOOKING_KELAS = 1
            WHERE ID_BOOKING_KELAS = "' . $data->ID_BOOKING_KELAS . '";'
        );
        return $this->respondDeleted(null);
    }
    public function postListKelas()
    {
        $ID_USER = $this->request->getJSON()->ID_USER;
        $data = [[]];
        for($i = 0; $i < 7; $i++) {
            for($j = 0; $j < 18; $j++) {
                $data[$i][$j] = $this->db->query(
                    'SELECT * FROM jadwal_harians NATURAL JOIN jadwals NATURAL JOIN users NATURAL JOIN kelass
                    WHERE TANGGAL_JADWAL_HARIAN >= (SELECT MAX(TANGGAL_JADWAL_HARIAN)
                    FROM jadwal_harians
                    WHERE DAYOFWEEK(TANGGAL_JADWAL_HARIAN) = 1) AND DATEDIFF((SELECT MAX(TANGGAL_JADWAL_HARIAN)
                    FROM jadwal_harians
                    WHERE DAYOFWEEK(TANGGAL_JADWAL_HARIAN) = 1), NOW()) >= -7 AND DAYOFWEEK(TANGGAL_JADWAL_HARIAN) = "' . $i + 1 . '" AND SESI_JADWAL = "' . $j . '" AND IS_DELETED_JADWAL_HARIAN IS NULL AND IS_DELETED_JADWAL IS NULL;'
                    )->getResultArray();
                for($k = 0; $k < count($data[$i][$j]); $k++) {
                    if($data[$i][$j][$k]['JAD_ID_JADWAL'] != null) {
                        $data[$i][$j][$k]['NAMA_INSTRUKTUR_SEBELUMNYA'] = $this->db->query('SELECT NAMA_USER FROM users NATURAL JOIN jadwals WHERE ID_JADWAL = "' . $data[$i][$j][$k]['JAD_ID_JADWAL'] . '";')->getResultArray()[0]['NAMA_USER'];
                    }
                    $data[$i][$j][$k]['SISA_KUOTA'] = $this->db->query('SELECT 10-COUNT(ID_BOOKING_KELAS) FROM booking_kelass WHERE ID_JADWAL = "'. $data[$i][$j][$k]['ID_JADWAL'] .'" AND IS_DELETED_BOOKING_KELAS IS NULL;')->getResultArray()[0]['10-COUNT(ID_BOOKING_KELAS)'];
                    $data[$i][$j][$k]['SUDAH_DIAMBIL'] = $this->db->query('SELECT COUNT(ID_BOOKING_KELAS) FROM booking_kelass WHERE ID_JADWAL = "'. $data[$i][$j][$k]['ID_JADWAL'] .'" AND ID_USER = "' . $ID_USER . '" AND IS_DELETED_BOOKING_KELAS IS NULL;')->getResultArray()[0]['COUNT(ID_BOOKING_KELAS)'];
                    if($data[$i][$j][$k]['SUDAH_DIAMBIL'] > 0) {
                        $data[$i][$j][$k]['ID_BOOKING_KELAS'] = $this->db->query('SELECT ID_BOOKING_KELAS FROM booking_kelass WHERE ID_JADWAL = "'. $data[$i][$j][$k]['ID_JADWAL'] .'" AND ID_USER = "' . $ID_USER . '" AND IS_DELETED_BOOKING_KELAS IS NULL;')->getResultArray()[0]['ID_BOOKING_KELAS'];
                    }
                }
            }
        }
        $STATUS_AKTIVASI = $this->db->query(
            'SELECT COUNT(ID_MEMBERSHIP) FROM memberships
            WHERE TANGGAL_KADALUARSA_MEMBERSHIP > NOW() AND MEM_ID_USER = "' . $ID_USER . '";'
            )->getResultArray()[0]['COUNT(ID_MEMBERSHIP)'];
        array_unshift($data, ['STATUS_AKTIVASI' => $STATUS_AKTIVASI]);
        return $this->respond($data, 200);
    }
}
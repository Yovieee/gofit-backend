<?php

namespace App\Controllers;

class Instruktur extends BaseController
{
    public function getIndex()
    {
        return $this->respond($this->db->query(
            'SELECT * FROM instrukturs NATURAL JOIN users WHERE IS_DELETED_INSTRUKTUR IS NULL AND IS_DELETED_USER IS NULL;'
            )->getResultArray(), 200);
    }
    public function postUpdate()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE instrukturs I INNER JOIN users U ON U.ID_USER = I.ID_USER
            SET U.NAMA_USER = "' . $data->NAMA_USER . '",
                U.EMAIL_USER = "' . $data->EMAIL_USER . '",
                U.FOTO_USER = "' . $data->FOTO_USER . '",
                U.TANGGAL_LAHIR_USER = "' . $data->TANGGAL_LAHIR_USER . '",
                I.DESKRIPSI_INSTRUKTUR = "' . $data->DESKRIPSI_INSTRUKTUR . '"
            WHERE I.ID_INSTRUKTUR = "' . $data->ID_INSTRUKTUR . '" AND U.ID_USER = "' . $data->ID_USER . '";'
        );
        return $this->respond($data, 200);
    }
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $this->db->transStart();
        $this->db->query(
            'INSERT INTO users (
                ID_USER,
                NAMA_USER,
                TANGGAL_DIBUAT_USER,
                FOTO_USER,
                EMAIL_USER,
                PASSWORD_USER,
                TANGGAL_LAHIR_USER)
            VALUES ("'
                . $data->ID_USER . '", "'
                . $data->NAMA_USER . '", "'
                . date("Y-m-d") . '", "'
                . $data->FOTO_USER . '", "'
                . $data->EMAIL_USER . '", "'
                . password_hash($data->TANGGAL_LAHIR_USER, PASSWORD_BCRYPT) . '", "'
                . $data->TANGGAL_LAHIR_USER
                . '");');
        $this->db->query(
            'INSERT INTO instrukturs (
                ID_INSTRUKTUR,
                ID_USER,
                DESKRIPSI_INSTRUKTUR)
            VALUES ("'
                . $data->ID_INSTRUKTUR . '", "'
                . $data->ID_USER . '", "'
                . $data->DESKRIPSI_INSTRUKTUR
                . '");'
            );
        $this->db->transComplete();
        return $this->respondCreated($data);
    }
    public function postDelete()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE users U INNER JOIN instrukturs I ON U.ID_USER = I.ID_USER
            SET I.IS_DELETED_INSTRUKTUR = 1,
                U.IS_DELETED_USER = 1
            WHERE I.ID_INSTRUKTUR = "' . $data->ID_INSTRUKTUR . '" AND U.ID_USER = "' . $data->ID_USER . '";'
        );
        return $this->respondDeleted($data);
    }
    public function getGenerateID()
    {
        return $this->respond(['I' . sprintf("%02d", $this->db->query(
            'SELECT COUNT(ID_INSTRUKTUR)+1 FROM instrukturs;'
            )->getResultArray()[0]['COUNT(ID_INSTRUKTUR)+1'])], 200);
    }
    public function postRetrieveJadwal()
    {
        $ID_USER = $this->request->getJSON()->ID_USER;
        $data = $this->db->query(
            'SELECT * FROM jadwals NATURAL JOIN users NATURAL JOIN kelass NATURAL JOIN jadwal_harians WHERE ID_USER = "' . $ID_USER . '";'
            )->getResultArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i]['PRESENSI_INSTRUKTUR'] = $this->db->query(
                'SELECT * FROM presensi_instrukturs PI LEFT OUTER JOIN users U ON PI.PEG_ID_USER = U.ID_USER WHERE PI.ID_JADWAL = "' . $data[$i]['ID_JADWAL'] . '" AND PI.IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
                )->getResultArray()[0];
            $data[$i]['DAFTAR_MEMBER'] = $this->db->query(
                'SELECT * FROM members NATURAL JOIN users NATURAL JOIN booking_kelass NATURAL JOIN presensi_kelass WHERE ID_JADWAL = "' . $data[$i]['ID_JADWAL'] . '" AND IS_DELETED_MEMBER IS NULL;'
                )->getResultArray();
        }
        return $this->respond($data, 200);
    }
    public function postTampilProfil()
    {
        $ID_USER = $this->request->getJSON()->ID_USER;
        $BULAN = sprintf("%02d",$this->request->getJSON()->BULAN);
        $TAHUN = $this->request->getJSON()->TAHUN;
        $BULAN_BATAS = $BULAN == 12 ? '01' : sprintf("%02d",$BULAN+1);
        $TAHUN_BATAS = $BULAN == 12 ? $TAHUN+1 : $TAHUN;
        $data = [];
        $data['JUMLAH_WAKTU_TERLAMBAT'] = $this->db->query(
            'SELECT SUM(DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR) FROM presensi_instrukturs NATURAL JOIN jadwals
            WHERE ID_USER = "' . $ID_USER . '" AND TANGGAL_PRESENSI_INSTRUKTUR >= "' . $TAHUN . '-' . $BULAN . '-01" AND TANGGAL_PRESENSI_INSTRUKTUR < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
            )->getResultArray()[0]['SUM(DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR)'] ?? 0;
        $data['AKTIVITAS'] = $this->db->query(
            'SELECT * FROM presensi_instrukturs NATURAL JOIN jadwals NATURAL JOIN kelass
            WHERE ID_USER = "' . $ID_USER . '" AND TANGGAL_PRESENSI_INSTRUKTUR IS NOT NULL AND IS_DELETED_PRESENSI_INSTRUKTUR IS NULL'
        )->getResultArray();
        for($i=0; $i<count($data['AKTIVITAS']); $i++){
            $data['AKTIVITAS'][$i]['JUMLAH_MEMBER'] = $this->db->query(
                'SELECT SUM(STATUS_PRESENSI_KELAS) FROM presensi_kelass NATURAL JOIN booking_kelass
                WHERE ID_JADWAL = "' . $data['AKTIVITAS'][$i]['ID_JADWAL'] . '" AND IS_DELETED_PRESENSI_KELAS IS NULL;'
            )->getResultArray()[0]['SUM(STATUS_PRESENSI_KELAS)'] ?? 0;
        }
        return $this->respond($data, 200);
    }
}
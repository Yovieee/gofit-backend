<?php

namespace App\Controllers;

class Member extends BaseController
{
    public function getIndex()
    {
        $data = $this->db->query(
            'SELECT * FROM members NATURAL JOIN users WHERE IS_DELETED_MEMBER IS NULL AND IS_DELETED_USER IS NULL;'
            )->getResultArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i]['MEMBERSHIP_TRANSACTIONS'] = $this->db->query(
                'SELECT * FROM memberships M INNER JOIN users U ON M.PEG_ID_USER = U.ID_USER WHERE M.ID_MEMBER = "' . $data[$i]['ID_MEMBER'] . '" ORDER BY M.TANGGAL_KADALUARSA_MEMBERSHIP DESC;'
                )->getResultArray();
            $data[$i]['MONEY_DEPOSIT_TRANSACTIONS'] = $this->db->query(
                'SELECT * FROM deposit_uangs DU INNER JOIN users U ON DU.PEG_ID_USER = U.ID_USER WHERE DU.ID_MEMBER = "' . $data[$i]['ID_MEMBER'] . '" ORDER BY DU.TANGGAL_DEPOSIT_UANG DESC;'
                )->getResultArray();
            $data[$i]['CLASS_DEPOSIT_TRANSACTIONS'] = $this->db->query(
                'SELECT * FROM deposit_kelass DK INNER JOIN users U ON DK.PEG_ID_USER = U.ID_USER INNER JOIN kelass K ON DK.ID_KELAS = K.ID_KELAS WHERE DK.ID_MEMBER = "' . $data[$i]['ID_MEMBER'] . '" ORDER BY DK.TANGGAL_DEPOSIT_KELAS DESC;'
                )->getResultArray();
        }
        return $this->respond($data, 200);
    }
    public function postUpdate()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE members M INNER JOIN users U ON U.ID_USER = M.ID_USER
            SET U.NAMA_USER = "' . $data->NAMA_USER . '",
                U.EMAIL_USER = "' . $data->EMAIL_USER . '",
                U.FOTO_USER = "' . $data->FOTO_USER . '",
                U.TANGGAL_LAHIR_USER = "' . $data->TANGGAL_LAHIR_USER . '",
                M.ALAMAT_MEMBER = "' . $data->ALAMAT_MEMBER . '",
                M.TELEPON_MEMBER = "' . $data->TELEPON_MEMBER . '"
            WHERE M.ID_MEMBER = "' . $data->ID_MEMBER . '" AND U.ID_USER = "' . $data->ID_USER . '";'
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
            'INSERT INTO members (
                ID_MEMBER,
                ID_USER,
                ALAMAT_MEMBER,
                TELEPON_MEMBER,
                SISA_DEPOSIT_MEMBER)
            VALUES ("'
                . $data->ID_MEMBER . '", "'
                . $data->ID_USER . '", "'
                . $data->ALAMAT_MEMBER . '", "'
                . $data->TELEPON_MEMBER . '", "
                0
                ");'
            );
        $this->db->transComplete();
        return $this->respondCreated($data);
    }
    public function postDelete()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE users U INNER JOIN members M ON U.ID_USER = M.ID_USER
            SET M.IS_DELETED_MEMBER = 1,
                U.IS_DELETED_USER = 1
            WHERE M.ID_MEMBER = "' . $data->ID_MEMBER . '" AND U.ID_USER = "' . $data->ID_USER . '";'
        );
        return $this->respondDeleted($data);
    }
    public function getGenerateID()
    {
        return $this->respond([date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
            'SELECT COUNT(ID_MEMBER)+1 FROM members
            WHERE ID_MEMBER LIKE "' . date("y") . '.' . date("m") . '.%";'
            )->getResultArray()[0]['COUNT(ID_MEMBER)+1'])], 200);
    }
    public function postRetrieveBooking()
    {
        $ID_USER = $this->request->getJSON()->ID_USER;
        $data = [];
        $data['BOOKING_GYM'] = $this->db->query(
            'SELECT * FROM booking_gyms
            WHERE ID_USER = "' . $ID_USER . '" AND IS_DELETED_BOOKING_GYM IS NULL;'
            )->getResultArray();
        $data['BOOKING_KELAS'] = $this->db->query(
            'SELECT * FROM booking_kelass
            WHERE ID_USER = "' . $ID_USER . '";'
            )->getResultArray();
        return $this->respond($data, 200);
    }
    public function postTampilProfil()
    {
        $ID_USER = $this->request->getJSON()->ID_USER;
        $data = [];
        $data['AKTIVASI'] = $this->db->query(
            'SELECT TANGGAL_KADALUARSA_MEMBERSHIP FROM memberships
            WHERE MEM_ID_USER = "' . $ID_USER . '" AND TANGGAL_KADALUARSA_MEMBERSHIP > NOW() AND IS_DELETED_MEMBERSHIP IS NULL ORDER BY TANGGAL_KADALUARSA_MEMBERSHIP DESC;'
            )->getResultArray()[0] ?? ['TANGGAL_KADALUARSA_MEMBERSHIP' => 'This member is not activated yet'];
        $data['DEPOSIT_UANG'] = $this->db->query(
            'SELECT SISA_DEPOSIT_UANG FROM deposit_uangs
            WHERE MEM_ID_USER = "' . $ID_USER . '" AND IS_DELETED_DEPOSIT_UANG IS NULL ORDER BY TANGGAL_DEPOSIT_UANG DESC;'
            )->getResultArray()[0] ?? ['SISA_DEPOSIT_UANG' => 'This member has no deposit'];
        $data['DEPOSIT_KELAS'] = $this->db->query(
            'SELECT * FROM deposit_kelass DK INNER JOIN users U ON DK.PEG_ID_USER = U.ID_USER INNER JOIN kelass K ON DK.ID_KELAS = K.ID_KELAS WHERE DK.MEM_ID_USER = "' . $ID_USER . '" ORDER BY DK.TANGGAL_DEPOSIT_KELAS DESC;'
                )->getResultArray() ?? [];
        $data['AKTIVITAS'] = $this->db->query(
            'SELECT 0 AS JENIS_AKTIVITAS, PMG.TANGGAL_PRESENSI_MEMBER_GYM AS TANGGAL_AKTIVITAS, NULL AS NAMA_USER, NULL AS NAMA_KELAS FROM booking_gyms BG JOIN presensi_member_gyms PMG ON BG.NO_STRUK_PRESENSI_MEMBER_GYM = PMG.NO_STRUK_PRESENSI_MEMBER_GYM WHERE BG.ID_USER = "' . $ID_USER . '" AND PMG.TANGGAL_PRESENSI_MEMBER_GYM IS NOT NULL AND IS_DELETED_PRESENSI_MEMBER_GYM IS NULL
            UNION ALL
            SELECT 1 AS JENIS_AKTIVITAS, PK.TANGGAL_PRESENSI_KELAS AS TANGGAL_AKTIVITAS, U.NAMA_USER AS NAMA_USER, K.NAMA_KELAS AS NAMA_KELAS FROM booking_kelass BK JOIN presensi_kelass PK ON BK.NO_STRUK_PRESENSI_KELAS = PK.NO_STRUK_PRESENSI_KELAS JOIN jadwals J ON J.ID_JADWAL = BK.ID_JADWAL JOIN users U ON U.ID_USER = J.ID_USER JOIN kelass K ON K.ID_KELAS = J.ID_KELAS WHERE BK.ID_USER = "' . $ID_USER . '" AND PK.TANGGAL_PRESENSI_KELAS IS NOT NULL AND PK.IS_DELETED_PRESENSI_KELAS IS NULL
            ORDER BY TANGGAL_AKTIVITAS DESC;'
                )->getResultArray() ?? [];
        return $this->respond($data, 200);
    }
}
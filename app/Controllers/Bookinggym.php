<?php

namespace App\Controllers;

class Bookinggym extends BaseController
{
    public function getIndex()
    {
        return $this->respond($this->db->query(
            'SELECT * FROM booking_gyms'
            )->getResultArray(), 200);
    }
    public function postCreate()
    {
        $data = $this->request->getJSON();
        $IS_SUDAH_BOOK_PADA_HARI_ITU = $this->db->query(
            'SELECT COUNT(NO_STRUK_PRESENSI_MEMBER_GYM) FROM booking_gyms
            WHERE TANGGAL_BOOKING_GYM = "' . $data->TANGGAL_BOOKING_GYM . '" AND ID_MEMBER = "' . $data->ID_MEMBER . '" AND IS_DELETED_BOOKING_GYM IS NULL;'
            )->getResultArray()[0]['COUNT(NO_STRUK_PRESENSI_MEMBER_GYM)'];
        if($IS_SUDAH_BOOK_PADA_HARI_ITU != "0")
        {
            return $this->failValidationError();
        } else {
            $ID_BOOKING_GYM = base64_encode(random_bytes(24));
            $NO_STRUK_PRESENSI_MEMBER_GYM = date("y") . '.' . date("m") . '.' . sprintf("%03d", $this->db->query(
                'SELECT COUNT(NO_STRUK_PRESENSI_MEMBER_GYM)+1 FROM presensi_member_gyms
                WHERE NO_STRUK_PRESENSI_MEMBER_GYM LIKE "' . date("y") . '.' . date("m") . '.%";'
                )->getResultArray()[0]['COUNT(NO_STRUK_PRESENSI_MEMBER_GYM)+1']);
            $this->db->query(
                'INSERT INTO booking_gyms (
                    ID_BOOKING_GYM,
                    ID_USER,
                    ID_MEMBER,
                    TANGGAL_BOOKING_GYM,
                    SESI_BOOKING_GYM)
                VALUES ("'
                    . $ID_BOOKING_GYM . '", "'
                    . $data->ID_USER . '", "'
                    . $data->ID_MEMBER . '", "'
                    . $data->TANGGAL_BOOKING_GYM . '", "'
                    . $data->SESI_BOOKING_GYM
                    . '");'
                );
            $this->db->query(
                'INSERT INTO presensi_member_gyms (
                    NO_STRUK_PRESENSI_MEMBER_GYM,
                    ID_BOOKING_GYM)
                VALUES ("'
                    . $NO_STRUK_PRESENSI_MEMBER_GYM . '", "'
                    . $ID_BOOKING_GYM
                    . '");'
                );
            $this->db->query(
                'UPDATE booking_gyms
                SET NO_STRUK_PRESENSI_MEMBER_GYM = "' . $NO_STRUK_PRESENSI_MEMBER_GYM . '"
                WHERE ID_BOOKING_GYM = "' . $ID_BOOKING_GYM . '";'
                );
            return $this->respondCreated($data);
        }
    }
    public function postDelete()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE booking_gyms
            SET IS_DELETED_BOOKING_GYM = 1
            WHERE ID_BOOKING_GYM = "' . $data->ID_BOOKING_GYM . '";'
        );
        return $this->respondDeleted($data);
    }
}
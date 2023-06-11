<?php

namespace App\Controllers;

class Presensiinstruktur extends BaseController
{
    public function getIndex()
    {
        $data = $this->db->query(
            'SELECT * FROM presensi_instrukturs PI LEFT OUTER JOIN users U ON PI.PEG_ID_USER = U.ID_USER WHERE PI.IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
            )->getResultArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i]['JADWAL'] = $this->db->query(
                'SELECT * FROM jadwals NATURAL JOIN users NATURAL JOIN kelass NATURAL JOIN jadwal_harians WHERE ID_JADWAL = "' . $data[$i]['ID_JADWAL'] . '";'
                )->getResultArray()[0];
        }
        return $this->respond($data, 200);
    }
    public function postAjukanIzin()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_instrukturs
            SET STATUS_PRESENSI_INSTRUKTUR = "3",
                KETERANGAN_PRESENSI_INSTRUKTUR = "' . $data->KETERANGAN_PRESENSI_INSTRUKTUR . '"
            WHERE ID_PRESENSI_INSTRUKTUR = "' . $data->ID_PRESENSI_INSTRUKTUR . '";'
        );
        return $this->respond(null, 200);
    }
    public function postTerimaIzin()
    {
        // To do: perbaiki transfer kelas
        $data = $this->request->getJSON();
        //this->db->transStart();
        if($data->ID_INSTRUKTUR != null) {
            $this->db->query(
                'UPDATE jadwal_harians JH INNER JOIN jadwals J ON JH.ID_JADWAL = J.ID_JADWAL
                SET JH.IS_DELETED_JADWAL_HARIAN = 1
                WHERE J.ID_JADWAL = "' . $data->ID_JADWAL . '";'
            );
            $schedule = $this->db->query(
                'SELECT * FROM jadwal_harians NATURAL JOIN jadwals WHERE ID_JADWAL = "' . $data->ID_JADWAL . '";'
                )->getResultArray()[0];
            $ID_USER = $this->db->query('SELECT ID_USER FROM instrukturs WHERE ID_INSTRUKTUR = "' . $data->ID_INSTRUKTUR . '";')->getResultArray()[0]['ID_USER'];
            $ID_JADWAL = base64_encode(random_bytes(24));
            $ID_JADWAL_HARIAN = base64_encode(random_bytes(24));
            $ID_PRESENSI_INSTRUKTUR = base64_encode(random_bytes(24));
            $this->db->query(
                'INSERT INTO jadwals (
                    ID_JADWAL,
                    ID_KELAS,
                    ID_USER,
                    ID_INSTRUKTUR,
                    SESI_JADWAL,
                    JAD_ID_JADWAL)
                VALUES ("'
                    . $ID_JADWAL . '", "'
                    . $schedule['ID_KELAS'] . '", "'
                    . $ID_USER . '", "'
                    . $data->ID_INSTRUKTUR . '", "'
                    . $schedule['SESI_JADWAL'] . '", "'
                    . $data->ID_JADWAL
                    . '");');
            $this->db->query(
                'INSERT INTO jadwal_harians (
                    ID_JADWAL,
                    ID_JADWAL_HARIAN,
                    TANGGAL_JADWAL_HARIAN)
                VALUES ("'
                    . $ID_JADWAL . '", "'
                    . $ID_JADWAL_HARIAN . '", "'
                    . $schedule['TANGGAL_JADWAL_HARIAN']
                    . '");'
                );
            $this->db->query(
                'INSERT INTO presensi_instrukturs (
                    ID_PRESENSI_INSTRUKTUR,
                    ID_JADWAL)
                    VALUES ("'
                    . $ID_PRESENSI_INSTRUKTUR . '", "'
                    . $ID_JADWAL
                    . '");'
                );
            $bookingKelas = $this->db->query(
                'SELECT ID_BOOKING_KELAS FROM booking_kelass WHERE ID_JADWAL = "' . $data->ID_JADWAL . '";'
                )->getResultArray();
            foreach ($bookingKelas as $bookingKelasItem) {
                var_dump($bookingKelasItem['ID_BOOKING_KELAS']);
                $this->db->query(
                    'UPDATE booking_kelass
                    SET ID_JADWAL = "' . $ID_JADWAL . '"
                    WHERE ID_BOOKING_KELAS = "' . $bookingKelasItem['ID_BOOKING_KELAS'] . '";'
                );
            }
        } else {
            $this->db->query(
                'UPDATE jadwal_harians JH INNER JOIN jadwals J ON JH.ID_JADWAL = J.ID_JADWAL
                SET JH.IS_LIBUR_JADWAL_HARIAN = 1
                WHERE J.ID_JADWAL = "' . $data->ID_JADWAL . '";'
            );
        }
        $this->db->query(
            'UPDATE presensi_instrukturs
            SET STATUS_PRESENSI_INSTRUKTUR = "4",
                PEG_ID_USER = "' . $data->PEG_ID_USER . '",
                ID_PEGAWAI = "'. $data->ID_PEGAWAI .'"
            WHERE ID_PRESENSI_INSTRUKTUR = "' . $data->ID_PRESENSI_INSTRUKTUR . '";'
        );
        //this->db->transComplete();
        return $this->respond(null, 200);
    }
    public function postTolakIzin()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_instrukturs
            SET STATUS_PRESENSI_INSTRUKTUR = "5",
                PEG_ID_USER = "' . $data->PEG_ID_USER . '",
                ID_PEGAWAI = "'. $data->ID_PEGAWAI .'"
            WHERE ID_PRESENSI_INSTRUKTUR = "' . $data->ID_PRESENSI_INSTRUKTUR . '";'
        );
        return $this->respond(null, 200);
    }
    public function postUnavailableInstructors()
    {   
        $data = $this->request->getJSON();
        return $this->respond(
            $this->db->query(
            'SELECT * FROM instrukturs NATURAL JOIN users
            WHERE ID_INSTRUKTUR IN (
                SELECT ID_INSTRUKTUR FROM jadwal_harians NATURAL JOIN jadwals
                WHERE TANGGAL_JADWAL_HARIAN = "' . $data->TANGGAL_JADWAL_HARIAN . '" AND SESI_JADWAL = "' . $data->SESI_JADWAL . '"
                AND IS_DELETED_JADWAL IS NULL AND IS_DELETED_JADWAL_HARIAN IS NULL AND IS_LIBUR_JADWAL_HARIAN IS NULL)
                    ORDER BY RAND();')
            ->getResultArray(), 200);
    }
    public function postHadirkan()
    {
        $data = $this->request->getJSON();
        $jadwal = $this->db->query(
            'SELECT * FROM jadwals NATURAL JOIN jadwal_harians
            WHERE ID_JADWAL = "' . $data->ID_JADWAL . '";'
        )->getResultArray()[0];
        $tanggalJadwal = strtotime(substr($jadwal['TANGGAL_JADWAL_HARIAN'], 0, 10) . ' ' . [
            '06:00',
            '06:30',
            '07:00',
            '07:30',
            '08:00',
            '08:30',
            '09:00',
            '09:30',
            '10:00',
            '17:00',
            '17:30',
            '18:00',
            '18:30',
            '19:00',
            '19:30',
            '20:00',
            '20:30',
            '21:00',
          ][intval($jadwal['SESI_JADWAL'])] . ':00');
        $tanggalSekarang = strtotime(date('Y-m-d H:i:s'));
        if($tanggalSekarang > $tanggalJadwal) {
            $STATUS_PRESENSI_INSTRUKTUR = 2;
            $DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR = $tanggalSekarang - $tanggalJadwal;
        } else {
            $STATUS_PRESENSI_INSTRUKTUR = 1;
            $DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR = 0;
        }
        $this->db->query(
            'UPDATE presensi_instrukturs
            SET STATUS_PRESENSI_INSTRUKTUR = "' . $STATUS_PRESENSI_INSTRUKTUR . '",
                PEG_ID_USER = "' . $data->PEG_ID_USER . '",
                ID_PEGAWAI = "' . $data->ID_PEGAWAI . '",
                TANGGAL_PRESENSI_INSTRUKTUR = "' . date('Y-m-d H:i:s') . '",
                DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR = "' . $DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR . '"
            WHERE ID_PRESENSI_INSTRUKTUR = "' . $data->ID_PRESENSI_INSTRUKTUR . '";'
        );
        return $this->respond(null, 200);
    }
    public function postSelesaikan()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE presensi_instrukturs
            SET JAM_SELESAI_PRESENSI_INSTRUKTUR = NOW()
            WHERE ID_PRESENSI_INSTRUKTUR = "' . $data->ID_PRESENSI_INSTRUKTUR . '";'
        );
        return $this->respond(null, 200);
    }
}
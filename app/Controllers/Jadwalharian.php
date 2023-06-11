<?php

namespace App\Controllers;

class Jadwalharian extends BaseController
{
    private function lastSunday()
    {
        $today = date("Y-m-d");
        if (date("w", strtotime($today)) === "0") {
            $lastSunday = $today;
        } else {
            $lastSunday = date("Y-m-d", strtotime("last Sunday", strtotime($today)));
        }

        return $lastSunday;
    }
    public function getIndex()
    {
        $data = [[]];
        for($i = 0; $i < 7; $i++) {
            for($j = 0; $j < 18; $j++) {
                $data[$i][$j] = $this->db->query(
                    'SELECT * FROM jadwal_harians NATURAL JOIN jadwals NATURAL JOIN users NATURAL JOIN kelass
                    WHERE TANGGAL_JADWAL_HARIAN >= "' . $this->lastSunday() . '" AND DAYOFWEEK(TANGGAL_JADWAL_HARIAN) = "' . $i + 1 . '" AND SESI_JADWAL = "' . $j . '" AND IS_DELETED_JADWAL_HARIAN IS NULL AND IS_DELETED_JADWAL IS NULL;'
                    )->getResultArray();
                for($k = 0; $k < count($data[$i][$j]); $k++) {
                    if($data[$i][$j][$k]['JAD_ID_JADWAL'] != null) {
                        $data[$i][$j][$k]['NAMA_INSTRUKTUR_SEBELUMNYA'] = $this->db->query('SELECT NAMA_USER FROM users NATURAL JOIN jadwals WHERE ID_JADWAL = "' . $data[$i][$j][$k]['JAD_ID_JADWAL'] . '";')->getResultArray()[0]['NAMA_USER'];
                    }
                    $data[$i][$j][$k]['SISA_KUOTA'] = $this->db->query('SELECT 10-COUNT(ID_BOOKING_KELAS) FROM booking_kelass WHERE ID_JADWAL = "'. $data[$i][$j][$k]['ID_JADWAL'] .'";')->getResultArray()[0]['10-COUNT(ID_BOOKING_KELAS)'];
                }
            }
        }
        return $this->respond($data, 200);
    }
    public function postJadikanLibur()
    {
        $data = $this->request->getJSON();
        $this->db->query(
            'UPDATE jadwal_harians JH INNER JOIN jadwals J ON JH.ID_JADWAL = J.ID_JADWAL INNER JOIN presensi_instrukturs PI ON J.ID_JADWAL = PI.ID_JADWAL
            SET JH.IS_LIBUR_JADWAL_HARIAN = 1,
                PI.STATUS_PRESENSI_INSTRUKTUR = 4,
                PI.PEG_ID_USER = "' . $data->PEG_ID_USER . '",
                PI.ID_PEGAWAI = "' . $data->ID_PEGAWAI . '"
            WHERE JH.ID_JADWAL_HARIAN = "' . $data->ID_JADWAL_HARIAN . '" AND J.ID_JADWAL = "' . $data->ID_JADWAL . '";'
        );
        return $this->respond(null, 200);
    }
    public function getGenerate()
    {
        for($i = 0; $i < 7; $i++) {
            $data = $this->db->query(
                'SELECT * FROM jadwal_pakets NATURAL JOIN jadwals NATURAL JOIN kelass NATURAL JOIN users WHERE IS_DELETED_JADWAL_PAKET IS NULL AND IS_DELETED_JADWAL IS NULL AND HARI_JADWAL_PAKET = "' . $i . '";'
                )->getResultArray();
            foreach ($data as $schedule) {
                $ID_JADWAL = base64_encode(random_bytes(24));
                $ID_JADWAL_HARIAN = base64_encode(random_bytes(24));
                $ID_PRESENSI_INSTRUKTUR = base64_encode(random_bytes(24));
                $this->db->transStart();
                $this->db->query(
                    'INSERT INTO jadwals (
                        ID_JADWAL,
                        ID_KELAS,
                        ID_USER,
                        ID_INSTRUKTUR,
                        SESI_JADWAL)
                    VALUES ("'
                        . $ID_JADWAL . '", "'
                        . $schedule['ID_KELAS'] . '", "'
                        . $schedule['ID_USER'] . '", "'
                        . $schedule['ID_INSTRUKTUR'] . '", "'
                        . $schedule['SESI_JADWAL']
                        . '");');
                $this->db->query(
                    'INSERT INTO jadwal_harians (
                        ID_JADWAL,
                        ID_JADWAL_HARIAN,
                        TANGGAL_JADWAL_HARIAN)
                    VALUES ("'
                        . $ID_JADWAL . '", "'
                        . $ID_JADWAL_HARIAN . '", "'
                        . date('Y-m-d',strtotime( $this->lastSunday() . ' + ' . $i . ' days'))
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
                $this->db->transComplete();
            }
        }
        return $this->respond(null, 200);
    }
}
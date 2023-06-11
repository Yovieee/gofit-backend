<?php

namespace App\Controllers;

class Laporan extends BaseController
{
    public function postLaporanPendapatan()
    {
        $data = $this->request->getJSON();
        $laporan = [];
        for($i = 1; $i <= 12; $i++) {
            array_push($laporan, [
                'BULAN' => [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'Desember',
                ][$i-1],
                'DEPOSIT_UANG' => $this->db->query(
                    'SELECT SUM(JUMLAH_DEPOSIT_UANG) FROM deposit_uangs WHERE MONTH(TANGGAL_DEPOSIT_UANG) = "' . $i . '" AND YEAR(TANGGAL_DEPOSIT_UANG) = "' . $data->TAHUN . '" AND IS_DELETED_DEPOSIT_UANG IS NULL;'
                )->getResultArray()[0]['SUM(JUMLAH_DEPOSIT_UANG)'] ?? 0,
                'AKTIVASI' => 3000000 * $this->db->query(
                    'SELECT COUNT(ID_MEMBERSHIP) FROM memberships WHERE MONTH(TANGGAL_AKTIVASI_MEMBERSHIP) = "' . $i . '" AND YEAR(TANGGAL_AKTIVASI_MEMBERSHIP) = "' . $data->TAHUN . '" AND IS_DELETED_MEMBERSHIP IS NULL;'
                )->getResultArray()[0]['COUNT(ID_MEMBERSHIP)']]);
        }
        return $this->respond($laporan, 200);
    }
    public function postLaporanAktivitasKelas()
    {
        $BULAN = sprintf("%02d",$this->request->getJSON()->BULAN);
        $TAHUN = $this->request->getJSON()->TAHUN;
        $BULAN_BATAS = $BULAN == 12 ? '01' : sprintf("%02d",$BULAN+1);
        $TAHUN_BATAS = $BULAN == 12 ? $TAHUN+1 : $TAHUN;
        $laporan = $this->db->query(
            'SELECT NAMA_KELAS, ID_KELAS, NAMA_USER, ID_USER FROM kelass JOIN instrukturs NATURAL JOIN users;'
        )->getResultArray();
        $laporanResult = [];
        for($i = 0; $i < count($laporan); $i++) {
            $laporan[$i]['JUMLAH_PESERTA'] = $this->db->query(
                'SELECT COUNT(BK.ID_BOOKING_KELAS) FROM booking_kelass BK JOIN jadwals J ON BK.ID_JADWAL = J.ID_JADWAL JOIN jadwal_harians JH ON J.ID_JADWAL = JH.ID_JADWAL JOIN presensi_kelass PK ON BK.NO_STRUK_PRESENSI_KELAS = PK.NO_STRUK_PRESENSI_KELAS WHERE J.ID_KELAS = "' . $laporan[$i]['ID_KELAS'] . '" AND J.ID_USER = "' . $laporan[$i]['ID_USER'] . '" AND PK.STATUS_PRESENSI_KELAS = "1" AND JH.TANGGAL_JADWAL_HARIAN >= "' . $TAHUN . '-' . $BULAN . '-01" AND JH.TANGGAL_JADWAL_HARIAN < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND BK.IS_DELETED_BOOKING_KELAS IS NULL;'
            )->getResultArray()[0]['COUNT(BK.ID_BOOKING_KELAS)'];
            $laporan[$i]['JUMLAH_LIBUR'] = $this->db->query(
                'SELECT COUNT(JH.ID_JADWAL_HARIAN) FROM jadwal_harians JH JOIN jadwals J ON J.ID_JADWAL = JH.ID_JADWAL WHERE J.ID_KELAS = "' . $laporan[$i]['ID_KELAS'] . '" AND J.ID_USER = "' . $laporan[$i]['ID_USER'] . '" AND JH.IS_LIBUR_JADWAL_HARIAN = "1" AND JH.TANGGAL_JADWAL_HARIAN >= "' . $TAHUN . '-' . $BULAN . '-01" AND JH.TANGGAL_JADWAL_HARIAN < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND JH.IS_DELETED_JADWAL_HARIAN IS NULL;'
            )->getResultArray()[0]['COUNT(JH.ID_JADWAL_HARIAN)'];
            if($laporan[$i]['JUMLAH_PESERTA'] != '0' || $laporan[$i]['JUMLAH_LIBUR'] != '0') {
                array_push($laporanResult, $laporan[$i]);
            }
        }
        if(count($laporanResult) > 0) return $this->respond($laporanResult, 200);
        else return $this->respond([['NAMA_KELAS' => 'No data', 'NAMA_USER' => 'No data', 'JUMLAH_PESERTA' => 'No data', 'JUMLAH_LIBUR' => 'No data']], 200);
    }
    public function postLaporanAktivitasGym()
    {
        $BULAN = sprintf("%02d",$this->request->getJSON()->BULAN);
        $TAHUN = $this->request->getJSON()->TAHUN;
        $laporan = $this->db->query(
            'SELECT COUNT(ID_BOOKING_GYM) AS JUMLAH_MEMBER, TANGGAL_BOOKING_GYM FROM booking_gyms GROUP BY TANGGAL_BOOKING_GYM;'
        )->getResultArray();
        return $this->respond($laporan, 200);
    }
    public function postLaporanKinerjaInstruktur()
    {
        $BULAN = sprintf("%02d",$this->request->getJSON()->BULAN);
        $TAHUN = $this->request->getJSON()->TAHUN;
        $BULAN_BATAS = $BULAN == 12 ? '01' : sprintf("%02d",$BULAN+1);
        $TAHUN_BATAS = $BULAN == 12 ? $TAHUN+1 : $TAHUN;
        $laporan = $this->db->query(
            'SELECT * FROM users NATURAL JOIN instrukturs;'
        )->getResultArray();
        for($i = 0; $i < count($laporan); $i++) {
            $laporan[$i]['JUMLAH_HADIR'] = $this->db->query(
                'SELECT COUNT(ID_PRESENSI_INSTRUKTUR) FROM presensi_instrukturs NATURAL JOIN jadwals NATURAL JOIN jadwal_harians WHERE ID_USER = "' . $laporan[$i]['ID_USER'] . '" AND (STATUS_PRESENSI_INSTRUKTUR = "1" OR STATUS_PRESENSI_INSTRUKTUR = "2") AND TANGGAL_JADWAL_HARIAN >= "' . $TAHUN . '-' . $BULAN . '-01" AND TANGGAL_JADWAL_HARIAN < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
            )->getResultArray()[0]['COUNT(ID_PRESENSI_INSTRUKTUR)'];
            $laporan[$i]['JUMLAH_LIBUR'] = $this->db->query(
                'SELECT COUNT(ID_PRESENSI_INSTRUKTUR) FROM presensi_instrukturs NATURAL JOIN jadwals NATURAL JOIN jadwal_harians WHERE ID_USER = "' . $laporan[$i]['ID_USER'] . '" AND STATUS_PRESENSI_INSTRUKTUR = "4" AND TANGGAL_JADWAL_HARIAN >= "' . $TAHUN . '-' . $BULAN . '-01" AND TANGGAL_JADWAL_HARIAN < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
            )->getResultArray()[0]['COUNT(ID_PRESENSI_INSTRUKTUR)'];
            $laporan[$i]['WAKTU_TERLAMBAT'] = $this->db->query(
                'SELECT SUM(DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR) FROM presensi_instrukturs NATURAL JOIN jadwals NATURAL JOIN jadwal_harians WHERE ID_USER = "' . $laporan[$i]['ID_USER'] . '" AND TANGGAL_JADWAL_HARIAN >= "' . $TAHUN . '-' . $BULAN . '-01" AND TANGGAL_JADWAL_HARIAN < "' . $TAHUN_BATAS . '-' . $BULAN_BATAS . '-01" AND IS_DELETED_PRESENSI_INSTRUKTUR IS NULL;'
            )->getResultArray()[0]['SUM(DETIK_KETERLAMBATAN_PRESENSI_INSTRUKTUR)'] ?? 0;
        }
        return $this->respond($laporan, 200);
    }
}
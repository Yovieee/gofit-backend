<?php

namespace App\Controllers;
use \Firebase\JWT\JWT;

class Autentikasi extends BaseController
{
    public function postLogin()
    {
        $data = $this->request->getJSON();
        $user = $this->db->query(
            'SELECT DISTINCT U.ID_USER, I.ID_INSTRUKTUR, P.ID_PEGAWAI, M.ID_MEMBER, P.JABATAN_PEGAWAI, U.NAMA_USER, U.EMAIL_USER, U.PASSWORD_USER, U.FOTO_USER, U.TANGGAL_LAHIR_USER
            FROM users U LEFT OUTER JOIN instrukturs I ON U.ID_USER = I.ID_USER LEFT OUTER JOIN pegawais P ON U.ID_USER = P.ID_USER LEFT OUTER JOIN members M ON U.ID_USER = M.ID_USER
            WHERE IS_DELETED_USER IS NULL AND EMAIL_USER="' . $data->EMAIL . '";'
            )->getResultArray();
        if(count($user) == 0) return $this->failNotFound();
        else if(password_verify($data->PASSWORD, $user[0]['PASSWORD_USER']))
        {
            $payload = [
                'ID_USER' => $user[0]['ID_USER'],
                'ID_INSTRUKTUR' => $user[0]['ID_INSTRUKTUR'],
                'ID_PEGAWAI' => $user[0]['ID_PEGAWAI'],
                'ID_MEMBER' => $user[0]['ID_MEMBER'],
                'JABATAN_PEGAWAI' => $user[0]['JABATAN_PEGAWAI']
            ];
            $user[0]['TOKEN'] = JWT::encode($payload, JWT_SECRET, 'HS256');
            return $this->respond($user[0], 200)->setCookie('token', JWT::encode($payload, JWT_SECRET, 'HS256'), '86400');
        }
        else
        {
            return $this->failUnauthorized();
        }
    }
    
    public function getLogout()
    {
        return $this->respond(null, 200)->deleteCookie('token');
    }

    public function postChangePassword()
    {
        $data = $this->request->getJSON();
        $user = $this->db->query(
            'SELECT DISTINCT U.ID_USER, I.ID_INSTRUKTUR, P.ID_PEGAWAI, M.ID_MEMBER, P.JABATAN_PEGAWAI, U.NAMA_USER, U.EMAIL_USER, U.PASSWORD_USER, U.FOTO_USER, U.TANGGAL_LAHIR_USER
            FROM users U LEFT OUTER JOIN instrukturs I ON U.ID_USER = I.ID_USER LEFT OUTER JOIN pegawais P ON U.ID_USER = P.ID_USER LEFT OUTER JOIN members M ON U.ID_USER = M.ID_USER
            WHERE U.ID_USER="' . $data->ID_USER . '";'
            )->getResultArray();
        if(password_verify($data->PASSWORD_LAMA, $user[0]['PASSWORD_USER']))
        {
            $this->db->query(
                'UPDATE users
                SET PASSWORD_USER = "' . password_hash($data->PASSWORD_USER, PASSWORD_BCRYPT) . '"
                WHERE ID_USER = "' . $data->ID_USER . '";'
            );
            return $this->respond($user[0], 200);
        }
        else
        {
            return $this->failUnauthorized();
        }
    }
    public function postRecoverPassword() {
        $data = $this->request->getJSON();
        $dateOfBirth = $this->db->query(
            'SELECT DATE_FORMAT(TANGGAL_LAHIR_USER, "%Y-%m-%d")
            FROM users
            WHERE ID_USER="' . $data->ID_USER . '";'
            )->getResultArray()[0]['DATE_FORMAT(TANGGAL_LAHIR_USER, "%Y-%m-%d")'];
        $this->db->query(
            'UPDATE users
            SET PASSWORD_USER = "' . password_hash($dateOfBirth, PASSWORD_BCRYPT) . '"
            WHERE ID_USER = "' . $data->ID_USER . '";'
        );
        $user = $this->db->query(
            'SELECT DISTINCT U.ID_USER, I.ID_INSTRUKTUR, P.ID_PEGAWAI, M.ID_MEMBER, P.JABATAN_PEGAWAI, U.NAMA_USER, U.EMAIL_USER, U.PASSWORD_USER, U.FOTO_USER, U.TANGGAL_LAHIR_USER
            FROM users U LEFT OUTER JOIN instrukturs I ON U.ID_USER = I.ID_USER LEFT OUTER JOIN pegawais P ON U.ID_USER = P.ID_USER LEFT OUTER JOIN members M ON U.ID_USER = M.ID_USER
            WHERE IS_DELETED_USER IS NULL AND U.ID_USER="' . $data->ID_USER . '";'
            )->getResultArray();
        return $this->respond($user[0], 200);
    }
}
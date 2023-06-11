<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // if (!$request->is('options') & !$request->is('get'))
        // { 
        //     $cookie = $request->getCookie();
        //     if (isset($cookie['token'])) {
        //         JWT::decode($cookie['token'], new Key(JWT_SECRET, 'HS256'));
        //     }
        //     else
        //     {
        //         JWT::decode($request->getJSON()->TOKEN, new Key(JWT_SECRET, 'HS256'));
        //     }
        // }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Code to execute after the controller method is called
    }
}

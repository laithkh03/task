<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\App;
use CodeIgniter\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface {

    private $secret_key;

    public function __construct() {
        $config = new App();
        $this->secret_key = $config->JWTSecretKey;
    }

    public function before(RequestInterface $request, $arguments = null) {
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            return Services::response()->setStatusCode(401, 'Unauthorized');
        }
    
        $token = str_replace('Bearer ', '', $authHeader->getValue());
      
        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->secret_key, 'HS256') 
            );
        } catch (\Exception $e) {
            return Services::response()->setStatusCode(401, 'Unauthorized');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {
        
    }
}

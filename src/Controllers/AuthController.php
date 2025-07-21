<?php

namespace App\Controllers;

use App\Services\AuthService;
use Exception;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validação dos dados
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Os campos name, email e password são obrigatórios.']);
            return;
        }
        
        try {
            $result = $this->authService->register($data['name'], $data['email'], $data['password']);
            http_response_code(201);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
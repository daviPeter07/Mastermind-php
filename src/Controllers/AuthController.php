<?php

namespace App\Controllers;

use App\Services\AuthService;
use Exception;

class AuthController
{
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function register() {
        //$data usado pra interpretação de arquivo json tanto para receber e retornar valores
        $data = json_decode(file_get_contents('php://input'), true);

        // Validação dos dados, caso nao haja os dados indicados retorna um http 400
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Os campos name, email e password são obrigatórios.']);
            return;
        }

        // Validação adicional para o nome
        $name = trim($data['name']);
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'O nome não pode estar vazio.']);
            return;
        }

        // Validação de comprimento do nome
        if (strlen($name) < 2 || strlen($name) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'O nome deve ter entre 2 e 255 caracteres.']);
            return;
        }
        
        try {
            //controller valida os dados e cria o objeto enviando pra logica do service, se estiver tudo ok : 201
            $result = $this->authService->register($name, $data['email'], $data['password']);
            http_response_code(201);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['Error' => $e->getMessage()]);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data["email"]) || empty($data["password"])) {
            http_response_code(400);
            echo json_encode(["error" => "Email ou senha são obrigatórios"]);
            return;
        }

        try {
            $result = $this->authService->login($data["email"], $data["password"]);
            http_response_code(201);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["Error"=> $e->getMessage()]);
        }
    }
}
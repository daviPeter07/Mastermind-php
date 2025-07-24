<?php

namespace App\Controllers;

use App\Services\JwtService;
use App\Services\UserService;
use Exception;

class UserController {

  private UserService $userService;
  private JwtService $jwtService;
  public function __construct() {
    $this->userService = new UserService();
    $this->jwtService = new JwtService();
  }

  public function index() {
    $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? null;

      if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(403);
        echo json_encode(["error" => "Token inválido"]);
        return;
      }

      $token = $matches[1];
      $payload = $this->jwtService->verifyToken($token);

      if (!$payload) {
        http_response_code(401);
        echo json_encode(["error"=> "Token inválido ou expirado"]);
        return;
      }

      if ($payload->role !== 'ADMIN') {
              http_response_code(403);
              echo json_encode(['error' => 'Acesso negado. Permissões de administrador necessárias.']);
              return;
      }

      $users = $this->userService->getUsers();
      http_response_code(200);
      echo json_encode($users);
  }

  /**
     * Busca e retorna um usuário específico pelo ID.
     * @param string $id
     */

  public function show(string $id) {
    $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? null;

      if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido"]);
        return;
      }

      $token = $matches[1];
      $payload = $this->jwtService->verifyToken($token);

      if (!$payload) {
        http_response_code(401);
        echo json_encode(["error"=> "Token inválido ou expirado"]);
        return;
      }

      if ($payload->role !== 'ADMIN') {
              http_response_code(403);
              echo json_encode(['error' => 'Acesso negado. Permissões de administrador necessárias.']);
              return;
      }

      $user = $this->userService->getUserById( $id);

      if ($user) {
        http_response_code(200);
        echo json_encode($user);
      } else {
        http_response_code(404);
        echo json_encode(['error'=> 'Usuário não encontrado']);
      }
  }

  /**
     * Lida com a requisição de ATUALIZAÇÃO de um usuário.
     * @param string $id
     */
  public function update(string $id) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
          http_response_code(401);
          echo json_encode(['error'=> 'Token inválido']);
          return; 
        }
        $payload = $this->jwtService->verifyToken($matches[1]);

        if (!$payload || $payload->role !== 'ADMIN') { 
          http_response_code(403);
          echo json_encode(['error'=> 'Acesso negado. Permissões de administrador necessárias.']);
          return; 
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $updatedUser = $this->userService->updateUser($id, $data);

        if ($updatedUser) {
            http_response_code(200);
            echo json_encode($updatedUser);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Usuário não encontrado.']);
        }
  }

  /**
     * Lida com a requisição de DELEÇÃO de um usuário.
     * @param string $id
     */
  public function delete(string $id) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
          http_response_code(401);
          echo json_encode(['error'=> 'Token inválido']);
           return; }
        $payload = $this->jwtService->verifyToken($matches[1]);
        if (!$payload || $payload->role !== 'ADMIN') {
           http_response_code(403);
           echo json_encode(['error'=> 'Acesso negado. Permissões de administrador necessárias.']);
           return; }

        $this->userService->deleteUser($id);
        http_response_code(204);
        echo json_encode(['error'=> 'Usuário deletado com sucesso']);
  }
  }

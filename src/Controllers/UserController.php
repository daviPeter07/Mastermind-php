<?php

namespace App\Controllers;

use App\Services\JwtService;
use App\Services\UserService;
use stdClass;

class UserController
{

  private UserService $userService;
  private JwtService $jwtService;
  public function __construct()
  {
    $this->userService = new UserService();
    $this->jwtService = new JwtService();
  }

  /**
   * Função privada de segurança. Verifica autenticação e autorização de ADMIN.
   * Reutilizada por todos os métodos que precisam de proteção.
   * @return stdClass|null O payload do token se for válido, ou termina a requisição.
   */

  private function authorizeAdmin(): ?stdClass
  {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      http_response_code(401);
      echo json_encode(['error' => 'Token de autenticação não fornecido.']);
      exit; // Usamos exit para parar a execução completamente
    }

    $token = $matches[1];
    $payload = $this->jwtService->verifyToken($token);

    if (!$payload) {
      http_response_code(401);
      echo json_encode(['error' => 'Token inválido ou expirado.']);
      exit;
    }

    if ($payload->role !== 'ADMIN') {
      http_response_code(403);
      echo json_encode(['error' => 'Acesso negado. Permissões de administrador necessárias.']);
      exit;
    }

    return $payload;
  }
  public function index()
  {
    $this->authorizeAdmin();

    $users = $this->userService->getUsers();
    http_response_code(200);
    echo json_encode($users);
  }

  public function show(string $id)
  {
    $this->authorizeAdmin();

    $user = $this->userService->getUserById($id);

    if ($user) {
      http_response_code(200);
      echo json_encode($user);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'Usuário não encontrado']);
    }
  }

  public function update(string $id)
  {
    $this->authorizeAdmin();

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

  public function delete(string $id)
  {
    $this->authorizeAdmin();

    $this->userService->deleteUser($id);
    http_response_code(204);
  }
}

<?php

namespace App\Controllers;

use App\Services\TaskService;
use App\Services\JwtService;
use Exception;

class TaskController
{
  private TaskService $taskService;
  private JwtService $jwtService;

  public function __construct()
  {
    $this->taskService = new TaskService();
    $this->jwtService = new JwtService();
  }

  /**
   * Função privada de segurança.
   * Verifica o token e retorna o ID do usuário (subject).
   * @return string - O UUID do usuário logado.
   */
  private function getAuthenticatedUserId(): string
  {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      http_response_code(401);
      echo json_encode(['error' => 'Token de autenticação não fornecido.']);
      exit;
    }

    $token = $matches[1];
    $payload = $this->jwtService->verifyToken($token);
    if (!$payload) {
      http_response_code(401);
      echo json_encode(['error' => 'Token inválido ou expirado.']);
      exit;
    }

    return $payload->sub;
  }

  public function create()
  {
    $userId = $this->getAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['content']) || empty($data['category_id'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Os campos content e category_id são obrigatórios.']);
      return;
    }

    try {
      $task = $this->taskService->create($data, $userId);
      http_response_code(201);
      echo json_encode($task);
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function index()
  {
    $userId = $this->getAuthenticatedUserId();
    $tasks = $this->taskService->findByUser($userId);

    http_response_code(200);
    echo json_encode($tasks);
  }

  public function show(string $id)
  {
    $userId = $this->getAuthenticatedUserId();

    $task = $this->taskService->findById((int)$id, $userId);

    if ($task) {
      http_response_code(200);
      echo json_encode($task);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'Task não encontrada ou não pertence a você.']);
    }
  }

  public function update(string $id)
  {
    $userId = $this->getAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);

    try {
      $updatedTask = $this->taskService->update((int)$id, $data, $userId);

      if ($updatedTask) {
        http_response_code(200);
        echo json_encode($updatedTask);
      } else {
        http_response_code(404);
        echo json_encode(['error' => 'Task não encontrada ou não pertence a você.']);
      }
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function delete(string $id)
  {
    $userId = $this->getAuthenticatedUserId();

    $wasDeleted = $this->taskService->delete((int)$id, $userId);

    if ($wasDeleted) {
      http_response_code(204);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'Task não encontrada ou não pertence a você.']);
    }
  }

  public function findByCategory(string $categoryId)
  {
    $userId = $this->getAuthenticatedUserId();

    try {
      $tasks = $this->taskService->findByCategory((int)$categoryId, $userId);
      http_response_code(200);
      echo json_encode($tasks);
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function updateStatus(string $id)
  {
    $userId = $this->getAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['status'])) {
      http_response_code(400);
      echo json_encode(['error' => 'O campo status é obrigatório.']);
      return;
    }

    try {
      $updatedTask = $this->taskService->updateStatus((int)$id, $data['status'], $userId);

      if ($updatedTask) {
        http_response_code(200);
        echo json_encode($updatedTask);
      } else {
        http_response_code(404);
        echo json_encode(['error' => 'Task não encontrada ou não pertence a você.']);
      }
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }
}
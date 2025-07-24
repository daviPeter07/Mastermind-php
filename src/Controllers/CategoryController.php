<?php

namespace App\Controllers;

use App\Services\CategoryService;
use App\Services\JwtService;
use Exception;

class CategoryController
{
  private CategoryService $categoryService;
  private JwtService $jwtService;

  public function __construct()
  {
    $this->categoryService = new CategoryService();
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

    if (empty($data['name']) || empty($data['type'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Os campos name e type são obrigatórios.']);
      return;
    }

    try {
      $category = $this->categoryService->create($data, $userId);
      http_response_code(201);
      echo json_encode($category);
    } catch (Exception $e) {
      http_response_code(409);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function index()
  {
    //pega o id do user seguindo regra de negocio e retorna categoria do proprio user
    $userId = $this->getAuthenticatedUserId();
    $categories = $this->categoryService->findByUser($userId);

    http_response_code(200);
    echo json_encode($categories);
  }

  public function update(int $id)
  {
    $userId = $this->getAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);

    try {
      $updatedCategory = $this->categoryService->update($id, $data, $userId);

      if ($updatedCategory) {
        http_response_code(200);
        echo json_encode($updatedCategory);
      } else {
        http_response_code(404);
        echo json_encode(['error' => 'Categoria não encontrada ou não pertence a você.']);
      }
    } catch (Exception $e) {
      http_response_code(400);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function delete(int $id)
  {
    $userId = $this->getAuthenticatedUserId();

    $wasDeleted = $this->categoryService->delete($id, $userId);

    if ($wasDeleted) {
      http_response_code(204);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'Categoria não encontrada ou não pertence a você.']);
    }
  }
}

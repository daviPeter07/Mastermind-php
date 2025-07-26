<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class TaskService {
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getConnection();
  }

  public function create(array $data, string $userId): array
  {
    // Verifica se a categoria pertence ao usuário
    $stmt = $this->db->prepare(
      "SELECT id FROM categories WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$data['category_id'], $userId]);
    if (!$stmt->fetch()) {
      throw new Exception("Categoria não encontrada ou não pertence a você.");
    }

    $stmt = $this->db->prepare(
      "INSERT INTO tasks (content, status, due_date, user_id, category_id) 
       VALUES (?, ?, ?, ?, ?) 
       RETURNING id, content, status, due_date, category_id, created_at"
    );
    
    $status = $data['status'] ?? 'PENDENTE';
    $dueDate = $data['due_date'] ?? null;
    
    $stmt->execute([
      $data['content'], 
      $status, 
      $dueDate, 
      $userId, 
      $data['category_id']
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findByUser(string $userId): array
  {
    $stmt = $this->db->prepare(
      "SELECT t.id, t.content, t.status, t.due_date, t.created_at, 
              c.id as category_id, c.name as category_name
       FROM tasks t 
       INNER JOIN categories c ON t.category_id = c.id 
       WHERE t.user_id = ? 
       ORDER BY t.created_at DESC"
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById(int $id, string $userId): ?array
  {
    $stmt = $this->db->prepare(
      "SELECT t.id, t.content, t.status, t.due_date, t.created_at, 
              c.id as category_id, c.name as category_name
       FROM tasks t 
       INNER JOIN categories c ON t.category_id = c.id 
       WHERE t.id = ? AND t.user_id = ?"
    );
    $stmt->execute([$id, $userId]);

    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    return $task ?: null;
  }

  public function findByCategory(int $categoryId, string $userId): array
  {
    // Verifica se a categoria pertence ao usuário
    $stmt = $this->db->prepare(
      "SELECT id FROM categories WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$categoryId, $userId]);
    if (!$stmt->fetch()) {
      throw new Exception("Categoria não encontrada ou não pertence a você.");
    }

    $stmt = $this->db->prepare(
      "SELECT t.id, t.content, t.status, t.due_date, t.created_at, 
              c.id as category_id, c.name as category_name
       FROM tasks t 
       INNER JOIN categories c ON t.category_id = c.id 
       WHERE t.category_id = ? AND t.user_id = ? 
       ORDER BY t.created_at DESC"
    );
    $stmt->execute([$categoryId, $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function update(int $id, array $data, string $userId): ?array
  {
    $fields = [];
    $values = [];

    // Validação dos campos permitidos
    $allowedFields = ['content', 'status', 'due_date', 'category_id'];
    
    foreach ($data as $field => $value) {
      if (in_array($field, $allowedFields)) {
        $fields[] = "$field = ?";
        $values[] = $value;
      }
    }

    if (empty($fields)) {
      throw new Exception("Nenhum dado fornecido para atualização.");
    }

    // Se estiver atualizando a categoria, verifica se ela pertence ao usuário
    if (isset($data['category_id'])) {
      $stmt = $this->db->prepare(
        "SELECT id FROM categories WHERE id = ? AND user_id = ?"
      );
      $stmt->execute([$data['category_id'], $userId]);
      if (!$stmt->fetch()) {
        throw new Exception("Categoria não encontrada ou não pertence a você.");
      }
    }

    $setClause = implode(', ', $fields);
    $values[] = $id;
    $values[] = $userId;

    $sql = "UPDATE tasks SET $setClause WHERE id = ? AND user_id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($values);

    // Retorna a task atualizada
    return $this->findById($id, $userId);
  }

  public function delete(int $id, string $userId): bool
  {
    $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    return $stmt->rowCount() > 0;
  }

  public function updateStatus(int $id, string $status, string $userId): ?array
  {
    if (!in_array($status, ['PENDENTE', 'CONCLUIDA'])) {
      throw new Exception("Status inválido. Use 'PENDENTE' ou 'CONCLUIDA'.");
    }

    $stmt = $this->db->prepare(
      "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$status, $id, $userId]);

    if ($stmt->rowCount() > 0) {
      return $this->findById($id, $userId);
    }

    return null;
  }
}
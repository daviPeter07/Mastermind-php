<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class CategoryService
{

  private PDO $db;

  public function __construct()
  {
    $this->db = Database::getConnection();
  }

  public function create(array $data, string $userId): array
  {
    //verifica se o usuário já tem uma categoria com o mesmo nome.
    $stmt = $this->db->prepare(
      "SELECT id FROM categories WHERE name = ? AND user_id = ?"
    );
    $stmt->execute([$data['name'], $userId]);
    if ($stmt->fetch()) {
      throw new Exception("Você já possui uma categoria com este nome.");
    }

    $stmt = $this->db->prepare(
      "INSERT INTO categories (name, type, user_id) VALUES (?, ?, ?) RETURNING id, name, type"
    );
    $stmt->execute([$data['name'], $data['type'], $userId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function findByUser(string $userId): array
  {
    //Busca apenas as categorias que pertencem ao usuário logado.
    $stmt = $this->db->prepare(
      "SELECT id, name, type, created_at FROM categories WHERE user_id = ? ORDER BY name ASC"
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById(int $id, string $userId): ?array
  {
    // A query busca pelo ID da categoria E pelo ID do usuário.
    $stmt = $this->db->prepare(
      "SELECT id, name, type, created_at FROM categories WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$id, $userId]);

    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    return $category ?: null;
  }

  public function update(int $id, array $data, string $userId): ?array
  {
    $fields = [];
    foreach (array_keys($data) as $field) {
      $fields[] = "$field = ?";
    }
    $setClause = implode(', ', $fields);

    if (empty($setClause)) {
      throw new Exception("Nenhum dado fornecido para atualização.");
    }

    //usuario só pode editar uma categoria que pertence a ele.
    $sql = "UPDATE categories SET $setClause WHERE id = ? AND user_id = ?";

    $stmt = $this->db->prepare($sql);

    $values = array_values($data);
    $values[] = $id;
    $values[] = $userId;
    $stmt->execute($values);

    $stmt = $this->db->prepare("SELECT id, name, type FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $updatedCategory = $stmt->fetch(PDO::FETCH_ASSOC);

    return $updatedCategory ?: null;
  }

  public function delete(int $id, string $userId): bool
  {
    $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    //Se for > 0, significa que a categoria foi encontrada e deletada.
    return $stmt->rowCount() > 0;
  }
}

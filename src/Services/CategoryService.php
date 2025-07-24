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

  /**
   * Cria uma nova categoria para um usuário específico.
   * @param array $data
   * @param string $userId
   * @return array
   * @throws Exception
   */
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

  /**
   * Busca todas as categorias de um usuário específico.
   * @param string $userId - O ID do usuário logado.
   * @return array - Uma lista de categorias.
   */
  public function findByUser(string $userId): array
  {
    //Busca apenas as categorias que pertencem ao usuário logado.
    $stmt = $this->db->prepare(
      "SELECT id, name, type, created_at FROM categories WHERE user_id = ? ORDER BY name ASC"
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

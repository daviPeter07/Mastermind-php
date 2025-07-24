<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class UserService {

  private PDO $db;
  public function __construct() {
    $this->db = Database::getConnection();
  }

  /**
     * Busca todos os usuários no banco de dados.
     * @return array
     */

  public function getUsers(): array {
    $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getUserById(string $id): ?array {
    $stmt = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
  }

  /**
     * Atualiza os dados de um usuário.
     * @param string $id - ID do usuário a ser atualizado.
     * @param array $data - Dados para atualizar (ex: ['name' => 'Novo Nome']).
     * @return array|null - O usuário atualizado ou null se não for encontrado.
     */
    public function updateUser(string $id, array $data): ?array
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "$field = ?";
        }
        $setClause = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE users SET $setClause WHERE id = ?");
        
        $values = array_values($data);
        $values[] = $id;
        $stmt->execute($values);

        return $this->getUserById($id);
    }
}
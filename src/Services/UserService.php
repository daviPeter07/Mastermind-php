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
}
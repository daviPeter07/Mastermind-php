<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AuthService {
  private PDO $db;

  public function __construct() {
    $this->db = Database::getConnection();
  }

    /**
     * Registra um novo usuário no banco de dados.
     * @param string $name
     * @param string $email
     * @param string $password
     * @return array
     * @throws Exception
     */

     public function register(string $name, string $email, string $password): array {
      $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
  
      //verificação de email existente
      if ($stmt->fetch()) {
        throw new Exception("Email já cadastrado");
      }
      //encripta a senha
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $this->db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
      $stmt->execute([$name, $email, $hashedPassword]);

      return ["message" => "Usuário cadastrado com sucesso"];
     }
}
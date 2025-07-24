<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AuthService {
  private PDO $db;
  private JwtService $jwtService;

  public function __construct() {
    $this->db = Database::getConnection();
    $this->jwtService = new JwtService();
  }

     public function register(string $name, string $email, string $password): array {
      $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
  
      //verificação de email existente
      if ($stmt->fetch()) {
        throw new Exception("Email já cadastrado");
      }
      //encripta a senha
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $this->db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?) RETURNING id, role");
      $stmt->execute([$name, $email, $hashedPassword]);
      
      $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$newUser) {
        throw new Exception("Falha ao criar usuário");
      }

      $token = $this->jwtService->generateToken($newUser["id"], $newUser["role"]);

      $response = [
        "user" => [
          "id" => $newUser["id"],
          "name" => $name,
          "email" => $email,
        ],
        "token" => $token
      ];

      return $response;
     }

     /**
     * Autentica um usuário e retorna um token JWT.
     * @param string $email
     * @param string $password
     * @return array
     * @throws Exception
     */

     public function login(string $email, string $password): array {
      $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        throw new Exception("Email ou senha inválidos");
      }
      
      $isPassword = password_verify($password, $user["password"]);
      if (!$isPassword) {
        throw new Exception("Email ou senha inválidos");
      }

      $token = $this->jwtService->generateToken($user["id"], $user["role"]);

      $response = [
        "token" => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
          ]
      ];
      return $response;
     }
}
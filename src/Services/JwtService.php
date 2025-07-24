<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use stdClass;

class JwtService {

  private string $secretkey;
  private string $algorithm = "HS256";
  public function __construct() {
    $this->secretkey = getenv("JWT_SECRET");
    //validação de token
    if (!$this->secretkey) {
      throw new Exception("Token inválido");
    }
  }

   /**
     * Gera um novo token JWT para um usuário.
     * @param string $userId - O ID do usuário (UUID).
     * @param string $userRole - A role do usuário (ex: 'USER').
     * @return string - O token JWT gerado.
     */

   public function generateToken(string $userId, string $userRole): string {
      $issueAt = time();
      $expirationTime = $issueAt + (7 * 24 * 60 * 60);

      $payload = [
        "iat" => $issueAt,
        "exp" => $expirationTime,
        "sub" => $userId,
        "role" => $userRole
      ];

      return JWT::encode($payload, $this->secretkey, $this->algorithm);
   }

   /**
     * Verifica um token JWT e retorna seu payload se for válido.
     * @param string $token
     * @return stdClass|null
     */

   public function verifyToken(string $token): ?stdClass {
      try {
        $decoded = JWT::decode($token, new Key($this->secretkey, $this->algorithm));
        return $decoded;
      } catch(Exception $e) {
        return null;
      }
   }
}
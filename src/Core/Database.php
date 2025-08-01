<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
  private static ?PDO $instance = null;

  public static function getConnection(): PDO
  {
    if (self::$instance === null) {
      $host = getenv("DB_HOST");
      $port = getenv("DB_PORT");
      $user = getenv("POSTGRES_USER");
      $pass = getenv("POSTGRES_PASSWORD");
      $db   = getenv("POSTGRES_DB");

      if (!$host || !$port || !$user || !$pass || !$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Variáveis de ambiente do banco ausentes ou incompletas.']);
        exit;
      }

      $dsn = "pgsql:host=$host;port=$port;dbname=$db";

      try {
        self::$instance = new PDO($dsn, $user, $pass, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
      } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
          'error' => 'Erro de conexão com o banco de dados.',
          'details' => $e->getMessage()
        ]);
        exit;
      }
    }

    return self::$instance;
  }
}

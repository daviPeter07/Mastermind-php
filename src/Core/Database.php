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
      $connectionUrl = getenv('DATABASE_URL');

      if ($connectionUrl === false) {
        http_response_code(500);
        echo json_encode(['error' => 'A variável de ambiente DATABASE_URL não foi definida.']);
        exit;
      }

      $dbParts = parse_url($connectionUrl);

      $host = $dbParts['host'];
      $port = $dbParts['port'];
      $user = $dbParts['user'];
      $pass = $dbParts['pass'];
      $db   = ltrim($dbParts['path'], '/');

      $dsn = "pgsql:host=$host;port=$port;dbname=$db";

      try {
        self::$instance = new PDO($dsn, $user, $pass, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
      } catch (PDOException $e) {
        http_response_code(500);
        error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
        echo json_encode(['error' => 'Erro de conexão com o banco de dados.']);
        exit;
      }
    }
    return self::$instance;
  }
}
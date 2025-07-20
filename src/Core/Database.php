<?php

namespace App\Core;

use PDO;
use PDOException;

class Database {
  private static ?PDO $instance = null;

  public static function getConnection(): PDO {
    if (self::$instance === null) {
      $host = "postgres";
      $port = getenv('DB_PORT') ?: 5432;
      $db = getenv('POSTGRES_DB');
      $user = getenv('POSTGRES_USER');
      $pass = getenv('POSTGRES_PASSWORD');

      //conecta ao banco (Data Source Name)
      $dsn = "pgsql:host=$host;port=$port;dbname=$db";

      try {
        self::$instance = new PDO($dsn, $user, $pass, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
      } catch (PDOException) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro de conexão com o banco de dados.']);
        exit;
      }
    }
    return self::$instance;
  }
}
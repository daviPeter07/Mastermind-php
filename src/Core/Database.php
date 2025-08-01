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

      $connectionUrl = getenv("DATABASE_URL");

      if ($connectionUrl === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro na variavel de URL do DB.']);
        exit;
      }

      $dbParts = parse_url($connectionUrl);

      $host = $dbParts['host'];
      $port = $dbParts['port'];
      $user = $dbParts['user'];
      $pass = $dbParts['pass'];
      $db   = ltrim($dbParts['path'], '/');

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

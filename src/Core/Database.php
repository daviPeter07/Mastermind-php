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
        die("Erro fatal: A variável de ambiente DATABASE_URL não foi definida.");
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
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
      }
    }
    return self::$instance;
  }
}
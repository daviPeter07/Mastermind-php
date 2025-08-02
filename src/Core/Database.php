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
        die("Erro fatal: Variáveis de ambiente do banco ausentes ou incompletas.");
      }

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
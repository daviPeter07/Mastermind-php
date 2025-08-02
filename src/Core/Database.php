<?php
// src/Core/Database.php

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

      if ($dbParts === false) {
        die("Erro fatal: A DATABASE_URL está mal formatada.");
      }

      $host = $dbParts['host'] ?? null;
      $port = $dbParts['port'] ?? 5432;
      $user = $dbParts['user'] ?? null;
      $pass = $dbParts['pass'] ?? null;
      $db   = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : null;

      if (!$host || !$user || !$pass || !$db) {
        die("Erro fatal: A DATABASE_URL está incompleta.");
      }

      $dsn = "pgsql:host=$host;port=$port;dbname=$db";

      try {
        self::$instance = new PDO($dsn, $user, $pass, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
      } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        die("Erro de conexão com o banco de dados.");
      }
    }

    return self::$instance;
  }
}

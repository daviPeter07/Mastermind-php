<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use App\Core\Database;

try {
    echo "🔄 Executando migração do banco...\n";
    
    $pdo = Database::getConnection();
    
    $migrationFile = __DIR__ . '/../Database/migration.sql';
    $sql = file_get_contents($migrationFile);
    
    $pdo->exec($sql);
    
    echo "✅ Migração executada com sucesso!\n";
    
} catch (\Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
} 
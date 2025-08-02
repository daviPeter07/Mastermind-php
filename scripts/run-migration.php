<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use App\Core\Database;

try {
    echo "🔄 Verificando se as tabelas já existem...\n";
    
    $pdo = Database::getConnection();
    
    // Verifica se a tabela users já existe
    $stmt = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
    )");
    $tableExists = $stmt->fetchColumn();
    
    if ($tableExists) {
        echo "✅ Tabelas já existem, pulando migração.\n";
        return;
    }
    
    echo "🔄 Executando migração do banco...\n";
    
    $migrationFile = __DIR__ . '/../Database/migration.sql';
    $sql = file_get_contents($migrationFile);
    
    $pdo->exec($sql);
    
    echo "✅ Migração executada com sucesso!\n";
    
} catch (\Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
} 
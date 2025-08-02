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
    
    // Verifica se todas as tabelas já existem
    $requiredTables = ['users', 'categories', 'tasks'];
    $existingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = '$table'
        )");
        $exists = $stmt->fetchColumn();
        $existingTables[$table] = $exists;
        
        echo $exists ? "✅ Tabela '$table' existe\n" : "❌ Tabela '$table' não existe\n";
    }
    
    // Se todas as tabelas existem, pula a migração
    if (array_sum($existingTables) === count($requiredTables)) {
        echo "✅ Todas as tabelas já existem, pulando migração.\n";
        return;
    }
    
    echo "🔄 Algumas tabelas estão faltando, executando migração...\n";
    
    echo "🔄 Executando migração do banco...\n";
    
    $migrationFile = __DIR__ . '/../Database/migration.sql';
    $sql = file_get_contents($migrationFile);
    
    $pdo->exec($sql);
    
    echo "✅ Migração executada com sucesso!\n";
    
} catch (\Exception $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
} 
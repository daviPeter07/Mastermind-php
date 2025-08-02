<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use App\Core\Database;

echo "⏳ Executando migrações...\n";

try {
    $db = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/../Database/migration.sql');
    if ($sql === false) {
        throw new \Exception("Não foi possível ler o arquivo de migração.");
    }

    $db->exec($sql);
    echo "✅ Migrações executadas com sucesso!\n";
} catch (\PDOException $e) {
    echo "❌ Erro ao executar migrações: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
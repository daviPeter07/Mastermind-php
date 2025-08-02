<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// ✅ Garante que as envs estão definidas via sistema
$requiredEnvVars = [
    'DATABASE_URL',
    'TELEGRAM_BOT_TOKEN',
    'MASTERMIND_API_TOKEN',
    'POSTGRES_DB',
    'POSTGRES_USER',
    'POSTGRES_PASSWORD',
    'DB_PORT',
    'JWT_SECRET',
];

foreach ($requiredEnvVars as $var) {
    if (!getenv($var)) {
        echo "❌ Variável de ambiente faltando: {$var}\n";
        exit(1);
    }
}

use App\Bot\Bot;

try {
    echo "🚀 Iniciando o bot...\n";
    $bot = new Bot();
    $bot->listen();

} catch (\Exception $e) {
    echo "Erro ao iniciar o bot: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

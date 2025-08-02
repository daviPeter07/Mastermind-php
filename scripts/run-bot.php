<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use App\Bot\Bot;

try {
    $bot = new Bot();
    echo "🚀 Iniciando o bot...\n";
    $bot->listen();
    
} catch (\Exception $e) {
    echo "Erro ao iniciar o bot: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
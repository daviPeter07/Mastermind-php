<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Bot\Bot;

try {
    // Cria uma nova instância do nosso bot...
    $bot = new Bot();
    // ...e o coloca para ouvir.
    $bot->listen();
    
} catch (\Exception $e) {
    echo "Erro ao iniciar o bot: " . $e->getMessage() . "\n";
}
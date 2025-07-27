<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Bot\Bot;

try {
    $bot = new Bot();
    $bot->listen();
    
} catch (\Exception $e) {
    echo "Erro ao iniciar o bot: " . $e->getMessage() . "\n";
}
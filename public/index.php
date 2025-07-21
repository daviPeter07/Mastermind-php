<?php

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Services\AuthService;

$requestUrl = $_SERVER['REQUEST_URL'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

echo "hello world";
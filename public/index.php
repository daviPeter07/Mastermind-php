<?php

require __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Controllers\AuthController;
use App\Core\Router;

header('Content-Type: application/json');

$router = new Router();

$router->post('/api/register', [AuthController::class, 'register']);

$router->dispatch();  
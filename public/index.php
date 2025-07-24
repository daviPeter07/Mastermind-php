<?php

//autoloader do compose
require __DIR__ . "/../vendor/autoload.php";

//lib pra ler as envs
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

//import de controller e reqs
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\UserController;

//res : json
header('Content-Type: application/json');

$router = new Router();
//rotas e requisições

//autentificação
$router->post('/api/register', [AuthController::class, 'register']);
$router->post('/api/login', [AuthController::class, 'login']);

//get
$router->get('/api/users', [UserController::class, 'index']);
$router->get('/api/users/{id}', [UserController::class, 'show']);

$router->put('/api/users/{id}', [UserController::class, 'update']);

$router->dispatch();  
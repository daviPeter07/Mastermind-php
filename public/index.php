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
use App\Controllers\CategoryController;
use App\Controllers\TaskController;

//res : json
header('Content-Type: application/json');

$router = new Router();
//rotas e requisições

//autentificação
$router->post('/api/register', [AuthController::class, 'register']);
$router->post('/api/login', [AuthController::class, 'login']);

//ADMIN
$router->get('/api/users', [UserController::class, 'index']);
$router->get('/api/users/{id}', [UserController::class, 'show']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'delete']);

//Categories
$router->post('/api/categories', [CategoryController::class, 'create']);
$router->get('/api/categories', [CategoryController::class, 'index']);
$router->get('/api/categories/{id}', [CategoryController::class, 'show']);
$router->put('/api/categories/{id}', [CategoryController::class, 'update']);
$router->delete('/api/categories/{id}', [CategoryController::class, 'delete']);

//Tasks
$router->post('/api/tasks', [TaskController::class, 'create']);
$router->get('/api/tasks', [TaskController::class, 'index']);
$router->get('/api/tasks/{id}', [TaskController::class, 'show']);
$router->put('/api/tasks/{id}', [TaskController::class, 'update']);
$router->delete('/api/tasks/{id}', [TaskController::class, 'delete']);
$router->get('/api/categories/{id}/tasks', [TaskController::class, 'findByCategory']);
$router->patch('/api/tasks/{id}/status', [TaskController::class, 'updateStatus']);


$router->dispatch();  
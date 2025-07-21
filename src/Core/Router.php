<?php

namespace App\Core;

class Router {
  private array $routes = [];

  public function post(string $uri, array $action) {
    $this->routes["POST"][$uri] = $action;
  }

  public function get(string $uri, array $action) {
    $this->routes["GET"][$uri] = $action;
  }

  public function dispatch(){
    $uri = $_SERVER["REQUEST_URI"];
    $method = $_SERVER["REQUEST_METHOD"];

    if (isset($this->routes[$method][$uri])) {
      [$controllerClass, $controllerMethod] = $this->routes[$method][$uri];
      // Cria um novo objeto do Controller (ex: new AuthController())
      $controller = new $controllerClass();
      // Chama o método correspondente (ex: $controller->register())
      $controller->$controllerMethod();
    } else {
      http_response_code(404);
      echo json_encode(["error" => "Endpoint não encontrado"]);
    }
  }
}
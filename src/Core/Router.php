<?php

namespace App\Core;

class Router {
  private array $routes = [];

  public function post(string $uri, array $action) {
    $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-fA-F0-9-]+)', $uri);
    $this->routes["POST"][$uri] = $action;
  }

  public function get(string $uri, array $action) {
    $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-fA-F0-9-]+)', $uri);
    $this->routes["GET"][$uri] = $action;
  }

  public function put(string $uri, array $action) {
        $uri = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-fA-F0-9-]+)', $uri);
        $this->routes['PUT'][$uri] = $action;
    }

  //verificação de existencia de rota, se nao existe = 404
  public function dispatch(){
    $uri = $_SERVER["REQUEST_URI"]; //api/register, api/login...
    $method = $_SERVER["REQUEST_METHOD"]; //post, get, delete...

    foreach ($this->routes[$method] as $route => $action) {
            $pattern = "#^" . $route . "$#";

            //Se a URI atual corresponder ao padrão da rota
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                [$controllerClass, $controllerMethod] = $action;
                
                $controller = new $controllerClass();
                // E chama o método do controller, passando os parâmetros da URL
                $controller->$controllerMethod(...$params);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Endpoint não encontrado']);
  }
}
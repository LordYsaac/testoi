<?php

namespace App\Core;

/**
 * Enrutador simple basado en expresiones regulares. Soporta parametros de
 * ruta con la sintaxis {nombre} y middleware por ruta (clases con metodo
 * handle(), o [Clase, argumento] cuando el middleware necesita un parametro,
 * como RoleMiddleware necesita saber que permiso exigir).
 */
class Router
{
    private array $routes = [];
    private bool $exigirCsrf;

    public function __construct(bool $exigirCsrf = true)
    {
        $this->exigirCsrf = $exigirCsrf;
    }

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', trim($path, '/'));
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => '#^' . $pattern . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = trim((string) parse_url($uri, PHP_URL_PATH), '/');

        // Si la app vive en un subdirectorio (ej. /optica/), quitarlo antes de comparar
        $basePath = trim(Url::base(), '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = trim(substr($uri, strlen($basePath)), '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);

                if ($method === 'POST' && $this->exigirCsrf) {
                    Csrf::verifyRequest();
                }

                foreach ($route['middleware'] as $mw) {
                    $this->ejecutarMiddleware($mw);
                }

                $this->llamarHandler($route['handler'], array_values($matches));
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }

    private function ejecutarMiddleware(mixed $mw): void
    {
        if (is_array($mw)) {
            [$clase, $parametro] = $mw;
            (new $clase($parametro))->handle();
        } else {
            (new $mw())->handle();
        }
    }

    private function llamarHandler(array|callable $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            $controller = new $controllerClass();
            $controller->$method(...$params);
            return;
        }
        $handler(...$params);
    }
}

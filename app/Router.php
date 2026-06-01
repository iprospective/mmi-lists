<?php
declare(strict_types=1);

namespace App;

use PDO;

// Routeur minimaliste : associe « MÉTHODE /chemin » à [Controller::class, 'méthode'].
final class Router
{
    /** @var array<string, array{0:class-string,1:string}> */
    private array $routes = [];

    public function add(array $methods, string $path, array $handler): void
    {
        foreach ($methods as $m) {
            $this->routes[strtoupper($m) . ' ' . $path] = $handler;
        }
    }

    public function get(string $path, array $handler): void  { $this->add(['GET'], $path, $handler); }
    public function post(string $path, array $handler): void { $this->add(['POST'], $path, $handler); }
    public function any(string $path, array $handler): void  { $this->add(['GET', 'POST'], $path, $handler); }

    public function dispatch(string $method, string $path, PDO $pdo): void
    {
        $handler = $this->routes[$method . ' ' . $path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            view('errors/404', ['pageTitle' => 'Page introuvable']);
            return;
        }

        [$class, $fn] = $handler;
        (new $class($pdo))->$fn();
    }
}

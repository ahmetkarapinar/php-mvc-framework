<?php

declare(strict_types=1);

namespace Framework;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function __construct()
    {
    }
    public function add(string $method, string $path, array $controller)
    {

        $path = $this->normalizePath($path);
        $regexPath = preg_replace('#{[^/]+}#', '([^/]+)', $path);
        $this->routes[] = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'middlewares' => [],
            'regexPath' => $regexPath

        ];
    }
    private function normalizePath(string $path): string
    {

        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('#[/]{2,}#', '/', $path);
        return $path;
    }
    public function dispatch(string $path, string $method, Container $container)
    {
        $path = $this->normalizePath($path);
        $method = strtoupper($_POST['_METHOD'] ?? $method);

        echo $path . $method . '<br>';
        foreach ($this->routes as $route) {
            if (!preg_match("#^{$route['regexPath']}$#", $path, $paramValues) || $route['method'] !== $method) {
                continue;
            }

            array_shift($paramValues);
            preg_match_all('#{([^/]+)}#', $route['path'], $paramKeys);
            $paramKeys = $paramKeys[1];
            $params = array_combine($paramKeys, $paramValues);
            //dd($paramKeys);
            [$class, $function] = $route['controller'];
            //dd($function);
            $controllerInstance = $container ? $container->resolve($class) : new $class;
            //$controllerInstance = $container->resolve($class);
            //dd($controllerInstance);


            $action = fn () => $controllerInstance->{$function}($params);
            $allMiddleware = [...$route['middlewares'], ...$this->middlewares];
            // Pre hook
            foreach ($allMiddleware as $middleware) {
                $middlewareInstance = $container ? $container->resolve($middleware) : new $middleware;
                $action = fn () => $middlewareInstance->process($action);
            }
            $action();
            return;
        }
    }
    public function addMiddleware(string $middleWare)
    {
        $this->middlewares[] = $middleWare;
    }
    public function addRouteMiddleware(string $middleWare)
    {
        $lastRouteKey = array_key_last($this->routes);
        $this->routes[$lastRouteKey]['middlewares'][] = $middleWare;
    }
}

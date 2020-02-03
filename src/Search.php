<?php namespace cklamm\Router;

class Search
{
    public $method;
    public $path;

    public $routes = [];
    public $parameters = [];
    public $middleware = [];

    public function __construct($method, $path)
    {
        $this->stop = false;
        $this->method = $method;
        $this->path = $path;
    }

    public function add(Route $route, array $params, array $mw): void
    {
        $method = $route->method;

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = $route;
            $this->parameters[$method] = $params;
            $this->middleware[$method] = $mw;
        }
    }
}

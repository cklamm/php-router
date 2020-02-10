<?php namespace cklamm\Router;

class Search
{
    public $method;
    public $path;

    public $routes = [];
    public $parameters = [];

    public function __construct($method, $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public function add(Route $route, array $params): void
    {
        $method = $route->method;

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = $route;
            $this->parameters[$method] = $params;
        }
    }
}

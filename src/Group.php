<?php namespace cklamm\Router;

class Group
{
    public $prefix;
    public $routes = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function add(Route ...$routes): void
    {
        foreach ($routes as $route) {
            $this->routes[] = $route;
        }
    }

    public function middleware(...$names): void
    {
        foreach (array_reverse($names) as $name) {
            foreach ($this->routes as $route) {
                array_unshift($route->middleware, $name);
            }
        }
    }
}

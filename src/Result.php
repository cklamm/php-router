<?php namespace cklamm\Router;

class Result
{
    public $code;
    public $method;
    public $path;
    public $route;
    public $name;
    public $handler;

    public $parameters = [];
    public $middleware = [];
    public $options = [];

    public function __construct(Search $search, array $global)
    {
        $this->code = 404;
        $this->method = $search->method;
        $this->path = $search->path;

        if (empty($search->routes)) return;

        $this->code = 405;
        $this->options = array_keys($search->routes);

        if (!isset($search->routes[$this->method])) return;
        $route = $search->routes[$this->method];

        $this->code = 200;
        $this->route = $route->route;
        $this->handler = $route->handler;
        $this->name = $route->name;

        $this->parameters = $search->parameters[$this->method];
        $this->middleware = $global;

        if (!empty($route->middleware)) {
            array_push($this->middleware, ...$route->middleware);
        }
    }
}

<?php namespace cklamm\Router;

class Node
{
    protected $routes = [];
    protected $middleware = [];
    protected $nodes = [];

    public function add($method, $route, $handler, $name = null): Route
    {
        if (isset($this->routes[$method])) {
            throw new \Exception('Route already exists: ' . $method . ' ' . $route);
        }

        $route = new Route($method, $route, $handler, $name);
        $this->routes[$method] = $route;

        return $route;
    }

    public function middleware(...$names): void
    {
        foreach ($names as $name) {
            $this->middleware[] = $name;
        }
    }

    public function build($parts): Node
    {
        if (empty($parts)) return $this;

        $part = array_shift($parts);
        $key = preg_match('#^[:?*]#', $part) ? $part[0] : $part;

        if ($part == ':') {
            throw new \Exception('Route placeholder must have a name.');
        }

        if (!isset($this->nodes[$key])) {
            $this->nodes[$key] = new Node();
        }

        return $this->nodes[$key]->build($parts);
    }

    public function search($parts, Search $search, $params = [], $mw = []): Search
    {
        if (!empty($this->middleware)) {
            array_push($mw, ...$this->middleware);
        }

        if (empty($parts)) {
            foreach ($this->routes as $route) {
                $search->add($route, $params, $mw);
            }

            return $search;
        }

        $part = array_shift($parts);

        if (isset($this->nodes[$part])) {
            $this->nodes[$part]->search($parts, $search, $params, $mw);
        }

        if (isset($this->nodes[':'])) {
            array_push($params, $part);
            $this->nodes[':']->search($parts, $search, $params, $mw);
        }

        return $search;
    }
}

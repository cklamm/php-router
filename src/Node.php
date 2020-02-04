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

    public function build($parts, $optional = false): Node
    {
        if (empty($parts)) return $this;

        $part = array_shift($parts);
        $key = preg_match('#^[:?*]#', $part) ? $part[0] : $part;
        if ($key == '?') $optional = true;

        if (in_array($part, [':', '?'])) {
            throw new \Exception('Route parameter must have a name.');
        }

        if ($optional && !in_array($key, ['?', '*'])) {
            throw new \Exception('An optional parameter may only be followed
            by optional and wildcard parameters.');
        }

        if ($key == '*' && !empty($parts)) {
            throw new \Exception('A wildcard must be the last route segment.');
        }

        if (!isset($this->nodes[$key])) {
            $this->nodes[$key] = new Node();
        }

        return $this->nodes[$key]->build($parts, $optional);
    }

    public function search($parts, Search $search, $params = [], $mw = []): Search
    {
        $remaining = $parts;
        $part = array_shift($remaining);

        if (!empty($this->middleware)) {
            array_push($mw, ...$this->middleware);
        }

        if (empty($parts)) {
            foreach ($this->routes as $route) {
                $search->add($route, $params, $mw);
            }
        }
        else {
            if (isset($this->nodes[$part])) {
                $this->nodes[$part]->search($remaining, $search, $params, $mw);
            }

            if (isset($this->nodes[':'])) {
                $p = array_merge($params, [$part]);
                $this->nodes[':']->search($remaining, $search, $p, $mw);
            }
        }

        if (isset($this->nodes['?'])) {
            $p = array_merge($params, [$part ?? null]);
            $this->nodes['?']->search($remaining, $search, $p, $mw);
        }

        if (isset($this->nodes['*'])) {
            if (!empty($parts)) array_push($params, ...$parts);
            $this->nodes['*']->search([], $search, $params, $mw);
        }

        return $search;
    }
}

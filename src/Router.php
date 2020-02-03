<?php namespace cklamm\Router;

use Closure;

class Router
{
    protected $tree;
    protected $group = '';
    protected $names = [];

    public function __construct()
    {
        $this->tree = new Node();
    }

    public function add($method, $route, $handler, $name = null): Route
    {
        $method = strtoupper($method);
        $route = $this->sanitize($this->group . $route);
        $parts = $this->split($route);

        $node = $this->tree->build($parts);
        $route = $node->add($method, $route, $handler, $name);
        if (isset($name)) $this->setName($name, $route);

        return $route;
    }

    public function get($route, $handler, $name = null): Route
    {
        return $this->add('GET', $route, $handler, $name);
    }

    public function post($route, $handler, $name = null): Route
    {
        return $this->add('POST', $route, $handler, $name);
    }

    public function put($route, $handler, $name = null): Route
    {
        return $this->add('PUT', $route, $handler, $name);
    }

    public function patch($route, $handler, $name = null): Route
    {
        return $this->add('PATCH', $route, $handler, $name);
    }

    public function delete($route, $handler, $name = null): Route
    {
        return $this->add('DELETE', $route, $handler, $name);
    }

    public function group($group, Closure $cb = null): Node
    {
        $group = $this->sanitize($this->group . $group);
        $parts = $this->split($group);

        if (isset($cb)) {
            $temp = $this->group;
            $this->group = $group . '/';
            Closure::bind($cb, $this)();
            $this->group = $temp;
        }

        return $this->tree->build($parts);
    }

    public function middleware(...$names): void
    {
        $this->tree->middleware(...$names);
    }

    public function dispatch($method, $path): Result
    {
        $method = strtoupper($method);
        $path = $this->sanitize($path);
        $parts = $this->split($path);

        $search = new Search($method, $path);
        $this->tree->search($parts, $search);
        $result = new Result($search);

        return $result;
    }

    public function path($route, $data = []): string
    {
        if (!isset($this->names[$route])) {
            throw new \Exception('Named route does not exist: ' . $route);
        }

        return $this->names[$route]->path($data);
    }

    protected function sanitize($path): string
    {
        return preg_replace('#/+#', '/', trim($path, '/'));
    }

    protected function split($path): array
    {
        return ($path === '') ? [] : explode('/', $path);
    }

    protected function setName($name, $route): void
    {
        if (isset($this->names[$name])) {
            throw new \Exception('Named route already exists: ' . $name);
        }

        $this->names[$name] = $route;
    }
}

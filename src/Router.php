<?php namespace cklamm\Router;

use cklamm\Router\Exceptions\PathGenerationException;
use cklamm\Router\Exceptions\InvalidRouteException;
use Closure;

class Router
{
    protected $tree;
    protected $group;
    protected $names = [];
    protected $middleware = [];

    public function __construct()
    {
        $this->tree = new Node();
        $this->group = new Group('');
    }

    public function add(string $method, string $route, $handler, string $name = null): Route
    {
        $method = strtoupper($method);
        $route = $this->sanitize($this->group->prefix . $route);
        $parts = $this->split($route);

        $node = $this->tree->build($parts);
        $route = $node->add($method, $route, $handler, $name);
        if (isset($name)) $this->setName($name, $route);
        $this->group->add($route);

        return $route;
    }

    public function get(string $route, $handler, string $name = null): Route
    {
        return $this->add('GET', $route, $handler, $name);
    }

    public function post(string $route, $handler, string $name = null): Route
    {
        return $this->add('POST', $route, $handler, $name);
    }

    public function put(string $route, $handler, string $name = null): Route
    {
        return $this->add('PUT', $route, $handler, $name);
    }

    public function patch(string $route, $handler, string $name = null): Route
    {
        return $this->add('PATCH', $route, $handler, $name);
    }

    public function delete(string $route, $handler, string $name = null): Route
    {
        return $this->add('DELETE', $route, $handler, $name);
    }

    public function group(string $prefix, Closure $cb): Group
    {
        $prefix = $this->sanitize($this->group->prefix . $prefix);
        $parts = $this->split($prefix);
        $group = new Group($prefix . '/');

        $temp = $this->group;
        $this->group = $group;
        Closure::bind($cb, $this)();
        $temp->add(...$this->group->routes);
        $this->group = $temp;

        $this->tree->build($parts);
        return $group;
    }

    public function middleware(...$names): void
    {
        foreach ($names as $name) {
            $this->middleware[] = $name;
        }
    }

    public function dispatch(string $method, string $path): Result
    {
        $method = strtoupper($method);
        $path = $this->sanitize($path);
        $parts = $this->split($path);

        $search = new Search($method, $path);
        $this->tree->search($parts, $search);
        $result = new Result($search, $this->middleware);

        return $result;
    }

    public function path(string $name, $data = []): string
    {
        if (!isset($this->names[$name])) {
            throw PathGenerationException::namedRouteUndefined($name);
        }

        return $this->names[$name]->path($data);
    }

    protected function sanitize(string $path): string
    {
        return preg_replace('#/+#', '/', trim($path, '/'));
    }

    protected function split(string $path): array
    {
        return ($path === '') ? [] : explode('/', $path);
    }

    protected function setName(string $name, Route $route): void
    {
        if (isset($this->names[$name])) {
            throw InvalidRouteException::namedRouteExists($name);
        }

        $this->names[$name] = $route;
    }
}

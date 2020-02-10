<?php namespace cklamm\Router;

use cklamm\Router\Exceptions\InvalidRouteException;

class Node
{
    protected $routes = [];
    protected $nodes = [];

    public function add($method, $route, $handler, $name = null): Route
    {
        if (isset($this->routes[$method])) {
            throw InvalidRouteException::routeExists($method, $route);
        }

        $route = new Route($method, $route, $handler, $name);
        $this->routes[$method] = $route;

        return $route;
    }

    public function build($parts, $optional = false): Node
    {
        if (empty($parts)) return $this;

        $part = array_shift($parts);
        $key = preg_match('#^[:?*]#', $part) ? $part[0] : $part;
        if ($key == '?') $optional = true;

        if (in_array($part, [':', '?'])) {
            throw InvalidRouteException::missingParameterName();
        }

        if ($optional && !in_array($key, ['?', '*'])) {
            throw InvalidRouteException::invalidOptionalParameter();
        }

        if ($key == '*' && !empty($parts)) {
            throw InvalidRouteException::invalidWildcardParameter();
        }

        if (!isset($this->nodes[$key])) {
            $this->nodes[$key] = new Node();
        }

        return $this->nodes[$key]->build($parts, $optional);
    }

    public function search($parts, Search $search, $params = []): Search
    {
        $remaining = $parts;
        $part = array_shift($remaining);

        if (empty($parts)) {
            foreach ($this->routes as $route) {
                $search->add($route, $params);
            }
        }
        else {
            if (isset($this->nodes[$part])) {
                $this->nodes[$part]->search($remaining, $search, $params);
            }

            if (isset($this->nodes[':'])) {
                $p = array_merge($params, [$part]);
                $this->nodes[':']->search($remaining, $search, $p);
            }
        }

        if (isset($this->nodes['?'])) {
            $p = array_merge($params, [$part ?? null]);
            $this->nodes['?']->search($remaining, $search, $p);
        }

        if (isset($this->nodes['*'])) {
            if (!empty($parts)) array_push($params, ...$parts);
            $this->nodes['*']->search([], $search, $params);
        }

        return $search;
    }
}

<?php namespace cklamm\Router;

class Route
{
    public $method;
    public $route;
    public $handler;
    public $name;

    public $middleware = [];

    public function __construct($method, $route, $handler, $name = null)
    {
        $this->method = $method;
        $this->route = $route;
        $this->handler = $handler;
        $this->name = $name;
    }

    public function middleware(...$names): void
    {
        foreach ($names as $name) {
            $this->middleware[] = $name;
        }
    }

    public function path($data = []): string
    {
        if (is_object($data)) $data = (array) $data;
        $assoc = count(array_filter(array_keys($data), 'is_string')) > 0;
        $parts = explode('/', $this->route);
        $i = 0;

        foreach ($parts as &$part) {
            if (!preg_match('#^[:?*]#', $part)) continue;
            $name = substr($part, 1);
            $key = $assoc ? $name : $i++;

            if ($part[0] == ':' && !isset($data[$key])) {
                throw new \Exception('No value given for route placeholder ' . $name);
            }

            if ($part[0] == '*') {
                if ($assoc && $key === '') {
                    throw new \Exception('Wildcard parameter must have a name.');
                }

                if ($assoc && isset($data[$key]) && !is_array($data[$key])) {
                    throw new \Exception('Value for route wildcard must be an array.');
                }

                if (isset($data[$key])) {
                    if ($assoc) $data[$key] = implode('/', $data[$key]);
                    else $data[$key] = implode('/', array_slice($data, $key));
                    if ($data[$key] === '') $data[$key] = null;
                }
            }

            $part = $data[$key] ?? null;
        }

        return implode('/', array_filter($parts, function ($val) {
            return isset($val);
        }));
    }
}

<?php namespace cklamm\Router;

use cklamm\Router\Exceptions\PathGenerationException;

class Route
{
    public $method;
    public $route;
    public $handler;
    public $name;

    public $middleware = [];

    public function __construct(string $method, string $route, $handler, string $name = null)
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
        $assoc = is_object($data);

        if (is_array($data)) {
            $assoc = count(array_filter(array_keys($data), 'is_string')) > 0;
        }

        $parts = explode('/', $this->route);
        $i = 0;

        foreach ($parts as &$part) {
            if (!preg_match('#^[:?*]#', $part)) continue;
            $name = substr($part, 1);
            $key = $assoc ? $name : $i++;

            $val = null;
            if (is_object($data) && isset($data->$key)) $val = $data->$key;
            if (is_array($data) && isset($data[$key])) $val = $data[$key];

            if ($part[0] == ':' && !isset($val)) {
                throw PathGenerationException::missingParameterValue($name);
            }

            if ($part[0] == '*') {
                if ($assoc && $key === '') {
                    throw PathGenerationException::missingWildcardName();
                }

                if ($assoc && isset($val) && !is_array($val)) {
                    throw PathGenerationException::invalidWildcardValue();
                }

                if (isset($val)) {
                    if ($assoc) $val = implode('/', $val);
                    else $val = implode('/', array_slice($data, $key));
                    if ($val === '') $val = null;
                }
            }

            $part = $val ?? null;
        }

        return implode('/', array_filter($parts, function ($val) {
            return isset($val);
        }));
    }
}

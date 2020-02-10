<?php namespace cklamm\Router\Exceptions;

use LogicException;

class InvalidRouteException extends LogicException
{
    public static function routeExists($method, $route)
    {
        $msg = 'Route already exists: ' . $method . ' ' . $route;
        return new static($msg);
    }

    public static function namedRouteExists($name)
    {
        $msg = 'Named route already exists: ' . $name;
        return new static($msg);
    }

    public static function missingParameterName()
    {
        $msg = 'Route parameter must have a name.';
        return new static($msg);
    }

    public static function invalidOptionalParameter()
    {
        $msg = 'An optional parameter may only be followed by optional and wildcard parameters.';
        return new static($msg);
    }

    public static function invalidWildcardParameter()
    {
        $msg = 'A wildcard must be the last route segment.';
        return new static($msg);
    }
}

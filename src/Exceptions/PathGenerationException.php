<?php namespace cklamm\Router\Exceptions;

use LogicException;

class PathGenerationException extends LogicException
{
    public static function namedRouteUndefined($name)
    {
        $msg = 'Named route does not exist: ' . $name;
        return new static($msg);
    }

    public static function missingParameterValue($name)
    {
        $msg = 'No value given for route parameter: ' . $name;
        return new static($msg);
    }

    public static function missingWildcardName()
    {
        $msg = 'Wildcard parameter must have a name.';
        return new static($msg);
    }

    public static function invalidWildcardValue()
    {
        $msg = 'Value for route wildcard must be an array.';
        return new static($msg);
    }
}

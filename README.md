# PHP Router

There is obviously an insufficient amount of routing libraries for PHP, so I wrote this one. Jokes aside, I do not recommend to use this library instead of popular ones like [FastRoute](https://github.com/nikic/FastRoute) or the [Symfony Routing](https://github.com/symfony/routing) component. I wrote it mainly as an exercise.

That being said, what can this library do? Like any router, it allows you to define which functionality your PHP application should invoke when a specific URL is requested. It also supports different HTTP methods, route parameters (including optional and wildcard parameters), route grouping, middleware and path generation through named routes. 

## Contents

* [Installation](#installation)
* [Usage Example](#usage-example)
* [Defining Routes](#defining-routes)
* [Route Parameters](#route-parameters)
* [Route Groups](#route-groups)
* [Middleware](#middleware)
* [Dispatching](#dispatching)
* [Path Generation](#path-generation)

## Installation

The library can be installed via [Composer](https://getcomposer.org).

```
composer require cklamm/router
```

## Usage Example

This is a simplified example showing the most important features. Explanations can be found in the respective sections of this documentation.

```php
$router = new \cklamm\Router\Router();      // instantiation

$router->middleware('global');              // global middleware
$router->get('', 'handler', 'home');        // home route (with name)

$router->group('pages', function () {       // route group
    $this->get('', 'page.index');           // GET route for pages
    $this->get('create', 'page.create');    // GET route for pages/create
    $this->post('', 'page.store');          // POST route for pages
    $this->get(':id', 'page.show');         // GET route for pages/:id
    $this->get(':id/edit', 'page.edit');    // GET route for pages/:id/edit
    $this->put(':id', 'page.update');       // PUT route for pages/:id
    $this->delete(':id', 'page.delete');    // DELETE route for pages/:id
})->middleware('mw1', 'mw2');               // group middleware

$router->get('foo/?opt', 'optional');       // optional parameter
$router->get('bar/*any', 'wildcard');       // wildcard parameter

$router->dispatch('get', 'pages/5/edit');   // dispatch requested path
```

## Defining Routes

Routes can be defined with the `add` method.

`add(string $method, string $route, mixed $handler, string $name = null): Route`

* `$method` is the HTTP method and will be converted to uppercase.
* `$route` is the actual route definition and can contain [parameters](#route-parameters).
* `$handler` can be a string, but it could also be a callback function that should be executed when the route is requested. Invoking such a function is left to the implementing application.
* The optional `$name` is used for [generating a path](#path-generation) for this route. Route names must be unique.

There are shortcut methods for common HTTP verbs (`GET, POST, PUT, PATCH, DELETE`). The following two definitions are equivalent.

```php
$router->add('GET', 'foo/bar', 'handler', 'name');
$router->get('foo/bar', 'handler', 'name');
```

## Route Parameters

Route definitions can contain parameters. The precedence of defined routes corresponds to the order of the table below. Therefore, unlike in many other routing libraries, the order in which routes are defined does not matter, as there is no ambiguity.

Notation | Type | Description
--- | --- | ---
`foo/bar` | static | This route has no parameters and matches exactly `foo/bar`.
`foo/:name` | required | This route allows any value in the second segment, e.g. `foo/bar` or `foo/26`.
`foo/?name` | optional | This route behaves like the one above, but it also matches `foo` alone. An optional parameter can only be followed by optional or wildcard parameters.
`foo/*name` | wildcard | This route matches `foo` followed by any number of segments, e.g. `foo/a/b/c`, but also `foo` alone. A wildcard must be the last segment of a route.

The following route has one required and two optional parameters. It matches paths such as `calendar/2020`, `calendar/2020/12` and `calendar/2020/12/31`.

```php
$router->get('calendar/:year/?month/?day', 'calendar');
```

> :warning: Be aware that this library does not use regular expressions for parameters, so the above route would also match `calendar/foo/bar`. Any validation must be done by the implementing application.

## Route Groups

Routes can be grouped with the `group` method.

`group(string $group, \Closure $cb = null): Node`

* `$group` is the prefix that all routes in this group will have. The prefix can consist of several segments.
* `$cb` is a callback function that contains the grouped route definitions. Inside this function, `$this` refers to the `Router` instance.

Route groups can be nested.

```php
$router->group('pages', function () {
    $this->get('', 'page.index');
    $this->get('create', 'page.create');
    $this->post('', 'page.store');

    $this->group(':id', function () {
        $this->get('', 'page.show');
        $this->get('edit', 'page.edit');
        $this->put('', 'page.update');
        $this->delete('', 'page.delete');
    });
});
```

## Middleware

Many PHP frameworks make use of *middleware* which provides functionality that should be executed before or after a request. Not every middleware should be executed for every request. This library makes it easy to assign middleware either globally, to a group of routes, or to a single route.

### Global Middleware

Middleware that should be executed for every request in your application can be assigned directly to the router.

```php
$router->middleware('global1');
$router->middleware('global2');
```

Instead of calling the method repeatedly, you can pass multiple middleware names at once.

```php
$router->middleware('global1', 'global2');
```

### Group Middleware

Middleware that should be executed for several related routes can be assigned to a route group.

```php
$router->group('foo', function () {
    $this->get('bar', 'handler');
})->middleware('group_mw');
```

If you do not want to use groups for defining routes, you can still use them for assigning middleware by omitting the callback function.

```php
$router->group('foo')->middleware('group_mw');
```

### Route Middleware

Middleware that should be executed for a single route only can be assigned to that route. Note that unlike global and group middleware, route middleware only applies to the HTTP method of that route.

```php
$router->get('foo/bar', 'handler')->middleware('route_mw');
```

## Dispatching

The `dispatch` method determines which of the previously defined routes matches a given path. Usually this method is called only once for each request.

`dispatch(string $method, string $path): Result`

* `$method` is the requested HTTP method.
* `$path` is the requested path (without domain name).

```php
$result = $router->dispatch('GET', 'pages/5/edit');
```

The `dispatch` method returns an instance of `cklamm\Router\Result`, which has the following public properties.

Property | Description
--- | ---
`code` | The HTTP status code is `200` if a matching route was found, `404` if no route was found, `405` if a route was found for another HTTP method than the requested one (see `options`).
`method` | The HTTP method that was requested.
`path` | The path that was requested.
`route` | The matching route or `null`.
`name` | The name of the matching route or `null`.
`handler` | The handler of the matching route or `null`.
`parameters` | An array containing the parameter values that have been extracted from the requested path. The values are in order of the parameters as they are defined in the route.
`middleware` | An array containing the middleware for matching route. This includes global, group and route middleware in the correct order.
`options` | An array with the HTTP methods that are available for the requested path.

If the `handler` that was defined for the route is a callback function, it can be executed like this:

```php
$handler = $result->handler;
$handler(...$result->parameters);
```

## Path Generation

The `path` method can be used to generate a path for a named route. This can be very useful for providing links to different pages of the application.

`path(string $route, mixed $data = []): string`

* `$route` is the name of the route. It needs to have been defined previously.
* `$data` is an array or an object containing values for the route parameters.

If a numerical array is passed to the `path` method, it will fill in the parameter values in the order they are provided. If an object or an associative array is passed, the method will fill in the values according to the names of the route parameters.

```php
$router->get('pages/:id/edit', 'handler', 'page.edit');
$router->get('calendar/:year/?month/?day', 'handler', 'calendar');
$router->get('foo/*any', 'handler', 'wildcard');

$router->path('page.edit', [5]);            // pages/5/edit
$router->path('page.edit', ['id' => 5]);    // pages/5/edit

$router->path('calendar', [2020]);          // calendar/2020
$router->path('calendar', [2020, 12]);      // calendar/2020/12
$router->path('calendar', [2020, 12, 31]);  // calendar/2020/12/31
$router->path('calendar', [                 // calendar/2020/12/31
    'year' => 2020,
    'month' => 12,
    'day' => 31,
]);

$router->path('wildcard', ['a', 'b', 'c']); // foo/a/b/c
$router->path('wildcard', [                 // foo/a/b/c
    'any' => ['a', 'b', 'c']
]);
```

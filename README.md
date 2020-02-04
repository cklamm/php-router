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

**`add(string $method, string $route, mixed $handler, string $name = null)`**

* `$method` is the HTTP method and will be converted to uppercase.
* `$route` is the actual route definition and can contain [parameters](#rote-parameters).
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
`foo/?name` | optional | This route behaves like the one above, but it also matches `foo` alone. An optional parameter can only be followed by other optional or wildcard parameters.
`foo/*name` | wildcard | This route matches `foo` followed by any number of segments, e.g. `foo/a/b/c`, but also `foo` alone. A wildcard must be the last segment of a route.

The following route has one required and two optional parameters. It matches paths such as `calendar/2020`, `calendar/2020/12` and `calendar/2020/12/31`.

```php
$router->get('calendar/:year/?month/?day', 'calendar');
```

<aside class="warning">
Be aware that this library does not use regular expressions for parameters, so the above route would also match `calendar/foo/bar`. Any validation must be done by the implementing application.
</aside>

## Route Groups

todo

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

todo

## Path Generation

todo

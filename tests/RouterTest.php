<?php namespace cklamm\Router\Tests;

use cklamm\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected $router;

    public function setUp(): void
    {
        $this->router = new Router();
        $this->router->middleware('global');
        $this->router->get('', 'get_', 'get_');

        $this->router->group('pages', function () {
            $this->get('', 'get_pages', 'get_pages');
            $this->get('create', 'get_pages_create', 'get_pages_create');
            $this->post('', 'post_pages', 'post_pages')->middleware('pages_post');

            $this->group(':id', function () {
                $this->get('', 'get_pages_id', 'get_pages_id')->middleware('pages_var_get');
                $this->get('edit', 'get_pages_id_edit', 'get_pages_id_edit');
                $this->put('', 'put_pages_id', 'put_pages_id');
                $this->delete('', 'delete_pages_id', 'delete_pages_id');
            })->middleware('pages_var');

            $this->group('about', function () {
                $this->get('', 'get_pages_about', 'get_pages_about');
                $this->get('edit', 'get_pages_about_edit', 'get_pages_about_edit');
            })->middleware('pages_about');
        })->middleware('pages');

        $this->router->get('calendar/:year/?month/?day',
            'get_calendar_year_month_day', 'get_calendar_year_month_day');

        $this->router->get('any/foo', 'get_any_foo', 'get_any_foo');
        $this->router->get('any/:var', 'get_any_var', 'get_any_var');
        $this->router->get('any/?opt', 'get_any_opt', 'get_any_opt');
        $this->router->get('any/*any', 'get_any_any', 'get_any_any');
    }

    protected function generate_handler($method, $route)
    {
        if (is_null($route)) return null;
        $route = preg_replace('#/[:?*]?#', '_', $route);
        return strtolower($method) . '_' . $route;
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_routes($method, $path, $expected, $params = [])
    {
        $result = $this->router->dispatch($method, $path);
        $handler = $this->generate_handler($method, $expected);

        $code = empty($result->options) ? 404 : 405;
        if (isset($result->route)) $code = 200;

        $this->assertSame($code, $result->code);
        $this->assertSame(strtoupper($method), $result->method);
        $this->assertSame($path, $result->path);
        $this->assertSame($expected, $result->route);
        $this->assertSame($handler, $result->name);
        $this->assertSame($handler, $result->handler);
        $this->assertSame($params, $result->parameters);
    }

    /**
     * @dataProvider middlewareProvider
     */
    public function test_middleware($method, $path, $expected)
    {
        $result = $this->router->dispatch($method, $path);
        $this->assertSame($expected, $result->middleware);
    }

    /**
     * @dataProvider optionsProvider
     */
    public function test_options($method, $path, $expected)
    {
        $result = $this->router->dispatch($method, $path);
        $this->assertSame($expected, $result->options);
    }

    /**
     * @dataProvider pathProvider
     */
    public function test_generates_path($name, $data, $expected)
    {
        $path = $this->router->path($name, $data);
        $this->assertSame($expected, $path);
    }

    public function test_exception_route_already_exists()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/:a', 'get_foo_a');
        $router->get('foo/:b', 'get_foo_b');
    }

    public function test_exception_named_route_already_exists()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo', 'get_foo', 'name');
        $router->get('bar', 'get_bar', 'name');
    }

    public function test_exception_named_route_does_not_exist()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->path('name');
    }

    public function test_exception_parameter_must_have_name()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/:/bar', 'get_foo_var_bar');
    }

    public function test_exception_optional_parameter_must_have_name()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/?/bar', 'get_foo_var_bar');
    }

    public function test_exception_optional_parameter_followed_by()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/?var/bar', 'get_foo_var_bar');
    }

    public function test_exception_wildcard_must_be_last_segment()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/*/bar', 'get_foo_var_bar');
    }

    public function test_exception_no_value_for_placeholder()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/:var', 'get_foo_var', 'get_foo_var');
        $router->path('get_foo_var');
    }

    public function test_exception_wildcard_must_have_name()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/*', 'get_foo_var', 'get_foo_var');
        $router->path('get_foo_var', ['wildcard' => 5]);
    }

    public function test_exception_wildcard_expects_array()
    {
        $this->expectException(\Exception::class);

        $router = new Router();
        $router->get('foo/*wildcard', 'get_foo_var', 'get_foo_var');
        $router->path('get_foo_var', ['wildcard' => 5]);
    }

    public function routeProvider()
    {
        return [
            ['get', 'foo', null],
            ['get', 'foo/bar', null],

            ['get', '', ''],
            ['post', '', null],

            ['get', 'pages', 'pages'],
            ['post', 'pages', 'pages'],
            ['put', 'pages', null],

            ['get', 'pages/foo', 'pages/:id', ['foo']],
            ['put', 'pages/foo', 'pages/:id', ['foo']],
            ['delete', 'pages/foo', 'pages/:id', ['foo']],
            ['post', 'pages/foo', null],

            ['get', 'pages/about', 'pages/about'],
            ['put', 'pages/about', 'pages/:id', ['about']],
            ['delete', 'pages/about', 'pages/:id', ['about']],

            ['get', 'pages/create', 'pages/create'],
            ['put', 'pages/create', 'pages/:id', ['create']],
            ['delete', 'pages/create', 'pages/:id', ['create']],

            ['get', 'pages/foo/edit', 'pages/:id/edit', ['foo']],
            ['get', 'pages/about/edit', 'pages/about/edit'],
            ['get', 'pages/create/edit', 'pages/:id/edit', ['create']],
            ['get', 'pages/foo/bar', null],

            ['get', 'calendar', null],
            ['get', 'calendar/2020', 'calendar/:year/?month/?day', ['2020', null, null]],
            ['get', 'calendar/2020/12', 'calendar/:year/?month/?day', ['2020', '12', null]],
            ['get', 'calendar/2020/12/31', 'calendar/:year/?month/?day', ['2020', '12', '31']],
            ['get', 'calendar/2020/12/31/foo', null],

            ['get', 'any', 'any/?opt', [null]],
            ['get', 'any/foo', 'any/foo'],
            ['get', 'any/bar', 'any/:var', ['bar']],
            ['get', 'any/foo/bar', 'any/*any', ['foo', 'bar']],
            ['get', 'any/a/b/c', 'any/*any', ['a', 'b', 'c']],
        ];
    }

    public function middlewareProvider()
    {
        return [
            ['get', 'foo', []],
            ['get', 'foo/bar', []],
            ['get', '', ['global']],

            ['get', 'pages', ['global', 'pages']],
            ['post', 'pages', ['global', 'pages', 'pages_post']],
            ['get', 'pages/foo', ['global', 'pages', 'pages_var', 'pages_var_get']],
            ['get', 'pages/about', ['global', 'pages', 'pages_about']],
            ['get', 'pages/create', ['global', 'pages']],
            ['get', 'pages/foo/edit', ['global', 'pages', 'pages_var']],
            ['get', 'pages/about/edit', ['global', 'pages', 'pages_about']],
            ['get', 'pages/create/edit', ['global', 'pages', 'pages_var']],
            ['get', 'pages/foo/bar', []],
        ];
    }

    public function optionsProvider()
    {
        return [
            ['get', 'foo', []],
            ['get', 'foo/bar', []],
            ['get', '', ['GET']],

            ['get', 'pages', ['GET', 'POST']],
            ['get', 'pages/foo', ['GET', 'PUT', 'DELETE']],
            ['get', 'pages/about', ['GET', 'PUT', 'DELETE']],
            ['get', 'pages/foo/edit', ['GET']],
            ['get', 'pages/about/edit', ['GET']],
            ['get', 'pages/foo/bar', []],
        ];
    }

    public function pathProvider()
    {
        return [
            ['get_', [], ''],
            ['get_pages', [], 'pages'],
            ['get_pages_id', ['foo'], 'pages/foo'],
            ['get_pages_id', ['id' => 'foo'], 'pages/foo'],
            ['get_pages_about', [], 'pages/about'],
            ['get_pages_id_edit', [0], 'pages/0/edit'],
            ['get_pages_id_edit', ['id' => 0], 'pages/0/edit'],
            ['get_pages_about_edit', [], 'pages/about/edit'],

            ['get_calendar_year_month_day', [2020], 'calendar/2020'],
            ['get_calendar_year_month_day', [2020, 12], 'calendar/2020/12'],
            ['get_calendar_year_month_day', [2020, 12, 31], 'calendar/2020/12/31'],

            ['get_calendar_year_month_day', [
                'year' => 2020
            ], 'calendar/2020'],
            ['get_calendar_year_month_day', [
                'year' => 2020, 'month' => 12
            ], 'calendar/2020/12'],
            ['get_calendar_year_month_day', [
                'year' => 2020, 'month' => 12, 'day' => 31
            ], 'calendar/2020/12/31'],

            ['get_any_foo', [], 'any/foo'],
            ['get_any_var', ['foo'], 'any/foo'],
            ['get_any_var', ['var' => 'foo'], 'any/foo'],
            ['get_any_opt', [], 'any'],
            ['get_any_opt', ['foo'], 'any/foo'],
            ['get_any_opt', ['opt' => 'foo'], 'any/foo'],

            ['get_any_any', [], 'any'],
            ['get_any_any', ['foo'], 'any/foo'],
            ['get_any_any', ['foo', 'bar'], 'any/foo/bar'],
            ['get_any_any', [3, 4, 5], 'any/3/4/5'],

            ['get_any_any', ['any' => []], 'any'],
            ['get_any_any', ['any' => ['foo']], 'any/foo'],
            ['get_any_any', ['any' => ['foo', 'bar']], 'any/foo/bar'],
            ['get_any_any', ['any' => [3, 4, 5]], 'any/3/4/5'],
        ];
    }
}

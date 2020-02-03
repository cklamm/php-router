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

            $this->group(':id')->middleware('pages_var');
            $this->get(':id', 'get_pages_id', 'get_pages_id')->middleware('pages_var_get');
            $this->get(':id/edit', 'get_pages_id_edit', 'get_pages_id_edit');
            $this->put(':id', 'put_pages_id', 'put_pages_id');
            $this->delete(':id', 'delete_pages_id', 'delete_pages_id');

            $this->group('about', function () {
                $this->get('', 'get_pages_about', 'get_pages_about');
                $this->get('edit', 'get_pages_about_edit', 'get_pages_about_edit');
            })->middleware('pages_about');
        })->middleware('pages');
    }

    protected function generate_handler($method, $route)
    {
        if (is_null($route)) return null;
        $route = preg_replace('#/:?#', '_', $route);
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
        ];
    }

    public function pathProvider()
    {
        return [
            ['get_', [], ''],
            ['get_pages', [], 'pages'],
            ['get_pages_id', ['foo'], 'pages/foo'],
            ['get_pages_about', [], 'pages/about'],
            ['get_pages_id_edit', [5], 'pages/5/edit'],
            ['get_pages_about_edit', [], 'pages/about/edit'],
        ];
    }
}

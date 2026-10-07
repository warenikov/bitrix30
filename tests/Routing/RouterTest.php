<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Routing;

use Bitrix30\Http\Exception\MethodNotAllowedException;
use Bitrix30\Http\Exception\NotFoundException;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Routing\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;

final class RouterTest extends TestCase
{
    public function testMatchesStaticRoute(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->get('/', self::class)->name('home');
        });

        $params = $router->match(new ServerRequest('GET', 'http://localhost/'));

        self::assertSame('home', $params['_route']);
        self::assertSame(self::class, $params[Router::HANDLER]);
    }

    public function testMatchesPlaceholderWithRequirement(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->get('/news/{slug}', self::class)
                ->name('news.show')
                ->where('slug', '[a-z0-9-]+');
        });

        $params = $router->match(new ServerRequest('GET', 'http://localhost/news/hello-world'));
        self::assertSame('hello-world', $params['slug']);

        $this->expectException(NotFoundException::class);
        $router->match(new ServerRequest('GET', 'http://localhost/news/HELLO'));
    }

    public function testThrowsNotFound(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->get('/', self::class);
        });

        $this->expectException(NotFoundException::class);
        $router->match(new ServerRequest('GET', 'http://localhost/missing'));
    }

    public function testThrowsMethodNotAllowedWithAllowHeader(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->post('/form', self::class);
        });

        try {
            $router->match(new ServerRequest('GET', 'http://localhost/form'));
            self::fail('Ожидали MethodNotAllowedException');
        } catch (MethodNotAllowedException $exception) {
            self::assertSame(405, $exception->getStatusCode());
            self::assertSame(['Allow' => 'POST'], $exception->getHeaders());
        }
    }

    public function testPrefixGroupsNest(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->prefix('/admin', static function (RouteConfigurator $routes): void {
                $routes->prefix('/users', static function (RouteConfigurator $routes): void {
                    $routes->get('/{id}', self::class)->name('admin.users.show')->where('id', '\d+');
                });
            });
        });

        $params = $router->match(new ServerRequest('GET', 'http://localhost/admin/users/42'));

        self::assertSame('admin.users.show', $params['_route']);
        self::assertSame('42', $params['id']);
    }

    public function testGeneratesUrlByName(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->get('/news/{slug}', self::class)->name('news.show');
        });

        // генерация не требует предварительного match()
        self::assertSame('/news/other', $router->generate('news.show', ['slug' => 'other']));
    }

    public function testPreservesTrailingSlash(): void
    {
        $router = $this->router(static function (RouteConfigurator $routes): void {
            $routes->get('/news/', self::class)->name('news.index');
        });

        $params = $router->match(new ServerRequest('GET', 'http://localhost/news/'));
        self::assertSame('news.index', $params['_route']);

        // «/news» и «/news/» — разные URL, редиректов пока нет
        $this->expectException(NotFoundException::class);
        $router->match(new ServerRequest('GET', 'http://localhost/news'));
    }

    public function testDuplicateRouteNamesAreRejected(): void
    {
        $routes = new RouteConfigurator();
        $routes->get('/feed', self::class);
        $routes->get('/feed', self::class);

        $this->expectException(\LogicException::class);
        $routes->build();
    }

    public function testProductionCacheIsWrittenAndReused(): void
    {
        $cacheFile = sys_get_temp_dir() . '/b30-routes-' . bin2hex(random_bytes(4)) . '.php';

        try {
            $builds = 0;
            $loader = static function () use (&$builds): RouteCollection {
                ++$builds;
                $routes = new RouteConfigurator();
                $routes->get('/', self::class)->name('home');

                return $routes->build();
            };

            $first = new Router($loader, $cacheFile, false);
            $first->match(new ServerRequest('GET', 'http://localhost/'));
            self::assertFileExists($cacheFile);
            self::assertSame(1, $builds);

            // второй роутер читает кеш, загрузчик не дёргается
            $second = new Router($loader, $cacheFile, false);
            $params = $second->match(new ServerRequest('GET', 'http://localhost/'));
            self::assertSame('home', $params['_route']);
            self::assertSame(1, $builds);
        } finally {
            @unlink($cacheFile);
        }
    }

    /** @param \Closure(RouteConfigurator): void $configure */
    private function router(\Closure $configure): Router
    {
        return new Router(
            static function () use ($configure): RouteCollection {
                $routes = new RouteConfigurator();
                $configure($routes);

                return $routes->build();
            },
            sys_get_temp_dir() . '/b30-routes-unused.php',
            true,
        );
    }
}

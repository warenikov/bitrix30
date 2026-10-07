<?php

declare(strict_types=1);

/**
 * Определения ядра для DI-контейнера (PHP-DI).
 *
 * Замыкание-определение — это фабрика: PHP-DI инжектит параметры
 * по type-hint. Классы без зависимостей собирает autowiring,
 * здесь только интерфейсы и сборки со скалярными параметрами.
 */

use Bitrix30\Http\ControllerDispatcher;
use Bitrix30\Http\Kernel;
use Bitrix30\Http\Middleware\ErrorHandlerMiddleware;
use Bitrix30\Http\Middleware\MaintenanceMiddleware;
use Bitrix30\Http\Middleware\RequestIdMiddleware;
use Bitrix30\Http\Middleware\RouteMatchMiddleware;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\RouteCollection;

return [
    ResponseFactoryInterface::class => static fn (Psr17Factory $factory): ResponseFactoryInterface => $factory,

    // Monolog приедет следующей подсистемой этапа 1; до него — заглушка
    LoggerInterface::class => static fn (): LoggerInterface => new NullLogger(),

    Router::class => static function (ContainerInterface $c): Router {
        $projectDir = $c->get('app.project_dir');
        $cacheDir = $c->get('app.cache_dir');
        \assert(\is_string($projectDir) && \is_string($cacheDir));

        $routesDir = $projectDir . '/config/routes';
        $loader = static function () use ($routesDir): RouteCollection {
            $configurator = new RouteConfigurator();
            foreach (glob($routesDir . '/*.php') ?: [] as $file) {
                $configure = require $file;
                \assert($configure instanceof \Closure);
                $configure($configurator);
            }

            return $configurator->build();
        };

        return new Router($loader, $cacheDir . '/routes.php', (bool) $c->get('app.debug'));
    },

    ErrorHandlerMiddleware::class => static fn (ContainerInterface $c): ErrorHandlerMiddleware => new ErrorHandlerMiddleware(
        $c->get(ResponseFactoryInterface::class),
        $c->get(LoggerInterface::class),
        (bool) $c->get('app.debug'),
    ),

    MaintenanceMiddleware::class => static function (ContainerInterface $c): MaintenanceMiddleware {
        $projectDir = $c->get('app.project_dir');
        \assert(\is_string($projectDir));

        return new MaintenanceMiddleware(
            $c->get(ResponseFactoryInterface::class),
            $projectDir . '/var/maintenance.flag',
        );
    },

    ControllerDispatcher::class => static fn (ContainerInterface $c): ControllerDispatcher => new ControllerDispatcher($c),

    Kernel::class => static fn (ContainerInterface $c): Kernel => new Kernel(
        [
            // порядок важен: request-id раньше error handler'а,
            // чтобы id попал и в лог ошибки, и на страницу ошибки
            $c->get(RequestIdMiddleware::class),
            $c->get(ErrorHandlerMiddleware::class),
            $c->get(MaintenanceMiddleware::class),
            $c->get(RouteMatchMiddleware::class),
        ],
        $c->get(ControllerDispatcher::class),
    ),
];

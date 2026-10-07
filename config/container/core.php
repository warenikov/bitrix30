<?php

declare(strict_types=1);

/**
 * Определения ядра для DI-контейнера (PHP-DI).
 *
 * Замыкание-определение — это фабрика: PHP-DI инжектит параметры
 * по type-hint. Классы без зависимостей собирает autowiring,
 * здесь только интерфейсы и сборки со скалярными параметрами.
 */

use Bitrix30\Container\Typed;
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
        $routesDir = Typed::string($c, 'app.project_dir') . '/config/routes';

        $loader = static function () use ($routesDir): RouteCollection {
            $configurator = new RouteConfigurator();
            $files = glob($routesDir . '/*.php');
            foreach ($files === false ? [] : $files as $file) {
                $configure = require $file;
                if (!$configure instanceof \Closure) {
                    throw new \RuntimeException(sprintf('Файл роутов %s должен возвращать closure, получен %s.', $file, get_debug_type($configure)));
                }
                $configure($configurator);
            }

            return $configurator->build();
        };

        return new Router($loader, Typed::string($c, 'app.cache_dir') . '/routes.php', Typed::bool($c, 'app.debug'));
    },

    ErrorHandlerMiddleware::class => static fn (ContainerInterface $c): ErrorHandlerMiddleware => new ErrorHandlerMiddleware(
        Typed::service($c, ResponseFactoryInterface::class),
        Typed::service($c, LoggerInterface::class),
        Typed::bool($c, 'app.debug'),
    ),

    MaintenanceMiddleware::class => static fn (ContainerInterface $c): MaintenanceMiddleware => new MaintenanceMiddleware(
        Typed::service($c, ResponseFactoryInterface::class),
        Typed::string($c, 'app.project_dir') . '/var/maintenance.flag',
    ),

    ControllerDispatcher::class => static fn (ContainerInterface $c): ControllerDispatcher => new ControllerDispatcher($c),

    Kernel::class => static fn (ContainerInterface $c): Kernel => new Kernel(
        [
            // порядок важен: request-id раньше error handler'а,
            // чтобы id попал и в лог ошибки, и на страницу ошибки
            Typed::service($c, RequestIdMiddleware::class),
            Typed::service($c, ErrorHandlerMiddleware::class),
            Typed::service($c, MaintenanceMiddleware::class),
            Typed::service($c, RouteMatchMiddleware::class),
        ],
        Typed::service($c, ControllerDispatcher::class),
    ),
];

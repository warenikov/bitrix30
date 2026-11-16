<?php

declare(strict_types=1);

/**
 * Определения ядра для DI-контейнера (PHP-DI).
 *
 * Замыкание-определение — это фабрика: PHP-DI инжектит параметры
 * по type-hint. Классы без зависимостей собирает autowiring,
 * здесь только интерфейсы и сборки со скалярными параметрами.
 */

use Bitrix30\Component\ComponentInterface;
use Bitrix30\Component\ComponentRegistry;
use Bitrix30\Container\Typed;
use Bitrix30\Http\ControllerDispatcher;
use Bitrix30\Http\Kernel;
use Bitrix30\Http\Error\ErrorPageRendererInterface;
use Bitrix30\Http\Error\ProdErrorPageRenderer;
use Bitrix30\Http\Error\WhoopsErrorPageRenderer;
use Bitrix30\Http\Middleware\ErrorHandlerMiddleware;
use Bitrix30\Http\Middleware\MaintenanceMiddleware;
use Bitrix30\Http\Middleware\RequestIdMiddleware;
use Bitrix30\Http\Middleware\RouteMatchMiddleware;
use Bitrix30\Log\RequestIdProcessor;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Routing\Router;
use Bitrix30\Site\Site;
use Bitrix30\Site\SiteRegistry;
use Bitrix30\View\TemplateRendererInterface;
use Bitrix30\View\Twig\Bitrix30Extension;
use Bitrix30\View\TwigRenderer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Routing\RouteCollection;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

return [
    ResponseFactoryInterface::class => static fn (Psr17Factory $factory): ResponseFactoryInterface => $factory,

    LoggerInterface::class => static function (ContainerInterface $c): LoggerInterface {
        $handler = new StreamHandler('php://stdout');
        $handler->setFormatter(Typed::bool($c, 'app.debug')
            ? new LineFormatter(null, null, true, true, true)
            : new JsonFormatter());

        $logger = new Logger('bitrix30');
        $logger->pushHandler($handler);
        $logger->pushProcessor(Typed::service($c, RequestIdProcessor::class));

        return $logger;
    },

    Router::class => static function (ContainerInterface $c): Router {
        $routesDir = Typed::string($c, 'app.project_dir') . '/config/routes';

        $loader = static function () use ($routesDir, $c): RouteCollection {
            $configurator = new RouteConfigurator();
            $files = glob($routesDir . '/*.php');
            foreach ($files === false ? [] : $files as $file) {
                $configure = require $file;
                if (!$configure instanceof \Closure) {
                    throw new \RuntimeException(sprintf('Файл роутов %s должен возвращать closure, получен %s.', $file, get_debug_type($configure)));
                }
                $configure($configurator);
            }

            Typed::service($c, SiteRegistry::class)->configureRoutes($configurator);

            return $configurator->build();
        };

        return new Router($loader, Typed::string($c, 'app.cache_dir') . '/routes.php', Typed::bool($c, 'app.debug'));
    },

    ErrorPageRendererInterface::class => static fn (ContainerInterface $c): ErrorPageRendererInterface => Typed::bool($c, 'app.debug')
        ? new WhoopsErrorPageRenderer()
        : new ProdErrorPageRenderer(),

    ErrorHandlerMiddleware::class => static fn (ContainerInterface $c): ErrorHandlerMiddleware => new ErrorHandlerMiddleware(
        Typed::service($c, ResponseFactoryInterface::class),
        Typed::service($c, LoggerInterface::class),
        Typed::service($c, ErrorPageRendererInterface::class),
    ),

    MaintenanceMiddleware::class => static fn (ContainerInterface $c): MaintenanceMiddleware => new MaintenanceMiddleware(
        Typed::service($c, ResponseFactoryInterface::class),
        Typed::string($c, 'app.project_dir') . '/var/maintenance.flag',
    ),

    Environment::class => static function (ContainerInterface $c): Environment {
        $projectDir = Typed::string($c, 'app.project_dir');
        $debug = Typed::bool($c, 'app.debug');

        $loader = new FilesystemLoader();
        $sites = Typed::service($c, SiteRegistry::class);
        foreach ($sites->pagesDirectories() as $directory) {
            if (is_dir($directory)) {
                $loader->addPath($directory, 'site');
            }
        }
        $loader->addPath($sites->layout()->templatesDirectory(), 'layout');

        // шаблоны компонентов: @components/<Имя>/templates/…
        $loader->addPath($projectDir . '/app/Component', 'components');

        $twig = new Environment($loader, [
            'debug' => $debug,
            // кеш включён всегда: в dev auto_reload (= debug) перекомпилирует
            // только изменившиеся шаблоны, и prod-путь всегда прогрет
            'cache' => Typed::string($c, 'app.cache_dir') . '/twig',
            'strict_variables' => true,
            // yield-рантайм Twig 3.12+; наши узлы написаны под него
            'use_yield' => true,
        ]);
        $twig->addRuntimeLoader(new ContainerRuntimeLoader($c));
        $twig->addExtension(new Bitrix30Extension());

        return $twig;
    },

    TemplateRendererInterface::class => static fn (ContainerInterface $c): TemplateRendererInterface => new TwigRenderer(
        Typed::service($c, Environment::class),
    ),

    SiteRegistry::class => static function (ContainerInterface $c): SiteRegistry {
        $siteClass = Typed::string($c, 'app.site');
        if (!is_a($siteClass, Site::class, true)) {
            throw new \RuntimeException(sprintf('Параметр app.site должен содержать класс, наследующий %s.', Site::class));
        }

        return new SiteRegistry($c, $siteClass);
    },

    ComponentRegistry::class => static function (ContainerInterface $c): ComponentRegistry {
        $file = Typed::string($c, 'app.project_dir') . '/config/components.php';
        $map = is_file($file) ? require $file : [];
        if (!\is_array($map)) {
            throw new \RuntimeException('config/components.php должен возвращать массив «имя → класс компонента».');
        }

        /** @var array<string, class-string<ComponentInterface>> $map */
        return new ComponentRegistry($map);
    },

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

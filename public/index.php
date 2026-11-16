<?php

declare(strict_types=1);

/**
 * Front controller — единственная точка входа HTTP.
 * Здесь только сборка контейнера, запроса и ядра; логика — внутри ядра.
 */

use Bitrix30\Container\Typed;
use Bitrix30\Http\Kernel;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require \dirname(__DIR__) . '/vendor/autoload.php';

// Встроенный сервер должен сам отдать существующий публичный ассет;
// остальные пути продолжают идти через front controller.
if (PHP_SAPI === 'cli-server') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = \is_string($requestUri) ? parse_url($requestUri, \PHP_URL_PATH) : false;
    $isPublicAsset = \is_string($path) && ($path === '/favicon.svg' || str_starts_with($path, '/assets/'));
    if ($isPublicAsset && is_file(__DIR__ . $path)) {
        return false;
    }
}

$projectDir = \dirname(__DIR__);

$builder = new ContainerBuilder();
$builder->addDefinitions($projectDir . '/config/app.php');
$builder->addDefinitions($projectDir . '/config/container/core.php');
$container = $builder->build();

$psr17Factory = Typed::service($container, Psr17Factory::class);
$request = (new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
    ->fromGlobals();

$kernel = Typed::service($container, Kernel::class);
$response = $kernel->handle($request);

(new SapiEmitter())->emit($response);
$kernel->terminate($request, $response);

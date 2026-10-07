<?php

declare(strict_types=1);

/**
 * Front controller — единственная точка входа HTTP.
 * Здесь только сборка контейнера, запроса и ядра; логика — внутри ядра.
 */

use Bitrix30\Http\Kernel;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require \dirname(__DIR__) . '/vendor/autoload.php';

$projectDir = \dirname(__DIR__);

$builder = new ContainerBuilder();
$builder->addDefinitions($projectDir . '/config/app.php');
$builder->addDefinitions($projectDir . '/config/container/core.php');
$container = $builder->build();

$psr17Factory = $container->get(Psr17Factory::class);
$request = (new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
    ->fromGlobals();

$kernel = $container->get(Kernel::class);
$response = $kernel->handle($request);

(new SapiEmitter())->emit($response);
$kernel->terminate($request, $response);

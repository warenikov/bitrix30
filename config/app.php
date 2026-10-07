<?php

declare(strict_types=1);

/**
 * Базовые параметры приложения. Значения берутся из ENV
 * (задаются в compose.yaml локально и в окружении прода),
 * секретов здесь нет и быть не должно.
 */

$env = $_SERVER['APP_ENV'] ?? 'dev';
$env = \is_string($env) ? $env : 'dev';

$debugRaw = $_SERVER['APP_DEBUG'] ?? ($env === 'dev' ? '1' : '0');

return [
    'app.env' => $env,
    'app.debug' => $debugRaw === '1' || $debugRaw === 'true',
    'app.project_dir' => \dirname(__DIR__),
    'app.cache_dir' => \dirname(__DIR__) . '/var/cache',
];

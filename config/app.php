<?php

declare(strict_types=1);

/**
 * Базовые параметры приложения. Значения берутся из ENV
 * (задаются в compose.yaml локально и в окружении прода),
 * секретов здесь нет и быть не должно.
 *
 * Безопасный дефолт — prod без debug: окружение, забывшее задать
 * APP_ENV, не должно светить трейсы наружу.
 */

// cli-server (php -S) не кладёт env-переменные в $_SERVER запроса,
// поэтому смотрим во все три места
$readEnv = static function (string $name): ?string {
    $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

    return \is_string($value) ? $value : null;
};

$env = $readEnv('APP_ENV') ?? 'prod';
$debugRaw = $readEnv('APP_DEBUG') ?? ($env === 'dev' ? '1' : '0');

return [
    'app.env' => $env,
    'app.debug' => $debugRaw === '1' || $debugRaw === 'true',
    'app.project_dir' => \dirname(__DIR__),
    'app.cache_dir' => \dirname(__DIR__) . '/var/cache',
];

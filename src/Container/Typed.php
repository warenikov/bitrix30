<?php

declare(strict_types=1);

namespace Bitrix30\Container;

use Psr\Container\ContainerInterface;

/**
 * Типобезопасное чтение из PSR-11 контейнера.
 *
 * PSR-11 `get()` возвращает mixed; эти хелперы проверяют тип в рантайме
 * (в отличие от `assert()`, который в prod вырезается) и отдают phpstan'у
 * точный тип. Используются в определениях контейнера и bootstrap-коде —
 * это не service locator: в конструкторы классов контейнер не попадает.
 */
final class Typed
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function service(ContainerInterface $container, string $class): object
    {
        $service = $container->get($class);
        if (!$service instanceof $class) {
            throw new \RuntimeException(sprintf('В контейнере по ключу «%s» лежит %s.', $class, get_debug_type($service)));
        }

        return $service;
    }

    public static function string(ContainerInterface $container, string $id): string
    {
        $value = $container->get($id);
        if (!\is_string($value)) {
            throw new \RuntimeException(sprintf('Параметр контейнера «%s» должен быть строкой, получен %s.', $id, get_debug_type($value)));
        }

        return $value;
    }

    public static function bool(ContainerInterface $container, string $id): bool
    {
        $value = $container->get($id);
        if (!\is_bool($value)) {
            throw new \RuntimeException(sprintf('Параметр контейнера «%s» должен быть bool, получен %s.', $id, get_debug_type($value)));
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Component\Fixture\Greeting;

use Bitrix30\Component\ComponentInterface;

/** Фикстура: минимальный компонент с параметрами и CSS в resources/. */
final class Greeting implements ComponentInterface
{
    public static function paramsClass(): string
    {
        return GreetingParams::class;
    }

    public function render(?object $params): string
    {
        \assert($params instanceof GreetingParams);

        return sprintf('<p>Привет, %s!</p>', htmlspecialchars($params->name, \ENT_QUOTES));
    }
}

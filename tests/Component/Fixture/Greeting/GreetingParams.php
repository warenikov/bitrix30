<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Component\Fixture\Greeting;

/** Фикстура: typed DTO параметров компонента. */
final readonly class GreetingParams
{
    public function __construct(
        public string $name,
        public bool $exclaim = true,
    ) {
    }
}

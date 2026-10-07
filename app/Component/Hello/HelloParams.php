<?php

declare(strict_types=1);

namespace App\Component\Hello;

/** Параметры компонента hello. Опечатка в имени параметра — ошибка, не тишина. */
final readonly class HelloParams
{
    public function __construct(
        public string $name,
        public bool $exclaim = true,
    ) {
    }
}

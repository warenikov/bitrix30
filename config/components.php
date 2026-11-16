<?php

declare(strict_types=1);

/** Карта компонентов приложения: имя → класс. Регистрация явная, без сканирования. */

use App\Component\Hello\Hello;
use App\Component\Hero\Hero;

return [
    'hello' => Hello::class,
    'hero' => Hero::class,
];

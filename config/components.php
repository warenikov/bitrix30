<?php

declare(strict_types=1);

/** Карта компонентов приложения: имя → класс. Регистрация явная, без сканирования. */

use App\Component\Hello\Hello;

return [
    'hello' => Hello::class,
];

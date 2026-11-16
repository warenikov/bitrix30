<?php

declare(strict_types=1);

namespace App\Module;

use Bitrix30\Admin\ModuleInterface;

final class CoreModule implements ModuleInterface
{
    public function key(): string
    {
        return 'core';
    }

    public function name(): string
    {
        return 'Core Module';
    }

    public function version(): string
    {
        return '0.1.0';
    }
}

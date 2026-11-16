<?php

declare(strict_types=1);

namespace App\Layout;

use Bitrix30\Site\Layout;

final class MainLayout extends Layout
{
    public function context(): array
    {
        return [
            'siteName' => 'bitrix30',
        ];
    }
}

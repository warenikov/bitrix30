<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Site\Fixture;

use App\Layout\MainLayout;
use Bitrix30\Site\Layout;
use Bitrix30\Site\Site;

final class CircularSite extends Site
{
    public static function parentSite(): string
    {
        return self::class;
    }

    public function layout(): Layout
    {
        return new MainLayout();
    }
}

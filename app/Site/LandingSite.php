<?php

declare(strict_types=1);

namespace App\Site;

/** Дочерний сайт: заменяет главную страницу, остальное наследует от AppSite. */
final class LandingSite extends AppSite
{
    /** @return class-string<AppSite> */
    public static function parentSite(): string
    {
        return AppSite::class;
    }
}

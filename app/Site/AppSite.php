<?php

declare(strict_types=1);

namespace App\Site;

use App\Controller\AboutController;
use App\Controller\HomeController;
use App\Layout\MainLayout;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Site\Layout;
use Bitrix30\Site\Site;

class AppSite extends Site
{
    public function layout(): Layout
    {
        return new MainLayout();
    }

    public function routes(RouteConfigurator $routes): void
    {
        $routes->get('/', HomeController::class)->name('home');
        $routes->get('/about', AboutController::class)->name('about');
    }
}

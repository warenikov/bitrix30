<?php

declare(strict_types=1);

use App\Controller\HomeController;
use Bitrix30\Routing\RouteConfigurator;

return static function (RouteConfigurator $routes): void {
    $routes->get('/', HomeController::class)->name('home');
};

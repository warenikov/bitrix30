<?php

declare(strict_types=1);

use App\Controller\PingController;
use Bitrix30\Routing\RouteConfigurator;

return static function (RouteConfigurator $routes): void {
    $routes->prefix('/api', static function (RouteConfigurator $routes): void {
        $routes->get('/ping', PingController::class)->name('api.ping');
    });
};

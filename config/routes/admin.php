<?php

declare(strict_types=1);

use App\Admin\DashboardController;
use Bitrix30\Routing\RouteConfigurator;

return static function (RouteConfigurator $routes): void {
    $routes->get('/admin', DashboardController::class)->name('admin.dashboard');
};

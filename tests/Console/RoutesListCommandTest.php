<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Console;

use App\Controller\HomeController;
use App\Controller\PingController;
use Bitrix30\Console\RoutesListCommand;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Routing\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RoutesListCommandTest extends TestCase
{
    public function testListsRouteMetadataAndHandlers(): void
    {
        $configurator = new RouteConfigurator();
        $configurator->get('/', HomeController::class)->name('home');
        $configurator->post('/ping', [PingController::class, '__invoke'])->name('ping');
        $configurator->any('/any', HomeController::class)->name('any');
        $collection = $configurator->build();

        $command = new RoutesListCommand(new Router(
            static fn () => $collection,
            '/tmp/bitrix30-routes-list-test.php',
            true,
        ));
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('home', $display);
        self::assertStringContainsString('POST', $display);
        self::assertStringContainsString('ANY', $display);
        self::assertStringContainsString('/ping', $display);
        self::assertStringContainsString(HomeController::class, $display);
        self::assertStringContainsString(PingController::class . '::__invoke', $display);
    }
}

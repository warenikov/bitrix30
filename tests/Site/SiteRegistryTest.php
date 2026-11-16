<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Site;

use App\Site\AppSite;
use App\Site\LandingSite;
use Bitrix30\Routing\RouteConfigurator;
use Bitrix30\Site\Site;
use Bitrix30\Site\SiteRegistry;
use Bitrix30\Tests\Site\Fixture\CircularSite;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class SiteRegistryTest extends TestCase
{
    public function testLineageStartsWithChildAndIncludesParent(): void
    {
        $registry = $this->registry(LandingSite::class);

        self::assertSame(
            [LandingSite::class, AppSite::class],
            array_map(static fn (Site $site): string => $site::class, $registry->lineage()),
        );
        self::assertSame(
            [
                \dirname(__DIR__, 2) . '/app/Site/LandingSite/pages',
                \dirname(__DIR__, 2) . '/app/Site/AppSite/pages',
            ],
            $registry->pagesDirectories(),
        );
    }

    public function testActiveSiteProvidesRoutes(): void
    {
        $routes = new RouteConfigurator();
        $this->registry(LandingSite::class)->configureRoutes($routes);

        self::assertNotNull($routes->build()->get('home'));
        self::assertNotNull($routes->build()->get('about'));
    }

    public function testCircularInheritanceIsRejected(): void
    {
        $registry = $this->registry(CircularSite::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Цикл в наследовании сайта');
        $registry->lineage();
    }

    /** @param class-string<Site> $siteClass */
    private function registry(string $siteClass): SiteRegistry
    {
        return new SiteRegistry(new class () implements ContainerInterface {
            public function get(string $id): object
            {
                return new $id();
            }

            public function has(string $id): bool
            {
                return class_exists($id);
            }
        }, $siteClass);
    }
}

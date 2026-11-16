<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Admin;

use App\Module\CoreModule;
use Bitrix30\Admin\ModuleRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ModuleRegistryTest extends TestCase
{
    public function testReturnsConfiguredModulesFromContainer(): void
    {
        $registry = new ModuleRegistry($this->container(), [CoreModule::class]);

        $modules = $registry->all();

        self::assertCount(1, $modules);
        self::assertSame('core', $modules[0]->key());
        self::assertSame('Core Module', $modules[0]->name());
    }

    public function testRejectsInvalidModuleClass(): void
    {
        $registry = new ModuleRegistry($this->container(), [\stdClass::class]);

        $this->expectException(\LogicException::class);
        $registry->all();
    }

    private function container(): ContainerInterface
    {
        return new class () implements ContainerInterface {
            public function get(string $id): object
            {
                return new $id();
            }

            public function has(string $id): bool
            {
                return class_exists($id);
            }
        };
    }
}

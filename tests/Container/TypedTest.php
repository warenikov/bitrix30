<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Container;

use Bitrix30\Container\Typed;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class TypedTest extends TestCase
{
    public function testReturnsTypedValues(): void
    {
        $container = $this->container([
            \ArrayObject::class => new \ArrayObject(),
            'dir' => '/tmp',
            'debug' => true,
        ]);

        self::assertInstanceOf(\ArrayObject::class, Typed::service($container, \ArrayObject::class));
        self::assertSame('/tmp', Typed::string($container, 'dir'));
        self::assertTrue(Typed::bool($container, 'debug'));
    }

    public function testThrowsOnWrongTypes(): void
    {
        $container = $this->container(['dir' => 42]);

        $this->expectException(\RuntimeException::class);
        Typed::string($container, 'dir');
    }

    public function testThrowsOnWrongService(): void
    {
        $container = $this->container([\ArrayObject::class => new \stdClass()]);

        $this->expectException(\RuntimeException::class);
        Typed::service($container, \ArrayObject::class);
    }

    /** @param array<string, mixed> $values */
    private function container(array $values): ContainerInterface
    {
        return new class ($values) implements ContainerInterface {
            /** @param array<string, mixed> $values */
            public function __construct(private readonly array $values)
            {
            }

            public function get(string $id): mixed
            {
                return $this->values[$id] ?? throw new \RuntimeException(sprintf('Нет записи %s', $id));
            }

            public function has(string $id): bool
            {
                return isset($this->values[$id]);
            }
        };
    }
}

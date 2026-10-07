<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Component;

use Bitrix30\Component\ComponentRegistry;
use Bitrix30\Component\ComponentRenderer;
use Bitrix30\Component\ParamsMapper;
use Bitrix30\Tests\Component\Fixture\Greeting\Greeting;
use Bitrix30\Tests\Component\Fixture\Greeting\GreetingParams;
use Bitrix30\View\StackRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ComponentRendererTest extends TestCase
{
    public function testRendersComponentWithTypedParams(): void
    {
        $stacks = new StackRegistry();
        $renderer = $this->renderer($stacks);

        $html = $renderer->render('greeting', ['name' => 'Мир']);

        self::assertSame('<p>Привет, Мир!</p>', $html);
    }

    public function testPushesComponentAssetsOnce(): void
    {
        $stacks = new StackRegistry();
        $renderer = $this->renderer($stacks);

        $renderer->render('greeting', ['name' => 'А']);
        $renderer->render('greeting', ['name' => 'Б']);

        $head = $stacks->resolve($stacks->placeholder('head'));
        self::assertSame(1, substr_count($head, '<style>'), 'CSS компонента должен попасть в стек один раз');
        self::assertStringContainsString('.greeting', $head);
    }

    public function testUnknownParamFailsLoudlyAndNamesValidOnes(): void
    {
        $renderer = $this->renderer(new StackRegistry());

        try {
            $renderer->render('greeting', ['nmae' => 'опечатка']);
            self::fail('Ожидали LogicException');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('nmae', $exception->getMessage());
            self::assertStringContainsString('name', $exception->getMessage());
        }
    }

    public function testMissingRequiredParamFailsLoudly(): void
    {
        $renderer = $this->renderer(new StackRegistry());

        try {
            $renderer->render('greeting');
            self::fail('Ожидали LogicException');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('name', $exception->getMessage());
        }
    }

    public function testUnknownComponentFailsWithKnownNames(): void
    {
        $renderer = $this->renderer(new StackRegistry());

        try {
            $renderer->render('missing');
            self::fail('Ожидали LogicException');
        } catch (\LogicException $exception) {
            self::assertStringContainsString('greeting', $exception->getMessage());
        }
    }

    private function renderer(StackRegistry $stacks): ComponentRenderer
    {
        $registry = new ComponentRegistry(['greeting' => Greeting::class]);

        $container = new class () implements ContainerInterface {
            public function get(string $id): object
            {
                return new Greeting();
            }

            public function has(string $id): bool
            {
                return $id === Greeting::class;
            }
        };

        return new ComponentRenderer($container, $registry, new ParamsMapper(), $stacks);
    }
}

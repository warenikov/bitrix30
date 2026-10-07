<?php

declare(strict_types=1);

namespace Bitrix30\Tests\View;

use Bitrix30\Component\ComponentRegistry;
use Bitrix30\Component\ComponentRenderer;
use Bitrix30\Component\ParamsMapper;
use Bitrix30\Tests\Component\Fixture\Greeting\Greeting;
use Bitrix30\View\StackRegistry;
use Bitrix30\View\Twig\Bitrix30Extension;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

final class TwigIntegrationTest extends TestCase
{
    public function testPushIsDeferredUntilResolve(): void
    {
        $stacks = new StackRegistry();
        $twig = $this->twig($stacks, [
            'page' => "<head>{{ stack('head') }}</head>{% push 'head' %}<style>.a{}</style>{% endpush %}<body></body>",
        ]);

        $html = $stacks->resolve($twig->render('page'));

        self::assertSame('<head><style>.a{}</style></head><body></body>', $html);
    }

    public function testPushPrependPutsContentFirst(): void
    {
        $stacks = new StackRegistry();
        $twig = $this->twig($stacks, [
            'page' => "{{ stack('h') }}{% push 'h' %}B{% endpush %}{% push 'h' prepend %}A{% endpush %}",
        ]);

        self::assertSame("A\nB", $stacks->resolve($twig->render('page')));
    }

    public function testPushOnceDeduplicatesAcrossRenders(): void
    {
        $stacks = new StackRegistry();
        $twig = $this->twig($stacks, [
            'widget' => "{% push 'scripts' once %}<script>init()</script>{% endpush %}x",
            'page' => "{{ include('widget') }}{{ include('widget') }}{{ stack('scripts') }}",
        ]);

        $html = $stacks->resolve($twig->render('page'));

        self::assertSame(1, substr_count($html, '<script>init()</script>'));
    }

    public function testComponentFunctionRendersUnescaped(): void
    {
        $stacks = new StackRegistry();
        $twig = $this->twig($stacks, [
            'page' => "{{ component('greeting', {name: 'мир'}) }}",
        ]);

        self::assertSame('<p>Привет, мир!</p>', $twig->render('page'));
    }

    public function testAutoescapeStaysOnForVariables(): void
    {
        $stacks = new StackRegistry();
        $twig = $this->twig($stacks, ['page' => '{{ v }}']);

        self::assertSame('&lt;b&gt;', $twig->render('page', ['v' => '<b>']));
    }

    /** @param array<string, string> $templates */
    private function twig(StackRegistry $stacks, array $templates): Environment
    {
        $components = new ComponentRenderer(
            new class () implements ContainerInterface {
                public function get(string $id): object
                {
                    return new Greeting();
                }

                public function has(string $id): bool
                {
                    return true;
                }
            },
            new ComponentRegistry(['greeting' => Greeting::class]),
            new ParamsMapper(),
            $stacks,
        );

        $services = [StackRegistry::class => $stacks, ComponentRenderer::class => $components];

        $twig = new Environment(new ArrayLoader($templates), [
            'cache' => false,
            'strict_variables' => true,
            'use_yield' => true,
        ]);
        $twig->addRuntimeLoader(new ContainerRuntimeLoader(new class ($services) implements ContainerInterface {
            /** @param array<string, object> $services */
            public function __construct(private readonly array $services)
            {
            }

            public function get(string $id): object
            {
                return $this->services[$id] ?? throw new \RuntimeException(sprintf('Нет сервиса %s', $id));
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        }));
        $twig->addExtension(new Bitrix30Extension());

        return $twig;
    }
}

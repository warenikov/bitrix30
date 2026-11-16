<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Site;

use App\Site\LandingSite;
use Bitrix30\Site\SiteRegistry;
use Bitrix30\View\PageRenderer;
use Bitrix30\View\StackRegistry;
use Bitrix30\View\Twig\Bitrix30Extension;
use Bitrix30\View\TwigRenderer;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\ContainerRuntimeLoader;

final class SiteRenderingTest extends TestCase
{
    public function testChildTemplateOverridesParentTemplate(): void
    {
        $twig = $this->twig(new StackRegistry());

        $html = $twig->getLoader()->getSourceContext('@site/home.twig')->getCode();

        self::assertStringContainsString('Дочерний сайт переопределил главную страницу', $html);
        self::assertStringNotContainsString('Базовый сайт', $html);
    }

    public function testInheritedPageIsWrappedInLayout(): void
    {
        $stacks = new StackRegistry();
        $pages = new PageRenderer(
            new TwigRenderer($this->twig($stacks)),
            $stacks,
            new Psr17Factory(),
            $this->sites(),
        );

        $response = $pages->render('@site/about.twig', ['title' => 'О фреймворке']);
        $html = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<title>О фреймворке</title>', $html);
        self::assertStringContainsString('<header>bitrix30</header>', $html);
        self::assertStringContainsString('Эта страница унаследована дочерним сайтом без изменений.', $html);
        self::assertStringContainsString('<footer>Сайты, наследование и layouts</footer>', $html);
    }

    private function twig(StackRegistry $stacks): Environment
    {
        $sites = $this->sites();
        $loader = new FilesystemLoader();
        foreach ($sites->pagesDirectories() as $directory) {
            $loader->addPath($directory, 'site');
        }
        $loader->addPath($sites->layout()->templatesDirectory(), 'layout');

        $twig = new Environment($loader, [
            'cache' => false,
            'strict_variables' => true,
            'use_yield' => true,
        ]);
        $twig->addRuntimeLoader(new ContainerRuntimeLoader(new class ($stacks) implements ContainerInterface {
            public function __construct(private readonly StackRegistry $stacks)
            {
            }

            public function get(string $id): object
            {
                return $this->stacks;
            }

            public function has(string $id): bool
            {
                return $id === StackRegistry::class;
            }
        }));
        $twig->addExtension(new Bitrix30Extension());

        return $twig;
    }

    private function sites(): SiteRegistry
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
        }, LandingSite::class);
    }
}

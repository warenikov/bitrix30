<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Component;

use App\Component\Hero\Hero;
use App\Component\Hero\HeroParams;
use Bitrix30\View\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class HeroTest extends TestCase
{
    public function testRendersTypedParameters(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2) . '/app/Component', 'components');
        $hero = new Hero(new TwigRenderer(new Environment($loader, [
            'cache' => false,
            'strict_variables' => true,
        ])));

        $html = $hero->render(new HeroParams('Laravel, только БУС', 'Фреймворк <без> магии'));

        self::assertStringContainsString('Laravel, только БУС', $html);
        self::assertStringContainsString('Фреймворк &lt;без&gt; магии', $html);
        self::assertStringContainsString('Этап 1 · MVP', $html);
        self::assertStringContainsString('LandingSite.php', $html);
    }
}

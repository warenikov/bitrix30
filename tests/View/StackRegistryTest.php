<?php

declare(strict_types=1);

namespace Bitrix30\Tests\View;

use Bitrix30\View\StackRegistry;
use PHPUnit\Framework\TestCase;

final class StackRegistryTest extends TestCase
{
    public function testResolveReplacesPlaceholderWithPushedContent(): void
    {
        $registry = new StackRegistry();
        $html = '<head>' . $registry->placeholder('head') . '</head>';

        // push ПОСЛЕ того, как плейсхолдер уже «отрендерен» — суть deferred
        $registry->push('head', '<link rel="stylesheet" href="/a.css">');
        $registry->push('head', '<link rel="stylesheet" href="/b.css">');

        self::assertSame(
            '<head><link rel="stylesheet" href="/a.css">' . "\n" . '<link rel="stylesheet" href="/b.css"></head>',
            $registry->resolve($html),
        );
    }

    public function testPrependPutsContentFirst(): void
    {
        $registry = new StackRegistry();
        $registry->push('head', 'second');
        $registry->push('head', 'first', prepend: true);

        self::assertSame('first' . "\n" . 'second', $registry->resolve($registry->placeholder('head')));
    }

    public function testEmptyStackPlaceholderDisappears(): void
    {
        $registry = new StackRegistry();
        $html = '<head>' . $registry->placeholder('head') . '</head>';

        self::assertSame('<head></head>', $registry->resolve($html));
    }

    public function testPushOnceDeduplicatesById(): void
    {
        $registry = new StackRegistry();
        $registry->pushOnce('head', 'news.css', '<link href="/news.css">');
        $registry->pushOnce('head', 'news.css', '<link href="/news.css">');

        self::assertSame('<link href="/news.css">', $registry->resolve($registry->placeholder('head')));
    }

    public function testForeignMarkersAreNotTouched(): void
    {
        $registry = new StackRegistry();
        $other = new StackRegistry();
        $html = $other->placeholder('head');

        // чужой токен — не наш маркер, трогать нельзя
        self::assertSame($html, $registry->resolve($html));
    }
}

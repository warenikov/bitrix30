<?php

declare(strict_types=1);

namespace Bitrix30\Site;

use Bitrix30\Routing\RouteConfigurator;

/** Базовый класс сайта: страницы, роуты и layout. */
abstract class Site
{
    /**
     * Родительский сайт; null означает верхний уровень иерархии.
     *
     * @return class-string<Site>|null
     */
    public static function parentSite(): ?string
    {
        return null;
    }

    /** Layout активного сайта; унаследованный метод сохраняет родительский layout. */
    abstract public function layout(): Layout;

    /** Роуты сайта; дочерний класс может переопределить этот метод. */
    public function routes(RouteConfigurator $routes): void
    {
    }

    /** Папка страниц конкретного класса сайта. */
    final public function pagesDirectory(): string
    {
        $file = (new \ReflectionClass($this))->getFileName();
        if ($file === false) {
            throw new \RuntimeException(sprintf('Не удалось определить файл сайта %s.', static::class));
        }

        return \dirname($file) . '/' . (new \ReflectionClass($this))->getShortName() . '/pages';
    }
}

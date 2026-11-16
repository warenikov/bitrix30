<?php

declare(strict_types=1);

namespace Bitrix30\Site;

/** Layout оборачивает HTML страницы и предоставляет общие данные шаблона. */
abstract class Layout
{
    /** Шаблон из пространства @layout. */
    public function template(): string
    {
        return '@layout/main.twig';
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [];
    }

    /** Папка шаблонов конкретного класса layout. */
    final public function templatesDirectory(): string
    {
        $file = (new \ReflectionClass($this))->getFileName();
        if ($file === false) {
            throw new \RuntimeException(sprintf('Не удалось определить файл layout %s.', static::class));
        }

        return \dirname($file) . '/templates';
    }
}

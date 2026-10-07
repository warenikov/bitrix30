<?php

declare(strict_types=1);

namespace Bitrix30\Component;

/**
 * Помогает компоненту адресовать собственные шаблоны, не зашивая
 * имя своей папки строкой: путь выводится из расположения класса —
 * как и ComponentRegistry::dirOf(). Переименовал папку — ничего не сломал.
 */
trait ComponentTemplates
{
    /** `$this->template('list.twig')` → `@components/<ПапкаКомпонента>/templates/list.twig` */
    private function template(string $file): string
    {
        $path = (new \ReflectionClass(static::class))->getFileName();
        if ($path === false) {
            throw new \LogicException(sprintf('У класса %s нет файла на диске.', static::class));
        }

        return '@components/' . basename(\dirname($path)) . '/templates/' . $file;
    }
}

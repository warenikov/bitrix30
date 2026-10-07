<?php

declare(strict_types=1);

namespace Bitrix30\Component;

/**
 * Реестр компонентов: имя → класс. Карта задаётся явно
 * (`config/components.php` приложения) — без магического сканирования.
 *
 * Папка компонента выводится из расположения его класса: рядом с ним
 * лежат `templates/` и `resources/`.
 */
final class ComponentRegistry
{
    /** @var array<string, string> имя → путь к папке (лениво) */
    private array $dirs = [];

    /** @param array<string, class-string<ComponentInterface>> $map */
    public function __construct(
        private readonly array $map,
    ) {
    }

    /** @return class-string<ComponentInterface> */
    public function classOf(string $name): string
    {
        return $this->map[$name]
            ?? throw new \LogicException(sprintf(
                'Компонент «%s» не зарегистрирован. Известные: %s.',
                $name,
                $this->map === [] ? '(нет)' : implode(', ', array_keys($this->map)),
            ));
    }

    /** Папка компонента — для шаблонов и ассетов. */
    public function dirOf(string $name): string
    {
        if (isset($this->dirs[$name])) {
            return $this->dirs[$name];
        }

        $file = (new \ReflectionClass($this->classOf($name)))->getFileName();
        if ($file === false) {
            throw new \LogicException(sprintf('У класса компонента «%s» нет файла на диске.', $name));
        }

        return $this->dirs[$name] = \dirname($file);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->map);
    }
}

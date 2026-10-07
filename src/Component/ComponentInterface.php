<?php

declare(strict_types=1);

namespace Bitrix30\Component;

/**
 * Компонент — переиспользуемый блок страницы: класс-контроллер в папке
 * рядом с `templates/` и `resources/`. Зависимости — через конструктор
 * (autowiring), параметры — типизированный readonly DTO, не массив.
 */
interface ComponentInterface
{
    /**
     * Класс DTO параметров или null, если компонент без параметров.
     * DTO — readonly-класс; значения из `component('имя', {...})`
     * передаются в его конструктор как именованные аргументы.
     *
     * @return class-string|null
     */
    public static function paramsClass(): ?string;

    /** Рендерит компонент в HTML. `$params` — экземпляр paramsClass() или null. */
    public function render(?object $params): string;
}

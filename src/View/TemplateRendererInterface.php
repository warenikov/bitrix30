<?php

declare(strict_types=1);

namespace Bitrix30\View;

/**
 * Рендер шаблона в строку. Единственная точка, через которую ядро
 * говорит с шаблонизатором: компоненты и страницы зависят от этого
 * интерфейса, а не от Twig напрямую.
 */
interface TemplateRendererInterface
{
    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = []): string;
}

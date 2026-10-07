<?php

declare(strict_types=1);

namespace Bitrix30\View;

use Twig\Environment;

/** Реализация рендера шаблонов на Twig ([[ADR-0006]]). */
final class TwigRenderer implements TemplateRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}

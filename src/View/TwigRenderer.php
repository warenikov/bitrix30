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
        // неймспейс @components смотрит на папки компонентов целиком;
        // без этой проверки туда можно адресовать и PHP-исходник
        if (!str_ends_with($template, '.twig')) {
            throw new \LogicException(sprintf('Шаблон должен быть .twig-файлом, запрошен «%s».', $template));
        }

        return $this->twig->render($template, $context);
    }
}

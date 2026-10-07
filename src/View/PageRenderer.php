<?php

declare(strict_types=1);

namespace Bitrix30\View;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Рендер целой страницы: шаблон → HTML → подстановка стеков → Response.
 * Разрешение стеков происходит здесь и только здесь — когда страница
 * отрендерена целиком и все `{% push %}` уже сработали.
 */
final class PageRenderer
{
    public function __construct(
        private readonly TemplateRendererInterface $templates,
        private readonly StackRegistry $stacks,
        private readonly ResponseFactoryInterface $responses,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = [], int $status = 200): ResponseInterface
    {
        try {
            $html = $this->stacks->resolve($this->templates->render($template, $context));
        } finally {
            $this->stacks->reset();
        }

        $response = $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }
}

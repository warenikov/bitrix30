<?php

declare(strict_types=1);

namespace Bitrix30\View;

use Bitrix30\Site\SiteRegistry;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Markup;

/**
 * Рендер целой страницы: шаблон → layout → подстановка стеков → Response.
 * Разрешение стеков происходит здесь и только здесь — когда страница
 * отрендерена целиком и все `{% push %}` уже сработали.
 */
final class PageRenderer
{
    public function __construct(
        private readonly TemplateRendererInterface $templates,
        private readonly StackRegistry $stacks,
        private readonly ResponseFactoryInterface $responses,
        private readonly SiteRegistry $sites,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = [], int $status = 200): ResponseInterface
    {
        try {
            $content = $this->templates->render($template, $context);
            $layout = $this->sites->layout();
            $html = $this->templates->render($layout->template(), array_merge($layout->context(), [
                'content' => new Markup($content, 'UTF-8'),
                'page' => $context,
            ]));
            $html = $this->stacks->resolve($html);
        } finally {
            $this->stacks->reset();
        }

        $response = $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }
}

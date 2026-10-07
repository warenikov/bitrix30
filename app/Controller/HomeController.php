<?php

declare(strict_types=1);

namespace App\Controller;

use Bitrix30\View\PageRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Заглавная страница демо-приложения: Twig-шаблон с компонентами. */
final class HomeController
{
    public function __construct(
        private readonly PageRenderer $pages,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pages->render('home.twig');
    }
}

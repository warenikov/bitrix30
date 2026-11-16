<?php

declare(strict_types=1);

namespace App\Controller;

use Bitrix30\View\PageRenderer;
use Psr\Http\Message\ResponseInterface;

final class AboutController
{
    public function __construct(
        private readonly PageRenderer $pages,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        return $this->pages->render('@site/about.twig', ['title' => 'О фреймворке']);
    }
}

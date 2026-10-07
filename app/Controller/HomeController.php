<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Временная заглавная страница демо-приложения: доказывает, что конвейер
 * запрос → роутер → контроллер → ответ работает. Уйдёт, когда появятся
 * Twig и компоненты (следующие подсистемы этапа 1).
 */
final class HomeController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write(
            '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
            . '<title>bitrix30</title></head>'
            . '<body><h1>bitrix30</h1><p>HTTP-ядро этапа 1 живо: PSR-7/15, DI, роутинг.</p></body></html>',
        );

        return $response;
    }
}

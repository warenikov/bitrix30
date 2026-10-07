<?php

declare(strict_types=1);

namespace Bitrix30\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Режим обслуживания: пока существует файл-флаг, все запросы получают 503.
 * Включение — `touch var/maintenance.flag`, выключение — удаление файла.
 */
final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $flagFile,
        private readonly int $retryAfterSeconds = 60,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!is_file($this->flagFile)) {
            return $handler->handle($request);
        }

        $response = $this->responseFactory->createResponse(503)
            ->withHeader('Retry-After', (string) $this->retryAfterSeconds)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write('<h1>Сайт на обслуживании</h1><p>Зайдите чуть позже.</p>');

        return $response;
    }
}

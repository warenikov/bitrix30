<?php

declare(strict_types=1);

namespace Bitrix30\Http\Middleware;

use Bitrix30\Http\Exception\HttpException;
use Bitrix30\Http\Middleware\RequestIdMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Последний рубеж: любое исключение ниже по конвейеру превращается в HTTP-ответ.
 *
 * HttpException отдаёт свой статус и заголовки, остальное — 500.
 * 5xx логируются как error, 4xx — как notice. В debug-режиме в ответ
 * попадает класс исключения и трейс, в prod — только статус и request id.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly bool $debug,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $exception) {
            return $this->renderException($exception, $request);
        }
    }

    private function renderException(\Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $status = $exception instanceof HttpException ? $exception->getStatusCode() : 500;
        $requestId = $request->getAttribute(RequestIdMiddleware::ATTRIBUTE);
        $requestId = \is_string($requestId) ? $requestId : null;

        $this->logger->log(
            $status >= 500 ? 'error' : 'notice',
            $exception->getMessage() !== '' ? $exception->getMessage() : $exception::class,
            [
                'exception' => $exception,
                'status' => $status,
                'request_id' => $requestId,
                'method' => $request->getMethod(),
                'path' => $request->getUri()->getPath(),
            ],
        );

        $response = $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        if ($exception instanceof HttpException) {
            foreach ($exception->getHeaders() as $name => $value) {
                $response = $response->withHeader($name, $value);
            }
        }

        $response->getBody()->write($this->renderBody($exception, $status, $requestId));

        return $response;
    }

    private function renderBody(\Throwable $exception, int $status, ?string $requestId): string
    {
        $title = sprintf('Ошибка %d', $status);
        $idLine = $requestId !== null
            ? sprintf('<p>Request ID: <code>%s</code></p>', htmlspecialchars($requestId, \ENT_QUOTES))
            : '';

        if (!$this->debug) {
            return sprintf('<h1>%s</h1>%s', $title, $idLine);
        }

        return sprintf(
            '<h1>%s</h1>%s<h2>%s: %s</h2><pre>%s</pre>',
            $title,
            $idLine,
            htmlspecialchars($exception::class, \ENT_QUOTES),
            htmlspecialchars($exception->getMessage(), \ENT_QUOTES),
            htmlspecialchars($exception->getTraceAsString(), \ENT_QUOTES),
        );
    }
}

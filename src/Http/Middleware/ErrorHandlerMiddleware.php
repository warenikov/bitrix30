<?php

declare(strict_types=1);

namespace Bitrix30\Http\Middleware;

use Bitrix30\Http\Error\ErrorPageRendererInterface;
use Bitrix30\Http\Exception\HttpException;
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
 * 5xx логируются как error, 4xx — как notice. HTML зависит от окружения:
 * Whoops в dev, только статус и request id в prod.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
        private readonly ErrorPageRendererInterface $errorPages,
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

        $response->getBody()->write($this->errorPages->render($exception, $status, $requestId));

        return $response;
    }
}

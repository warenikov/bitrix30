<?php

declare(strict_types=1);

namespace Bitrix30\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Тонкий PSR-15 конвейер: прогоняет запрос через цепочку middleware,
 * в конце передаёт его финальному обработчику (fallback).
 *
 * Неизменяемый: каждый шаг цепочки — новый экземпляр со сдвинутым индексом,
 * поэтому один и тот же конвейер можно безопасно использовать повторно.
 */
final class MiddlewarePipeline implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware цепочка в порядке выполнения
     * @param RequestHandlerInterface   $fallback   обработчик, если цепочка исчерпана
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $fallback,
        private readonly int $index = 0,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middleware[$this->index])) {
            return $this->fallback->handle($request);
        }

        $next = new self($this->middleware, $this->fallback, $this->index + 1);

        return $this->middleware[$this->index]->process($request, $next);
    }
}

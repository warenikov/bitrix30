<?php

declare(strict_types=1);

namespace Bitrix30\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * HTTP-ядро: прогоняет PSR-7 запрос через middleware-конвейер
 * до диспетчера контроллеров и отдаёт PSR-7 ответ.
 *
 * `terminate()` вызывается после отправки ответа клиенту — для работы,
 * которая не должна задерживать ответ (отложенные логи, метрики).
 */
final class Kernel
{
    /**
     * @param list<MiddlewareInterface>                                        $middleware  в порядке выполнения
     * @param RequestHandlerInterface                                          $dispatcher  финальный обработчик
     * @param list<callable(ServerRequestInterface, ResponseInterface): void>  $terminators послеответные задачи
     */
    public function __construct(
        private readonly array $middleware,
        private readonly RequestHandlerInterface $dispatcher,
        private readonly array $terminators = [],
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new MiddlewarePipeline($this->middleware, $this->dispatcher))->handle($request);
    }

    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->terminators as $terminator) {
            $terminator($request, $response);
        }
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Присваивает каждому запросу уникальный id: кладёт его в атрибут запроса
 * и в заголовок X-Request-Id ответа. По нему связываются логи, трейсы
 * и страница ошибки одного запроса.
 *
 * Id всегда генерируется заново — входящий X-Request-Id не доверяем,
 * пока нет механизма доверенных прокси.
 */
final class RequestIdMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'request_id';

    public const HEADER = 'X-Request-Id';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = bin2hex(random_bytes(8));

        $response = $handler->handle($request->withAttribute(self::ATTRIBUTE, $requestId));

        return $response->withHeader(self::HEADER, $requestId);
    }
}

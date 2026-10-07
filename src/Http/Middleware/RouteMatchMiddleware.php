<?php

declare(strict_types=1);

namespace Bitrix30\Http\Middleware;

use Bitrix30\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Сопоставляет запрос с роутом и кладёт результат в атрибуты запроса:
 * `_route` (имя), `_handler` (обработчик) и каждый плейсхолдер отдельным
 * атрибутом. Дальше по конвейеру запрос идёт уже «знающим свой роут».
 */
final class RouteMatchMiddleware implements MiddlewareInterface
{
    public const ROUTE_ATTRIBUTE = '_route';

    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->router->match($request) as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $handler->handle($request);
    }
}

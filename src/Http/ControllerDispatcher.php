<?php

declare(strict_types=1);

namespace Bitrix30\Http;

use Bitrix30\Http\Exception\NotFoundException;
use Bitrix30\Routing\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Финальный обработчик конвейера: берёт обработчик из атрибутов запроса
 * (его туда положил RouteMatchMiddleware), достаёт контроллер из контейнера
 * и вызывает экшен.
 *
 * Контракт экшена: `(ServerRequestInterface): ResponseInterface`;
 * параметры роута контроллер читает из атрибутов запроса.
 */
final class ControllerDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $request->getAttribute(Router::HANDLER);

        if (!\is_string($handler) && !\is_array($handler)) {
            throw new NotFoundException('Запрос дошёл до диспетчера без обработчика: конвейер без RouteMatchMiddleware?');
        }

        [$class, $method] = \is_array($handler) ? $handler : [$handler, '__invoke'];

        if (!\is_string($class) || !\is_string($method)) {
            throw new \LogicException('Обработчик роута должен быть class-string или [class-string, string].');
        }

        $controller = $this->container->get($class);
        $callable = [$controller, $method];

        if (!\is_object($controller) || !\is_callable($callable)) {
            throw new \LogicException(sprintf('У контроллера %s нет метода %s().', $class, $method));
        }

        $response = \Closure::fromCallable($callable)($request);

        if (!$response instanceof ResponseInterface) {
            throw new \LogicException(sprintf('Экшен %s::%s() обязан вернуть PSR-7 Response.', $class, $method));
        }

        return $response;
    }
}

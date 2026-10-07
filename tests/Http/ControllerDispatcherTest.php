<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Http;

use Bitrix30\Http\ControllerDispatcher;
use Bitrix30\Routing\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ControllerDispatcherTest extends TestCase
{
    public function testInvokesInvokableController(): void
    {
        $controller = new class () {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                return (new Psr17Factory())->createResponse(200);
            }
        };

        $dispatcher = new ControllerDispatcher($this->container([$controller::class => $controller]));
        $request = (new ServerRequest('GET', '/'))->withAttribute(Router::HANDLER, $controller::class);

        self::assertSame(200, $dispatcher->handle($request)->getStatusCode());
    }

    public function testInvokesNamedAction(): void
    {
        $controller = new class () {
            public function show(ServerRequestInterface $request): ResponseInterface
            {
                return (new Psr17Factory())->createResponse(200);
            }
        };

        $dispatcher = new ControllerDispatcher($this->container([$controller::class => $controller]));
        $request = (new ServerRequest('GET', '/'))
            ->withAttribute(Router::HANDLER, [$controller::class, 'show']);

        self::assertSame(200, $dispatcher->handle($request)->getStatusCode());
    }

    public function testRejectsActionWithoutResponse(): void
    {
        $controller = new class () {
            public function __invoke(ServerRequestInterface $request): string
            {
                return 'не Response';
            }
        };

        $dispatcher = new ControllerDispatcher($this->container([$controller::class => $controller]));
        $request = (new ServerRequest('GET', '/'))->withAttribute(Router::HANDLER, $controller::class);

        $this->expectException(\LogicException::class);
        $dispatcher->handle($request);
    }

    /** @param array<string, object> $services */
    private function container(array $services): ContainerInterface
    {
        return new class ($services) implements ContainerInterface {
            /** @param array<string, object> $services */
            public function __construct(private readonly array $services)
            {
            }

            public function get(string $id): object
            {
                return $this->services[$id] ?? throw new \RuntimeException(sprintf('Нет сервиса %s', $id));
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}

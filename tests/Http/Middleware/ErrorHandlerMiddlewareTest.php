<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Http\Middleware;

use Bitrix30\Http\Exception\MethodNotAllowedException;
use Bitrix30\Http\Middleware\ErrorHandlerMiddleware;
use Bitrix30\Http\Middleware\RequestIdMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

final class ErrorHandlerMiddlewareTest extends TestCase
{
    public function testPassesThroughSuccessfulResponse(): void
    {
        $middleware = new ErrorHandlerMiddleware(new Psr17Factory(), new NullLogger(), false);

        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->handler(static fn (): ResponseInterface => (new Psr17Factory())->createResponse(201)),
        );

        self::assertSame(201, $response->getStatusCode());
    }

    public function testMapsHttpExceptionToStatusAndHeaders(): void
    {
        $middleware = new ErrorHandlerMiddleware(new Psr17Factory(), new NullLogger(), false);

        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->handler(static function (): ResponseInterface {
                throw new MethodNotAllowedException(['POST', 'PUT']);
            }),
        );

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('POST, PUT', $response->getHeaderLine('Allow'));
    }

    public function testGenericExceptionBecomes500WithoutDetailsInProd(): void
    {
        $middleware = new ErrorHandlerMiddleware(new Psr17Factory(), new NullLogger(), false);

        $response = $middleware->process(
            new ServerRequest('GET', '/'),
            $this->handler(static function (): ResponseInterface {
                throw new \RuntimeException('внутренний секретный текст');
            }),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringNotContainsString('внутренний секретный текст', (string) $response->getBody());
    }

    public function testDebugModeShowsExceptionDetailsAndRequestId(): void
    {
        $middleware = new ErrorHandlerMiddleware(new Psr17Factory(), new NullLogger(), true);

        $request = (new ServerRequest('GET', '/'))->withAttribute(RequestIdMiddleware::ATTRIBUTE, 'abc123');
        $response = $middleware->process(
            $request,
            $this->handler(static function (): ResponseInterface {
                throw new \RuntimeException('что-то упало');
            }),
        );

        $body = (string) $response->getBody();
        self::assertStringContainsString('RuntimeException', $body);
        self::assertStringContainsString('что-то упало', $body);
        self::assertStringContainsString('abc123', $body);
    }

    /** @param \Closure(): ResponseInterface $respond */
    private function handler(\Closure $respond): RequestHandlerInterface
    {
        return new class ($respond) implements RequestHandlerInterface {
            /** @param \Closure(): ResponseInterface $respond */
            public function __construct(private readonly \Closure $respond)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->respond)();
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Http\Middleware;

use Bitrix30\Http\Middleware\RequestIdMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestIdMiddlewareTest extends TestCase
{
    public function testSetsAttributeAndResponseHeader(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public mixed $seenAttribute = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->seenAttribute = $request->getAttribute(RequestIdMiddleware::ATTRIBUTE);

                return (new Psr17Factory())->createResponse(200);
            }
        };

        $response = (new RequestIdMiddleware())->process(new ServerRequest('GET', '/'), $handler);
        $headerValue = $response->getHeaderLine(RequestIdMiddleware::HEADER);

        self::assertIsString($handler->seenAttribute);
        self::assertSame($handler->seenAttribute, $headerValue);
        self::assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $headerValue);
    }

    public function testIgnoresIncomingRequestIdHeader(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Psr17Factory())->createResponse(200);
            }
        };

        $request = (new ServerRequest('GET', '/'))->withHeader(RequestIdMiddleware::HEADER, 'spoofed');
        $response = (new RequestIdMiddleware())->process($request, $handler);

        self::assertNotSame('spoofed', $response->getHeaderLine(RequestIdMiddleware::HEADER));
    }
}

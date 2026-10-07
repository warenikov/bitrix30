<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Http;

use Bitrix30\Http\MiddlewarePipeline;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipelineTest extends TestCase
{
    public function testRunsMiddlewareInOrderAndReachesFallback(): void
    {
        /** @var \ArrayObject<int, string> $trace */
        $trace = new \ArrayObject();

        $middleware = static function (string $label) use ($trace): MiddlewareInterface {
            return new class ($label, $trace) implements MiddlewareInterface {
                /** @param \ArrayObject<int, string> $trace */
                public function __construct(
                    private readonly string $label,
                    private readonly \ArrayObject $trace,
                ) {
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $this->trace->append($this->label . ':before');
                    $response = $handler->handle($request);
                    $this->trace->append($this->label . ':after');

                    return $response;
                }
            };
        };

        $fallback = new class ($trace) implements RequestHandlerInterface {
            /** @param \ArrayObject<int, string> $trace */
            public function __construct(private readonly \ArrayObject $trace)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->trace->append('fallback');

                return (new Psr17Factory())->createResponse(200);
            }
        };

        $pipeline = new MiddlewarePipeline([$middleware('a'), $middleware('b')], $fallback);
        $response = $pipeline->handle(new ServerRequest('GET', '/'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['a:before', 'b:before', 'fallback', 'b:after', 'a:after'], $trace->getArrayCopy());
    }

    public function testPipelineIsReusable(): void
    {
        $fallback = new class () implements RequestHandlerInterface {
            public int $calls = 0;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                ++$this->calls;

                return (new Psr17Factory())->createResponse(204);
            }
        };

        $pipeline = new MiddlewarePipeline([], $fallback);
        $pipeline->handle(new ServerRequest('GET', '/'));
        $pipeline->handle(new ServerRequest('GET', '/'));

        self::assertSame(2, $fallback->calls);
    }
}

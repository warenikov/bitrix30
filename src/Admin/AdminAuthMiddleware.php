<?php

declare(strict_types=1);

namespace Bitrix30\Admin;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Защищает read-only админку HTTP Basic Auth из ENV-конфигурации. */
final class AdminAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AdminAccess $access,
        private readonly ResponseFactoryInterface $responses,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ($path !== '/admin' && !str_starts_with($path, '/admin/')) {
            return $handler->handle($request);
        }

        if (!$this->access->isConfigured()) {
            return $this->error(503, 'Admin is not configured');
        }

        $credentials = $this->credentials($request);
        if ($credentials === null || !$this->access->matches($credentials[0], $credentials[1])) {
            $response = $this->error(401, 'Authentication required');

            return $response->withHeader('WWW-Authenticate', 'Basic realm="bitrix30 admin"');
        }

        return $handler->handle($request);
    }

    /** @return array{string, string}|null */
    private function credentials(ServerRequestInterface $request): ?array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Basic ')) {
            return null;
        }

        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        $parts = explode(':', $decoded, 2);

        return [$parts[0], $parts[1]];
    }

    private function error(int $status, string $message): ResponseInterface
    {
        $response = $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write(sprintf('<h1>%s</h1>', $message));

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Admin;

use Bitrix30\Admin\AdminAccess;
use Bitrix30\Admin\AdminAuthMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminAuthMiddlewareTest extends TestCase
{
    private const EMAIL = 'admin@example.com';

    private string $hash;

    protected function setUp(): void
    {
        $this->hash = password_hash('secret', \PASSWORD_BCRYPT);
    }

    public function testRequiresBasicAuthentication(): void
    {
        $response = $this->middleware()->process(new ServerRequest('GET', '/admin'), $this->handler());

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Basic realm="bitrix30 admin"', $response->getHeaderLine('WWW-Authenticate'));
    }

    public function testRejectsInvalidCredentialsAndMalformedToken(): void
    {
        $wrong = (new ServerRequest('GET', '/admin'))
            ->withHeader('Authorization', 'Basic ' . base64_encode(self::EMAIL . ':wrong'));
        $malformed = (new ServerRequest('GET', '/admin'))->withHeader('Authorization', 'Basic not-base64');
        $withoutColon = (new ServerRequest('GET', '/admin'))
            ->withHeader('Authorization', 'Basic ' . base64_encode('without-colon'));

        self::assertSame(401, $this->middleware()->process($wrong, $this->handler())->getStatusCode());
        self::assertSame(401, $this->middleware()->process($malformed, $this->handler())->getStatusCode());
        self::assertSame(401, $this->middleware()->process($withoutColon, $this->handler())->getStatusCode());
    }

    public function testAllowsValidCredentials(): void
    {
        $request = (new ServerRequest('GET', '/admin'))
            ->withHeader('Authorization', 'Basic ' . base64_encode(self::EMAIL . ':secret'));

        $response = $this->middleware()->process($request, $this->handler());

        self::assertSame(200, $response->getStatusCode());
    }

    public function testReturns503WhenAdminIsNotConfigured(): void
    {
        $middleware = new AdminAuthMiddleware(new AdminAccess('', ''), new Psr17Factory());

        $response = $middleware->process(new ServerRequest('GET', '/admin'), $this->handler());

        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('Admin is not configured', (string) $response->getBody());
    }

    public function testRejectsInvalidPasswordHashAsNotConfigured(): void
    {
        $middleware = new AdminAuthMiddleware(new AdminAccess(self::EMAIL, 'invalid'), new Psr17Factory());

        self::assertSame(503, $middleware->process(new ServerRequest('GET', '/admin'), $this->handler())->getStatusCode());
    }

    public function testDoesNotAffectPublicRoutes(): void
    {
        $response = $this->middleware()->process(new ServerRequest('GET', '/'), $this->handler());

        self::assertSame(200, $response->getStatusCode());
    }

    private function middleware(): AdminAuthMiddleware
    {
        return new AdminAuthMiddleware(new AdminAccess(self::EMAIL, $this->hash), new Psr17Factory());
    }

    private function handler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Psr17Factory())->createResponse(200);
            }
        };
    }
}

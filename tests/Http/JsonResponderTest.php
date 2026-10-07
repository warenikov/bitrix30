<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Http;

use Bitrix30\Http\JsonResponder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class JsonResponderTest extends TestCase
{
    public function testRespondsWithJson(): void
    {
        $responder = new JsonResponder(new Psr17Factory());

        $response = $responder->respond(['ok' => true, 'кто' => 'мир'], 201);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('{"ok":true,"кто":"мир"}', (string) $response->getBody());
    }

    public function testNonEncodableDataThrows(): void
    {
        $responder = new JsonResponder(new Psr17Factory());

        $this->expectException(\JsonException::class);
        $responder->respond(\NAN);
    }
}

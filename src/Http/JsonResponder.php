<?php

declare(strict_types=1);

namespace Bitrix30\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * JSON-ответ для API-экшенов — парный к PageRenderer для HTML.
 * Контроллеру API ничего больше не нужно: инжектируй и возвращай
 * `$this->json->respond($data)`.
 */
final class JsonResponder
{
    public function __construct(
        private readonly ResponseFactoryInterface $responses,
    ) {
    }

    public function respond(mixed $data, int $status = 200): ResponseInterface
    {
        $json = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $response = $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($json);

        return $response;
    }
}

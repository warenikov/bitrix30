<?php

declare(strict_types=1);

namespace App\Controller;

use Bitrix30\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** Демо API-эндпоинта: JSON вместо HTML, тот же конвейер и роутинг. */
final class PingController
{
    public function __construct(
        private readonly JsonResponder $json,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json->respond([
            'ok' => true,
            'framework' => 'bitrix30',
            'time' => date(\DATE_ATOM),
        ]);
    }
}

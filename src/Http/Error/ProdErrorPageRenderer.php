<?php

declare(strict_types=1);

namespace Bitrix30\Http\Error;

/** Публичная страница ошибки без внутренних деталей приложения. */
final class ProdErrorPageRenderer implements ErrorPageRendererInterface
{
    public function render(\Throwable $exception, int $status, ?string $requestId): string
    {
        $idLine = $requestId !== null
            ? sprintf('<p>Request ID: <code>%s</code></p>', htmlspecialchars($requestId, \ENT_QUOTES))
            : '';

        return sprintf('<h1>Ошибка %d</h1>%s', $status, $idLine);
    }
}

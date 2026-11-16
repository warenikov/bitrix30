<?php

declare(strict_types=1);

namespace Bitrix30\Http\Error;

/** HTML страницы ошибки для выбранного окружения. */
interface ErrorPageRendererInterface
{
    public function render(\Throwable $exception, int $status, ?string $requestId): string;
}

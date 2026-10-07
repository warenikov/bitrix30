<?php

declare(strict_types=1);

namespace Bitrix30\Http\Exception;

/**
 * Исключение, сопоставленное HTTP-статусу: обработчик ошибок
 * превращает его в ответ с этим статусом вместо 500.
 */
class HttpException extends \RuntimeException
{
    /**
     * @param array<string, string> $headers дополнительные заголовки ответа (например, Allow для 405)
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly array $headers = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}

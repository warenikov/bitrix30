<?php

declare(strict_types=1);

namespace Bitrix30\Http\Exception;

/** Путь существует, но HTTP-метод не разрешён: 405 с заголовком Allow. */
final class MethodNotAllowedException extends HttpException
{
    /** @param list<string> $allowedMethods */
    public function __construct(array $allowedMethods, string $message = 'Method Not Allowed', ?\Throwable $previous = null)
    {
        parent::__construct(405, $message, ['Allow' => implode(', ', $allowedMethods)], $previous);
    }
}

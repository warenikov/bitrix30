<?php

declare(strict_types=1);

namespace Bitrix30\Http\Exception;

/** Запрошенный путь не сопоставлен ни одному роуту: 404. */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct(404, $message, [], $previous);
    }
}

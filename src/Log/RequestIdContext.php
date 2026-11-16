<?php

declare(strict_types=1);

namespace Bitrix30\Log;

/** Request ID текущего запроса для процессоров логов. */
final class RequestIdContext
{
    private ?string $requestId = null;

    public function set(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function get(): ?string
    {
        return $this->requestId;
    }

    public function reset(): void
    {
        $this->requestId = null;
    }
}

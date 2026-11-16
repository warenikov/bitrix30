<?php

declare(strict_types=1);

namespace Bitrix30\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/** Добавляет request-id в extra каждой записи Monolog. */
final class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestIdContext $context,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $requestId = $this->context->get();
        if ($requestId === null) {
            return $record;
        }

        return $record->with(extra: array_merge($record->extra, ['request_id' => $requestId]));
    }
}

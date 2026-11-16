<?php

declare(strict_types=1);

namespace Bitrix30\Tests\Log;

use Bitrix30\Log\RequestIdContext;
use Bitrix30\Log\RequestIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class RequestIdProcessorTest extends TestCase
{
    public function testAddsCurrentRequestIdToExtra(): void
    {
        $context = new RequestIdContext();
        $context->set('abc123');
        $processor = new RequestIdProcessor($context);

        $record = $processor($this->record());

        self::assertSame('abc123', $record->extra['request_id'] ?? null);
    }

    public function testDoesNotAddEmptyRequestId(): void
    {
        $processor = new RequestIdProcessor(new RequestIdContext());

        self::assertSame([], $processor($this->record())->extra);
    }

    private function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'bitrix30', Level::Info, 'test');
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Http\Error;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/** Подробная Whoops-страница исключения для dev-окружения. */
final class WhoopsErrorPageRenderer implements ErrorPageRendererInterface
{
    public function render(\Throwable $exception, int $status, ?string $requestId): string
    {
        $handler = new PrettyPageHandler();
        $handler->handleUnconditionally(true);
        $handler->setPageTitle(sprintf('Ошибка %d', $status));
        if ($requestId !== null) {
            $handler->addDataTable('Запрос', ['Request ID' => $requestId]);
        }

        $whoops = new Run();
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);
        $whoops->pushHandler($handler);

        return $whoops->handleException($exception);
    }
}

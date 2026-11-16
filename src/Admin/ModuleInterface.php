<?php

declare(strict_types=1);

namespace Bitrix30\Admin;

/** Публичные метаданные модуля для реестра и админки. */
interface ModuleInterface
{
    public function key(): string;

    public function name(): string;

    public function version(): string;
}

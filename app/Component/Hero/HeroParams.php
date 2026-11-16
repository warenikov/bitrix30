<?php

declare(strict_types=1);

namespace App\Component\Hero;

/** Параметры hero-блока визитки. */
final readonly class HeroParams
{
    public function __construct(
        public string $title,
        public string $lead,
        public string $versionLabel = 'Этап 1 · MVP',
    ) {
    }
}

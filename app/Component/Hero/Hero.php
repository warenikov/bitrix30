<?php

declare(strict_types=1);

namespace App\Component\Hero;

use Bitrix30\Component\ComponentInterface;
use Bitrix30\Component\ComponentTemplates;
use Bitrix30\View\TemplateRendererInterface;

final class Hero implements ComponentInterface
{
    use ComponentTemplates;

    public function __construct(
        private readonly TemplateRendererInterface $templates,
    ) {
    }

    public static function paramsClass(): string
    {
        return HeroParams::class;
    }

    public function render(?object $params): string
    {
        \assert($params instanceof HeroParams);

        return $this->templates->render($this->template('hero.twig'), [
            'title' => $params->title,
            'lead' => $params->lead,
            'versionLabel' => $params->versionLabel,
        ]);
    }
}

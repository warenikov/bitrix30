<?php

declare(strict_types=1);

namespace App\Component\Hello;

use Bitrix30\Component\ComponentInterface;
use Bitrix30\Component\ComponentTemplates;
use Bitrix30\View\TemplateRendererInterface;

/**
 * Демо-компонент: приветствие. Показывает полный цикл:
 * типизированные параметры, шаблон в templates/, CSS в resources/
 * (автоматически уезжает в стек head при первом вызове).
 */
final class Hello implements ComponentInterface
{
    use ComponentTemplates;

    public function __construct(
        private readonly TemplateRendererInterface $templates,
    ) {
    }

    public static function paramsClass(): string
    {
        return HelloParams::class;
    }

    public function render(?object $params): string
    {
        \assert($params instanceof HelloParams);

        return $this->templates->render($this->template('greeting.twig'), [
            'name' => $params->name,
            'mark' => $params->exclaim ? '!' : '.',
        ]);
    }
}

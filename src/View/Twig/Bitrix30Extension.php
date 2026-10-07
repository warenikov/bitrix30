<?php

declare(strict_types=1);

namespace Bitrix30\View\Twig;

use Bitrix30\Component\ComponentRenderer;
use Bitrix30\View\StackRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig-расширение фреймворка. Без состояния: все callables указывают
 * на runtime-классы, которые Twig достаёт из DI-контейнера через
 * ContainerRuntimeLoader — по экземпляру на запрос.
 *
 * - `{% push 'head' %}…{% endpush %}` — отложить фрагмент в стек;
 * - `{{ stack('head') }}` — место вывода стека (печатает маркер,
 *   содержимое подставляется после полного рендера страницы);
 * - `{{ component('news.list', {depth: 2}) }}` — вызов компонента.
 */
final class Bitrix30Extension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [new PushTokenParser()];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('stack', [StackRegistry::class, 'placeholder'], ['is_safe' => ['html']]),
            new TwigFunction('component', [ComponentRenderer::class, 'render'], ['is_safe' => ['html']]),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\Component;

use Bitrix30\View\StackRegistry;
use Psr\Container\ContainerInterface;

/**
 * Оркестратор вызова компонента: находит класс по имени, достаёт
 * экземпляр из контейнера, маппит параметры в DTO, рендерит
 * и докидывает ассеты компонента в стеки страницы.
 *
 * Ассеты v1 — инлайн: `resources/style.css` уходит в стек `head`
 * тегом `<style>`, `resources/script.js` — в стек `scripts` тегом
 * `<script>`, с дедупликацией по имени компонента. Публикация ассетов
 * файлами — позже, когда появится их сборка.
 */
final class ComponentRenderer
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ComponentRegistry $registry,
        private readonly ParamsMapper $params,
        private readonly StackRegistry $stacks,
    ) {
    }

    /** @param array<array-key, mixed> $params */
    public function render(string $name, array $params = []): string
    {
        $class = $this->registry->classOf($name);
        $component = $this->container->get($class);

        if (!$component instanceof ComponentInterface) {
            throw new \LogicException(sprintf('Класс компонента «%s» (%s) не реализует ComponentInterface.', $name, $class));
        }

        $dto = $this->params->map($name, $component::paramsClass(), $params);
        $html = $component->render($dto);

        $this->pushAssets($name);

        return $html;
    }

    private function pushAssets(string $name): void
    {
        $dir = $this->registry->dirOf($name);

        $css = @file_get_contents($dir . '/resources/style.css');
        if ($css !== false) {
            $this->stacks->pushOnce('head', 'component:' . $name . ':css', "<style>\n" . $css . '</style>');
        }

        $js = @file_get_contents($dir . '/resources/script.js');
        if ($js !== false) {
            $this->stacks->pushOnce('scripts', 'component:' . $name . ':js', "<script>\n" . $js . '</script>');
        }
    }
}

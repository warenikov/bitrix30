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
    /** @var array<string, true> компоненты, чьи ассеты уже прочитаны в этом запросе */
    private array $assetsPushed = [];

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
        if (isset($this->assetsPushed[$name])) {
            return;
        }
        $this->assetsPushed[$name] = true;

        $dir = $this->registry->dirOf($name);

        $css = $this->readAsset($name, $dir . '/resources/style.css');
        if ($css !== null) {
            $this->stacks->pushOnce('head', 'component:' . $name . ':css', "<style>\n" . $css . '</style>');
        }

        $js = $this->readAsset($name, $dir . '/resources/script.js');
        if ($js !== null) {
            $this->stacks->pushOnce('scripts', 'component:' . $name . ':js', "<script>\n" . $js . '</script>');
        }
    }

    /** Отсутствующий файл — норма; существующий, но нечитаемый — ошибка, а не «молча без стилей». */
    private function readAsset(string $name, string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Ассет компонента «%s» существует, но не читается: %s', $name, $file));
        }

        return $content;
    }
}

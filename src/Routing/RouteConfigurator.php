<?php

declare(strict_types=1);

namespace Bitrix30\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * Fluent-конфигуратор роутов — внешний API роутинга фреймворка.
 *
 * ```php
 * $routes->get('/', HomeController::class)->name('home');
 * $routes->prefix('/news', static function (RouteConfigurator $routes): void {
 *     $routes->get('/{slug}', [NewsController::class, 'show'])->where('slug', '[a-z0-9-]+');
 * });
 * ```
 *
 * Под капотом собирает symfony RouteCollection ([[docs/stack]]): матчинг
 * и компиляцию не пишем сами.
 */
final class RouteConfigurator
{
    /** @var list<RouteDefinition> */
    private array $definitions = [];

    private string $currentPrefix = '';

    /** @param class-string|array{class-string, string} $handler */
    public function get(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, ['GET'], $handler);
    }

    /** @param class-string|array{class-string, string} $handler */
    public function post(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, ['POST'], $handler);
    }

    /** @param class-string|array{class-string, string} $handler */
    public function put(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, ['PUT'], $handler);
    }

    /** @param class-string|array{class-string, string} $handler */
    public function patch(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, ['PATCH'], $handler);
    }

    /** @param class-string|array{class-string, string} $handler */
    public function delete(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, ['DELETE'], $handler);
    }

    /**
     * Роут, отвечающий на любой HTTP-метод.
     *
     * @param class-string|array{class-string, string} $handler
     */
    public function any(string $path, string|array $handler): RouteDefinition
    {
        return $this->add($path, [], $handler);
    }

    /**
     * Группа роутов под общим префиксом пути. Группы вкладываются.
     *
     * @param \Closure(self): void $routes
     */
    public function prefix(string $prefix, \Closure $routes): void
    {
        $previous = $this->currentPrefix;
        $this->currentPrefix = $previous . rtrim($prefix, '/');

        try {
            $routes($this);
        } finally {
            $this->currentPrefix = $previous;
        }
    }

    /**
     * @param list<string>                             $methods
     * @param class-string|array{class-string, string} $handler
     */
    public function add(string $path, array $methods, string|array $handler): RouteDefinition
    {
        // хвостовой слэш сохраняем как написано: для битриксовых сайтов
        // канонические URL часто оканчиваются на «/», и «/news/» ≠ «/news»
        $fullPath = $this->currentPrefix . '/' . ltrim($path, '/');

        $definition = new RouteDefinition($fullPath, $methods, $handler);
        $this->definitions[] = $definition;

        return $definition;
    }

    public function build(): RouteCollection
    {
        $collection = new RouteCollection();
        $names = [];
        foreach ($this->definitions as $definition) {
            $name = $definition->routeName();
            if (isset($names[$name])) {
                // RouteCollection::add() молча перезаписал бы первый роут
                throw new \LogicException(sprintf(
                    'Дубль роута «%s»: два объявления дают одно имя — задайте разные через name() или уберите лишнее.',
                    $name,
                ));
            }
            $names[$name] = true;
            $collection->add($name, $definition->toRoute());
        }

        return $collection;
    }
}

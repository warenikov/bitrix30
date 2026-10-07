<?php

declare(strict_types=1);

namespace Bitrix30\Routing;

use Symfony\Component\Routing\Route;

/**
 * Одно объявление роута в fluent-конфигураторе.
 *
 * Накапливает настройки (имя, ограничения, значения по умолчанию)
 * и отдаёт готовый symfony-Route при сборке коллекции.
 */
final class RouteDefinition
{
    private ?string $name = null;

    /** @var array<string, string> */
    private array $requirements = [];

    /** @var array<string, mixed> */
    private array $defaults = [];

    /**
     * @param list<string>                                $methods пустой список = любые методы
     * @param class-string|array{class-string, string}    $handler инвокабельный класс или [класс, метод]
     */
    public function __construct(
        private readonly string $path,
        private readonly array $methods,
        private readonly string|array $handler,
    ) {
    }

    /** Имя роута — для генерации URL. Без вызова имя выводится из метода и пути. */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** Ограничение плейсхолдера регулярным выражением: `where('slug', '[a-z0-9-]+')`. */
    public function where(string $parameter, string $regexp): self
    {
        $this->requirements[$parameter] = $regexp;

        return $this;
    }

    /** Значение по умолчанию для необязательного плейсхолдера. */
    public function default(string $parameter, mixed $value): self
    {
        $this->defaults[$parameter] = $value;

        return $this;
    }

    public function routeName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $methods = $this->methods === [] ? 'ANY' : implode('|', $this->methods);

        return $methods . ' ' . $this->path;
    }

    public function toRoute(): Route
    {
        return new Route(
            $this->path,
            [Router::HANDLER => $this->handler] + $this->defaults,
            $this->requirements,
            [],
            '',
            [],
            $this->methods,
        );
    }
}

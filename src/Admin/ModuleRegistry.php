<?php

declare(strict_types=1);

namespace Bitrix30\Admin;

use Psr\Container\ContainerInterface;

/** Явно зарегистрированные модули, создаваемые через DI. */
final class ModuleRegistry
{
    /** @var list<ModuleInterface>|null */
    private ?array $modules = null;

    /** @param list<class-string> $classes */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $classes,
    ) {
    }

    /** @return list<ModuleInterface> */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [];
        $keys = [];
        foreach ($this->classes as $class) {
            $module = $this->container->get($class);
            if (!$module instanceof ModuleInterface) {
                throw new \LogicException(sprintf('Модуль %s должен реализовывать %s.', $class, ModuleInterface::class));
            }
            if (isset($keys[$module->key()])) {
                throw new \LogicException(sprintf('Дубль ключа модуля «%s».', $module->key()));
            }
            $keys[$module->key()] = true;
            $modules[] = $module;
        }

        return $this->modules = $modules;
    }
}

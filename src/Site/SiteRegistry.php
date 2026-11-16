<?php

declare(strict_types=1);

namespace Bitrix30\Site;

use Bitrix30\Routing\RouteConfigurator;
use Psr\Container\ContainerInterface;

/** Активный сайт и его явная цепочка наследования. */
final class SiteRegistry
{
    /** @var list<Site>|null */
    private ?array $lineage = null;

    /** @param class-string<Site> $siteClass */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $siteClass,
    ) {
    }

    /** @return list<Site> сайты в порядке поиска шаблонов: потомок → родитель */
    public function lineage(): array
    {
        if ($this->lineage !== null) {
            return $this->lineage;
        }

        $lineage = [];
        $seen = [];
        $class = $this->siteClass;

        while ($class !== null) {
            if (isset($seen[$class])) {
                throw new \LogicException(sprintf('Цикл в наследовании сайта %s.', $class));
            }
            $seen[$class] = true;

            $site = $this->container->get($class);
            if (!$site instanceof Site) {
                throw new \LogicException(sprintf('Класс сайта %s должен наследовать %s.', $class, Site::class));
            }

            $lineage[] = $site;
            $class = $site::parentSite();
        }

        return $this->lineage = $lineage;
    }

    public function layout(): Layout
    {
        return $this->lineage()[0]->layout();
    }

    /** @return list<string> */
    public function pagesDirectories(): array
    {
        return array_map(
            static fn (Site $site): string => $site->pagesDirectory(),
            $this->lineage(),
        );
    }

    public function configureRoutes(RouteConfigurator $routes): void
    {
        $this->lineage()[0]->routes($routes);
    }
}

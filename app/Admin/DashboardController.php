<?php

declare(strict_types=1);

namespace App\Admin;

use Bitrix30\Admin\ModuleRegistry;
use Bitrix30\Routing\Router;
use Bitrix30\Site\Site;
use Bitrix30\Site\SiteRegistry;
use Bitrix30\View\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/** Read-only dashboard Admin v0. */
final class DashboardController
{
    public function __construct(
        private readonly TemplateRendererInterface $templates,
        private readonly ResponseFactoryInterface $responses,
        private readonly ModuleRegistry $modules,
        private readonly SiteRegistry $sites,
        private readonly Router $router,
        private readonly string $environment,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $html = $this->templates->render('@admin/dashboard.twig', [
            'environment' => $this->environment,
            'modules' => $this->modules->all(),
            'siteClasses' => array_map(
                static fn (Site $site): string => $site::class,
                $this->sites->lineage(),
            ),
            'routes' => $this->router->routes(),
            'handlerKey' => Router::HANDLER,
        ]);

        $response = $this->responses->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);

        return $response;
    }
}

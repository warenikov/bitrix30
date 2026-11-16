<?php

declare(strict_types=1);

namespace Bitrix30\Console;

use Bitrix30\Routing\Router;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'routes:list', description: 'Показать роуты конфигурации и активного сайта')]
final class RoutesListCommand extends Command
{
    public function __construct(
        private readonly Router $router,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['Имя', 'Методы', 'Путь', 'Обработчик']);

        foreach ($this->router->routes() as $name => $route) {
            $methods = $route->getMethods();
            $handler = $route->getDefault(Router::HANDLER);
            if (\is_array($handler)) {
                $handler = implode('::', array_map(
                    static fn (mixed $part): string => \is_string($part) ? $part : get_debug_type($part),
                    $handler,
                ));
            }

            $table->addRow([
                $name,
                $methods === [] ? 'ANY' : implode('|', $methods),
                $route->getPath(),
                \is_string($handler) ? $handler : get_debug_type($handler),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}

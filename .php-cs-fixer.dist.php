<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/app', __DIR__ . '/tests', __DIR__ . '/config', __DIR__ . '/public'])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
    ])
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache')
    ->setFinder($finder)
;

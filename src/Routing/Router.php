<?php

declare(strict_types=1);

namespace Bitrix30\Routing;

use Bitrix30\Http\Exception\MethodNotAllowedException;
use Bitrix30\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException as SymfonyMethodNotAllowed;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\Generator\Dumper\CompiledUrlGeneratorDumper;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RequestContext;

/**
 * Роутер фреймворка: матчинг запроса и генерация URL.
 *
 * Коллекция роутов компилируется (symfony CompiledUrlMatcher/Generator)
 * в один кеш-артефакт. В debug-режиме кеш пересобирается на каждый запрос,
 * в prod — один раз, сброс — удалением файла кеша.
 */
final class Router
{
    /** Ключ defaults роута, в котором конфигуратор хранит обработчик. */
    public const HANDLER = '_handler';

    /** @var array{matcher: array<mixed>, generator: array<mixed>}|null */
    private ?array $compiled = null;

    /** @param \Closure(): \Symfony\Component\Routing\RouteCollection $loader отложенная сборка коллекции */
    public function __construct(
        private readonly \Closure $loader,
        private readonly string $cacheFile,
        private readonly bool $debug,
    ) {
    }

    /**
     * Сопоставляет запрос с роутом.
     *
     * @return array<string, mixed> параметры роута: `_route`, обработчик и плейсхолдеры
     *
     * @throws NotFoundException         путь не найден
     * @throws MethodNotAllowedException путь есть, метод не разрешён
     */
    public function match(ServerRequestInterface $request): array
    {
        $uri = $request->getUri();
        $context = new RequestContext(
            '',
            $request->getMethod(),
            $uri->getHost() !== '' ? $uri->getHost() : 'localhost',
            $uri->getScheme() !== '' ? $uri->getScheme() : 'http',
        );

        $matcher = new CompiledUrlMatcher($this->compiled()['matcher'], $context);

        try {
            /** @var array<string, mixed> $parameters */
            $parameters = $matcher->match($uri->getPath());

            return $parameters;
        } catch (ResourceNotFoundException $exception) {
            throw new NotFoundException(sprintf('Нет роута для пути «%s»', $uri->getPath()), $exception);
        } catch (SymfonyMethodNotAllowed $exception) {
            throw new MethodNotAllowedException(
                array_values($exception->getAllowedMethods()),
                sprintf('Метод %s не разрешён для пути «%s»', $request->getMethod(), $uri->getPath()),
                $exception,
            );
        }
    }

    /**
     * Генерирует путь по имени роута: `generate('news.show', ['slug' => 'hello'])`.
     *
     * Всегда относительный путь с контекстом по умолчанию — без скрытой
     * зависимости от последнего match(). Абсолютные URL появятся отдельным
     * API с явным контекстом.
     *
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = []): string
    {
        return (new CompiledUrlGenerator($this->compiled()['generator'], new RequestContext()))
            ->generate($name, $parameters);
    }

    /** @return array{matcher: array<mixed>, generator: array<mixed>} */
    private function compiled(): array
    {
        if ($this->compiled !== null) {
            return $this->compiled;
        }

        if (!$this->debug && is_file($this->cacheFile)) {
            /** @var array{matcher: array<mixed>, generator: array<mixed>} $cached */
            $cached = require $this->cacheFile;

            return $this->compiled = $cached;
        }

        $collection = ($this->loader)();
        $compiled = [
            'matcher' => (new CompiledUrlMatcherDumper($collection))->getCompiledRoutes(),
            'generator' => (new CompiledUrlGeneratorDumper($collection))->getCompiledRoutes(),
        ];

        if (!$this->debug) {
            $this->writeCache($compiled);
        }

        return $this->compiled = $compiled;
    }

    /**
     * Пишет кеш атомарно (tmp + rename). Любой сбой записи — осознанная
     * деградация: приложение продолжает работать без кеша, компилируя
     * роуты на каждый запрос, вместо падения или потока warning'ов.
     *
     * @param array{matcher: array<mixed>, generator: array<mixed>} $compiled
     */
    private function writeCache(array $compiled): void
    {
        $dir = \dirname($this->cacheFile);
        if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
            return;
        }

        $tmp = $this->cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, '<?php return ' . var_export($compiled, true) . ';') === false) {
            return;
        }

        if (!@rename($tmp, $this->cacheFile)) {
            @unlink($tmp);
        }
    }
}

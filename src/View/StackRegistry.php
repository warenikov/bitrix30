<?php

declare(strict_types=1);

namespace Bitrix30\View;

/**
 * Стеки отложенного вывода — идея прототипа
 * («компонент докидывает CSS в head после того, как head отрендерен»),
 * но без перехвата всех узлов вывода: `stack()` печатает маркер-плейсхолдер,
 * а после полного рендера страницы resolve() заменяет маркеры содержимым.
 *
 * Маркер содержит случайный токен экземпляра, поэтому подделать его
 * из пользовательского контента нельзя. Один экземпляр = один запрос.
 */
final class StackRegistry
{
    /** @var array<string, list<string>> */
    private array $stacks = [];

    /** @var array<string, true> ключи уже добавленного через pushOnce */
    private array $seen = [];

    private readonly string $token;

    public function __construct()
    {
        $this->token = bin2hex(random_bytes(8));
    }

    /** Добавляет фрагмент в стек; `$prepend` — в начало вместо конца. */
    public function push(string $stack, string $content, bool $prepend = false): void
    {
        $this->stacks[$stack] ??= [];

        if ($prepend) {
            array_unshift($this->stacks[$stack], $content);

            return;
        }

        $this->stacks[$stack][] = $content;
    }

    /**
     * push() с дедупликацией по ключу: повторный вызов с тем же `$id`
     * игнорируется. Так ассеты компонента попадают на страницу один раз,
     * сколько бы раз компонент ни вызвали.
     */
    public function pushOnce(string $stack, string $id, string $content, bool $prepend = false): void
    {
        $key = $stack . "\x00" . $id;
        if (isset($this->seen[$key])) {
            return;
        }
        $this->seen[$key] = true;
        $this->push($stack, $content, $prepend);
    }

    /** Маркер, который `stack('имя')` печатает в вывод вместо содержимого. */
    public function placeholder(string $stack): string
    {
        return "\x00b30stack:" . $this->token . ':' . $stack . "\x00";
    }

    /**
     * Заменяет маркеры стеков их содержимым. Вызывается один раз,
     * когда страница отрендерена целиком и все push() уже случились.
     *
     * Напушенный фрагмент сам может содержать маркер другого стека
     * (strtr вставленное не пересканирует), поэтому проходы повторяются;
     * предел итераций защищает от стека, ссылающегося на самого себя.
     */
    public function resolve(string $html): string
    {
        $prefix = "\x00b30stack:" . $this->token . ':';

        $replacements = [];
        foreach (array_keys($this->stacks) as $stack) {
            $replacements[$this->placeholder($stack)] = implode("\n", $this->stacks[$stack]);
        }

        for ($pass = 0; $pass < 5 && str_contains($html, $prefix); ++$pass) {
            $html = strtr($html, $replacements);
        }

        // маркеры стеков, в которые ничего не напушили
        $pattern = '/\x00b30stack:' . preg_quote($this->token, '/') . ':[^\x00]*\x00/';

        return preg_replace($pattern, '', $html) ?? $html;
    }

    /**
     * Сбрасывает состояние. Вызывается после resolve() целой страницы:
     * следующий рендер в том же процессе (worker-mode, письмо, вторая
     * страница) начинает с чистых стеков и чистой дедупликации.
     */
    public function reset(): void
    {
        $this->stacks = [];
        $this->seen = [];
    }
}

<?php

declare(strict_types=1);

namespace Bitrix30\View\Twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Тег `{% push 'head' %}…{% endpush %}` — отложенный вывод в стек.
 * Модификаторы (в любом порядке):
 * - `prepend` — фрагмент в начало стека, а не в конец;
 * - `once` — дедупликация: сколько бы раз шаблон ни рендерился
 *   (компонент на странице дважды), фрагмент попадёт в стек один раз.
 * Показ стека — функцией `{{ stack('head') }}`.
 */
final class PushTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): PushNode
    {
        $stream = $this->parser->getStream();

        $name = $this->parser->parseExpression();

        $prepend = false;
        $once = false;
        while (true) {
            if ($stream->nextIf(Token::NAME_TYPE, 'prepend') !== null) {
                $prepend = true;
            } elseif ($stream->nextIf(Token::NAME_TYPE, 'once') !== null) {
                $once = true;
            } else {
                break;
            }
        }
        $stream->expect(Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse($this->decideEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new PushNode($name, $body, $prepend, $once, $token->getLine());
    }

    public function decideEnd(Token $token): bool
    {
        return $token->test('endpush');
    }

    public function getTag(): string
    {
        return 'push';
    }
}

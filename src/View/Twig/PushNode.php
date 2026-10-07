<?php

declare(strict_types=1);

namespace Bitrix30\View\Twig;

use Bitrix30\View\StackRegistry;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Узел тега `{% push %}`: захватывает тело (CaptureNode, совместим
 * с yield-рантаймом Twig 3.12+) и отправляет его в StackRegistry
 * через runtime-загрузчик — состояния в расширении нет.
 */
#[YieldReady]
final class PushNode extends Node
{
    public function __construct(AbstractExpression $name, Node $body, bool $prepend, bool $once, int $lineno)
    {
        $capture = new CaptureNode($body, $lineno);
        // нужна сырая строка, а не Markup: содержимое пойдёт в HTML как есть
        $capture->setAttribute('raw', true);

        parent::__construct(['name' => $name, 'body' => $capture], ['prepend' => $prepend, 'once' => $once], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $prepend = $this->getAttribute('prepend') === true ? 'true' : 'false';

        // CaptureNode сам завершает стейтмент «;», поэтому сначала
        // присваивание во временную переменную, затем вызов push()
        $compiler
            ->addDebugInfo($this)
            ->write('$b30PushedContent = ')
            ->subcompile($this->getNode('body'))
            ->raw("\n");

        if ($this->getAttribute('once') === true) {
            // id дедупликации — шаблон и строка тега: один и тот же push
            // из повторных рендеров шаблона попадёт в стек один раз
            $compiler
                ->write('$this->env->getRuntime(\\' . StackRegistry::class . '::class)->pushOnce(')
                ->subcompile($this->getNode('name'))
                ->raw(', $this->getTemplateName() . \':' . $this->getTemplateLine() . '\', $b30PushedContent, ')
                ->raw($prepend)
                ->raw(");\n");

            return;
        }

        $compiler
            ->write('$this->env->getRuntime(\\' . StackRegistry::class . '::class)->push(')
            ->subcompile($this->getNode('name'))
            ->raw(', $b30PushedContent, ')
            ->raw($prepend)
            ->raw(");\n");
    }
}

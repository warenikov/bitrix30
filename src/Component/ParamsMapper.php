<?php

declare(strict_types=1);

namespace Bitrix30\Component;

/**
 * Превращает массив из `component('имя', {...})` в типизированный DTO:
 * ключи массива становятся именованными аргументами конструктора.
 * Лишний ключ или неверный тип — понятная ошибка, а не молчаливое игнорирование
 * (урок магических $arParams: опечатка в ключе не должна «просто не работать»).
 */
final class ParamsMapper
{
    /** @param array<array-key, mixed> $params ключи проверяются на строковость в рантайме: из Twig могут прийти и числовые */
    public function map(string $componentName, ?string $paramsClass, array $params): ?object
    {
        if ($paramsClass === null) {
            if ($params !== []) {
                throw new \LogicException(sprintf(
                    'Компонент «%s» не принимает параметры, переданы: %s.',
                    $componentName,
                    implode(', ', array_keys($params)),
                ));
            }

            return null;
        }

        if (!class_exists($paramsClass)) {
            throw new \LogicException(sprintf('DTO параметров компонента «%s» не найден: %s.', $componentName, $paramsClass));
        }

        foreach (array_keys($params) as $key) {
            if (!\is_string($key)) {
                throw new \LogicException(sprintf('Параметры компонента «%s» должны быть именованными.', $componentName));
            }
        }

        // валидируем привязку аргументов заранее и по именам: тогда ошибка
        // конструктора самого DTO (если он что-то делает) не маскируется
        // под «параметры не подошли»
        $constructor = (new \ReflectionClass($paramsClass))->getConstructor();
        $known = [];
        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $known[$parameter->getName()] = $parameter;
        }

        $unknown = array_diff_key($params, $known);
        if ($unknown !== []) {
            throw new \LogicException(sprintf(
                'У компонента «%s» нет параметров: %s. Есть: %s.',
                $componentName,
                implode(', ', array_keys($unknown)),
                $known === [] ? '(нет)' : implode(', ', array_keys($known)),
            ));
        }

        foreach ($known as $name => $parameter) {
            if (!$parameter->isOptional() && !\array_key_exists($name, $params)) {
                throw new \LogicException(sprintf('Компоненту «%s» не передан обязательный параметр «%s».', $componentName, $name));
            }
        }

        return new $paramsClass(...$params);
    }
}

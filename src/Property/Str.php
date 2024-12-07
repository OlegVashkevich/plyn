<?php

namespace Plyn\Property;

use Sirius\Validation\Validator;

/**
 * Контроллер для свойства строки Plyn.
 * Использует библиотеку проверки Siriusphp для проверки строк.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Str
{
    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     * @param string $new_value Входная строка этого свойства.
     *
     * @return string Проверенная строка.
     */
    public function set($bean, $property, $new_value)
    {
        if (isset($property['validate'])) {
            $validator = new Validator();
            // Правило(а) валидатора должны быть массивом
            $validator->add([ $property['name'] => $property['validate'] ]);
            // Валидатору нужен массив в качестве входных данных
            if ($validator->validate([ $property['name'] => $new_value ])) {
                return $new_value;
            } else {
                $messages = $validator->getMessages();
                throw new \Exception('Ошибка проверки. ' . implode(', ', $messages[ $property['name'] ]));
            }
        } else {
            return $new_value;
        }
    }
}

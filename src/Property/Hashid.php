<?php

namespace Plyn\Property;

use Hashids\Hashids;
use RedBeanPHP\R as R;

/**
 * Контроллер для свойства хеш-идентификатора Plyn.
 * Генерация идентификаторов, подобных YouTube, на основе идентификаторов объектов контента.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Hashid
{
    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     * @param string $new_value
     *
     * @return string Новый хеш-идентификатор объекта с этим свойством.
     */
    public function set($bean, $property, $new_value)
    {
        if (isset($bean->{ $property['name'] })) {
            return $bean->{ $property['name'] };
        } else {
            if (isset($property['salt'])) {
                $salt = $property['salt'];
            } else {
                $salt = '';
            }

            if (isset($property['padding'])) {
                $padding = $property['padding'];
            } else {
                $padding = 0;
            }

            if (isset($property['alphabet'])) {
                $hashids = new Hashids($salt, $padding, $property['alphabet']);
            } else {
                $hashids = new Hashids($salt, $padding);
            }

            $id = R::store($bean); // Сохраняем сущность с полученным хеш-идентификатором
            return $hashids->encode($id);
        }
    }
}

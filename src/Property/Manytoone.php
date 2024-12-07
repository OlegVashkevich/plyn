<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Контроллер для свойства Plyn "многие к одному".
 * Позволяет пользователю определить отношение "многие к одному" между двумя объектами контента.
 * Контроллер типа свойства Manytoone обеспечивает отношение "многие к одному" между 2 моделями Plyn,
 * как описано здесь: http://redbeanphp.com/index.php?p=/many_to_one
 * Имя свойства должно быть именем модели Plyn, с которой эта модель может иметь отношение "многие к одному".
 * Для правильной работы другая модель должна иметь отношение "один ко многим" с этой моделью.
 * Таким образом, в нашем примере в проекте Plyn модель Plyn Book имеет отношение "многие к одному"
 * с моделью Plyn Genre, а модель Plyn Genre имеет отношение "один ко многим" с моделью Plyn Book.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Manytoone
{
    /**
     * Метод read выполняется каждый раз при чтении свойства с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return bean[] Массив с компонентами Redbean с отношением «многие ко многим» с записью с этим свойством.
     */
    public function read($bean, $property)
    {
        return R::findOne($property['name'], ' id = :id ', [ ':id' => $bean[$property['name'] . '_id'] ]);
    }

    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     * @param integer $new_value Идентификатор объекта,
     * с которым объект с этим свойством имеет отношение «многие к одному».
     *
     * @return bean Объект bean Redbean, с которым объект с этим свойством имеет отношение «многие к одному».
     */
    public function set($bean, $property, $new_value)
    {
        // Проверяем и установливаем отношение
        $relation = R::findOne($property['name'], ' id = :id ', [ ':id' => $new_value ]);
        if (!$relation) {
            throw new \Exception('Модель ' . $property['name'] . ' не существует.');
        } else {
            return $relation;
        }
    }

    /**
     * Метод options возвращает все необязательные значения, которые может иметь это свойство,
     * но НЕ те, которые оно имеет в данный момент.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return array Массив со всеми bean-компонентами модели Plyn $property['name'].
     */
    public function options($bean, $property)
    {
        return R::findAll($property['name']);
    }
}

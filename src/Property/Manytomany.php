<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Контроллер для свойства Plyn «многие ко многим».
 * Позволяет пользователю определить отношение «многие ко многим» между двумя записями контента.
 * Контроллер типа свойства Manytomany позволяет установить отношение «многие ко многим» между 2 моделями Plyn.
 * Имя свойства должно быть именем модели Plyn, с которой эта модель может иметь отношение «многие ко многим».
 * Для правильной работы другая модель также должна иметь отношение «многие ко многим» с этой моделью.
 * Таким образом, в нашем примере в проекте Plyn модель Plyn Book имеет отношение «многие ко многим»
 * с моделью Plyn Author, а модель Plyn Author имеет отношение «многие ко многим» с моделью Plyn Book.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Manytomany
{
    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     * @param integer[] $new_value Массив с идентификаторами объектов,
     * с которыми объект с этим свойством имеет отношение «многие ко многим».
     *
     * @return boolean Возвращает логическое значение, поскольку отношение «многие ко многим»
     * автоматически сохраняется в отдельной таблице базы данных.
     * Возвращает true, если какие-либо отношения установлены, false — если нет.
     */
    public function set($bean, $property, $new_value)
    {
        $list = [];
        foreach ($new_value as $id) {
            if ($id) {
                $list[] = R::load($property['name'], $id);
            }
        }

        if (count($list) > 0) {
            $bean->{ 'shared' . ucfirst($property['name']) . 'List' } = $list;
            R::store($bean);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Метод read выполняется каждый раз при чтении свойства с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return bean[] Массив с компонентами Redbean, имеющими связь «многие ко многим» с записью,
     * обладающей этим свойством.
     */
    public function read($bean, $property)
    {
        // ПРИМЕЧАНИЕ: Мы не выполняем метод чтения для каждого компонента.
        // Перед реализацией этого я хочу проверить потенциальные проблемы с производительностью.
        return  $bean->{ 'shared' . ucfirst($property['name']) . 'List' };
    }

    /**
     * Метод options возвращает все необязательные значения, которые может иметь это свойство,
     * но НЕ те, которые оно имеет в данный момент.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return bean[] Массив со всеми компонентами модели Plyn $property['name'].
     */
    public function options($bean, $property)
    {
        if ($bean) {
            // Список сущностей, которые уже имеют связь «многие ко многим» с этой сущностью
            $relations = $bean->{ 'shared' . ucfirst($property['name']) . 'List' };
            if ($relations) {
                $relations_ids = [];
                foreach ($relations as $relation) {
                    $relations_ids[] = $relation->id;
                }
                return R::find(
                    $property['name'],
                    ' id NOT IN (' . R::genSlots($relations_ids) . ') ',
                    $relations_ids
                );
            } else {
                return R::findAll($property['name']);
            }
        } else {
            return R::findAll($property['name']);
        }
    }
}

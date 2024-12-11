<?php

namespace Plyn\Property;

use RedBeanPHP\R;

/**
 * Контроллер для свойства Plyn «один ко многим».
 * Позволяет пользователю определить отношение «один ко многим» между двумя объектами контента.
 * Контроллер типа свойства Onetomany позволяет установить отношение «один ко многим» между 2 моделями Plyn.
 * Имя свойства должно быть именем модели Plyn, с которой эта модель может иметь отношение «один ко многим».
 * Для правильной работы другая модель должна иметь отношение «многие к одному» с этой моделью.
 * Таким образом, в нашем примере в проекте Plyn модель Plyn Genre имеет отношение «один ко многим»
 * с моделью Plyn Book, а модель Plyn Book имеет отношение «многие к одному» с моделью Plyn Genre.
 */
class Onetomany
{
    /**
     * Метод set выполняется каждый раз, когда задается свойство этого типа.
     *
     * @param bean  $bean      объект bean Readbean для этого свойства
     * @param array $property  массив свойств модели Plyn
     * @param int[] $new_value Массив с идентификаторами объектов,
     *                         с которыми объект с этим свойством имеет отношение «один ко многим»
     *
     * @return bool Возвращает логическое значение, поскольку отношение «один ко многим» установлено только в
     *              bean с отношением «один ко многим».
     *              Возвращает true, если установлены какие-либо отношения, и false в противном случае.
     */
    public function set($bean, $property, $new_value)
    {
        // Список дочерних компонентов для сохранения
        $children = [];

        // Подключаем дочернюю модель для чтения свойств
        $model_name = '\Plyn\Models\\'.ucfirst($property['module']).'\\'.ucfirst($property['name']);
        $child = new $model_name();

        $relative_position = false;
        foreach ($child->properties as $p) {
            if ('\\Plyn\\Property\\Position' === $p['type'] && $p['manytoone']) {
                $relative_position = true;
                $position_property_name = $p['name'];
                break;
            }
        }

        if ($relative_position && isset($position_property_name)) {
            // Проверяем, изменился ли родитель детей.
            // Если да, обновляем все позиции для старого и нового родителя.

            // $old_children = $bean->{ 'own'.ucfirst($property['name']).'List' };
            $old_children = R::find(
                $property['name'],
                $bean->getMeta('type').'_id = :id ORDER BY '.$position_property_name.' ASC ',
                [':id' => $bean->id]
            );
            $old_children_ids = [];
            $position = 0;
            foreach ($old_children as $old_child) {
                // Сбрасываем позицию оставшихся старых дочерних элементов,
                // устанавливаем позицию удаленных старых дочерних элементов на 0
                if (in_array($old_child->id, $new_value)) {
                    $old_child->{ $position_property_name } = $position;
                    $children[] = $old_child;
                    ++$position;

                    // Создаем массив с идентификаторами для следующего шага
                    $old_children_ids[] = $old_child->id;
                } else {
                    $old_child->{ $position_property_name } = 0;
                    $old_child->{ $bean->getMeta('type') } = null; // Удаляем родительский элемент перед сохранением
                    R::store($old_child);
                }
            }

            // Проверяем, были ли добавлены новые дети.
            $bottom_position = count($old_children_ids);
            foreach ($new_value as $new_child_id) {
                if ($new_child_id && !in_array($new_child_id, $old_children_ids)) {
                    // Добавляем новый дочерний элемент в нижнюю позицию
                    $new_child = R::load($property['name'], $new_child_id);
                    $new_child->{ $position_property_name } = $bottom_position;
                    $children[] = $new_child;
                    ++$bottom_position;
                }
            }
        } else {
            // Нет относительного положения
            foreach ($new_value as $id) {
                if ($id) {
                    $children[] = R::load($property['name'], $id);
                }
            }
        }

        // Сохраняем
        if (count($children) > 0) {
            $bean->{ 'own'.ucfirst($property['name']).'List' } = $children;
            R::store($bean);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Метод read выполняется каждый раз при чтении свойства с этим типом.
     *
     * @param bean     $bean     объект bean Readbean для этого свойства
     * @param string[] $property массив свойств модели Plyn
     *
     * @return bean[] массив с bean Redbean с отношением «многие к одному» с объектом с этим свойством
     */
    public function read($bean, $property)
    {
        // ПРИМЕЧАНИЕ: Мы не выполняем метод чтения для каждого компонента.
        // Прежде чем реализовать это, я хочу проверить потенциальные проблемы с производительностью.
        // return  $bean->{ 'own'.ucfirst($property['name']).'List' };

        // Подключаем дочернюю модель для чтения свойств
        $model_name = '\Plyn\Models\\'.ucfirst($property['module']).'\\'.ucfirst($property['name']);
        $child = new $model_name();

        // Все beans с этим родителем
        // Упорядочиваем по позицям, если существуют
        // ПРИМЕЧАНИЕ: Мы не выполняем метод чтения для каждого бина.
        // Прежде чем реализовать это, я хочу проверить потенциальные проблемы с производительностью.
        $add_to_query = '';
        foreach ($child->properties as $p) {
            if ('\\Plyn\\Property\\Position' === $p['type']) {
                $add_to_query = $p['name'].' ASC, ';
            }
        }

        return R::find(
            $property['name'],
            $bean->getMeta('type').'_id = :id ORDER BY '.$add_to_query.'title ASC ',
            [':id' => $bean->id]
        );
    }

    /**
     * Метод options возвращает все необязательные значения, которые может иметь это свойство,
     * но НЕ те, которые оно имеет в данный момент.
     *
     * @param bean  $bean     объект bean Readbean для этого свойства
     * @param array $property массив свойств модели Plyn
     *
     * @return bean[] массив со всеми bean модели Plyn $property['name']
     */
    public function options($bean, $property)
    {
        if ($bean) {
            // Возвращает только бины с другим или текущим идентификатором $col_name
            $col_name = $bean->getMeta('type').'_id';

            return R::find(
                $property['name'],
                ' '.$col_name.' != ? OR  '.$col_name.' IS NULL ',
                [$bean->id]
            );
        } else {
            return R::findAll($property['name']);
        }
    }
}

<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Контроллер для свойства положения Plyn.
 * Создает место для вновь размещенного компонента Redbean, обновляя позиции других компонентов,
 * и возвращает новую позицию компонента.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Position
{
    /**
     * Метод set выполняется каждый раз, когда задается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     * @param integer $new_value Входная позиция объекта с этим свойством.
     *
     * @return integer Новая позиция объекта с этим свойством.
     */
    public function set($bean, $property, $new_value)
    {
        if (!empty($new_value) || $new_value === 0 || $new_value === '0') {
            $new_value = intval($new_value); // Преобразуем в целое число
        }

        // Абсолютное положение или относительное отношение многие к одному?
        if (isset($property['manytoone'])) {
            // Относительное

            // Проверяем, изменился ли родитель
            $old_parent_id = $bean->{ $property['manytoone'] . '_id' };
            $new_parent_id = $bean->{ $property['manytoone'] }->id;

            $all = R::find($bean->getMeta('type'), $property['manytoone'] . '_id = ? ', [ $new_parent_id ]);
            $count_all = R::count($bean->getMeta('type'), $property['manytoone'] . '_id = ? ', [ $new_parent_id ]);

            // Проверяем, изменился ли родитель
            if ($old_parent_id !== $new_parent_id) {
                $count_all++; // Добавляем 1, потому что у компонента появился новый родительский элемент
                $all[] = $bean; // Добавляем bean в результат для нового родителя
                $new_value = $count_all - 1; // Добавляем bean в конец нового родителя

                // Родитель изменился, обновляем старые родительские позиции
                $old_siblings = R::find(
                    $bean->getMeta('type'),
                    $property['manytoone'] . '_id = :parent_id AND id != :bean_id ORDER BY :property ASC ',
                    [ ':parent_id' => $old_parent_id, ':bean_id' => $bean->id, ':property' => $property['name'] ]
                );
                $pos = 0;
                foreach ($old_siblings as $s) {
                    $s->{ $property['name'] } = $pos;
                    $s->modified = R::isoDateTime();
                    R::store($s);
                    $pos++;
                }
            }
        } else {
            // Абсолютное
            $all = R::findAll($bean->getMeta('type'));
            $count_all = R::count($bean->getMeta('type'));
        }

        $curr_value = $bean->{ $property['name'] };

        // Новый bean
        if (empty($curr_value) && $curr_value !== 0 && $curr_value !== '0') {
            // Позиция внизу
            $curr_value = $count_all;
        }

        // Нет новых данных
        if ((empty($new_value) && $new_value !== 0) || $new_value == $curr_value) {
            return $curr_value;
        } else {
            if ($new_value > $count_all - 1) {
                $new_value = $count_all - 1;
            }
            if ($new_value < 0) {
                $new_value = 0;
            }
            if ($new_value < $curr_value) {
                foreach ($all as $b) {
                    if ($b->{ $property['name'] } >= $new_value and $b->{ $property['name'] } < $curr_value) {
                        $b->{ $property['name'] } = $b->{ $property['name'] } + 1;
                        $b->modified = R::isoDateTime();
                        R::store($b);
                    }
                }
            } elseif ($new_value > $curr_value) {
                foreach ($all as $b) {
                    if ($b->{ $property['name'] } <= $new_value and $b->{ $property['name'] } > $curr_value) {
                        $b->{ $property['name'] } = $b->{ $property['name'] } - 1;
                        $b->modified = R::isoDateTime();
                        R::store($b);
                    }
                }
            }
            return $new_value;
        }
    }

    /**
     * Метод удаления выполняется каждый раз при удалении объекта со свойством этого типа.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     */
    public function delete($bean, $property)
    {
        // Абсолютное положение или относительное отношение многие к одному?
        if (isset($property['manytoone'])) {
            $count_all = R::count(
                $bean->getMeta('type'),
                $property['manytoone'] . '_id = ? ',
                [ $bean->{ $property['manytoone'] . '_id' } ]
            );
        } else {
            $count_all = R::count($bean->getMeta('type'));
        }

        $bottom = $count_all - 1;
        $this->set($bean, $property, $bottom); // Нет необходимости сохранять новую позицию этого компонента
    }
}

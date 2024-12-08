<?php

namespace Plyn\Core;

use RedBeanPHP\R as R;

/**
 * Контроллер поиска для Plyn.
 *
 * Мы используем "*" в качестве разделителя, так как "-" используется для символьного кода,
 * а "+" отфильтровывается из имени переменной $_GET PHP.
 *
 * Синтаксис:
 * Значение от: *min
 * Значение до: *max
 * Содержит: *has
 * Равно: *is
 * Сортировка: sort
 * Предел: limit
 * Смещение: offset
 *
 * смещение работает только если предел также определен
 *
 * Примеры структур запросов:
 * [model]?*has=[search string] : Поиск всех доступных для поиска свойств модели
 * [model]?[property]*has=[search string] : Поиск одного свойства модели
 * [model]?[property]*min=[number]&sort=[property]*asc
 * [model]?description*title*has=[search string]&title*has=[search string]&sort=title*asc&offset=10&limit=100
 *
 */
class Search
{
    protected $type;
    protected $criteria;
    protected $sequences;
    protected $model;

    /**
     * Конструктор
     *
     * @param string $module Название модуля
     * @param string $type Тип сущности для поиска
     */
    public function __construct($module, $type)
    {
        $this->type = $type;

        $this->criteria = array(
            '*min',
            '*max',
            '*has',
            '*is'
        );

        // Порядок сортировки
        $this->sequences = array(
            '*asc',
            '*desc'
        );

        $model_name = '\Plyn\Models\\' . ucfirst($module) . '\\' . ucfirst($type);
        $this->model = new $model_name();
    }

    /**
     * Функция поиска
     *
     * @param array[] $params Массив параметров запроса
     *
     * @return array[] Массив с ['result'] сущностями Redbean, соответствующими критериям поиска,
     * ['total'] сущностей для запроса, всего ['pages'],
     * текущая ['page'], ['offset'], ['limit'], ['query'] часть URL и ['section'] часть URL.
     */
    public function find($params)
    {
        // Поиск
        $loop = 0; // Чтобы создать разные имена для всех значений поиска
        $q = [];
        $s = [];
        $values = [];
        foreach ($params as $left => $right) {
            $lhs = $this->lefthandside($left);
            // Сортировка
            if ($lhs == 'sort') {
                $rhs = $this->righthandside($right);

                $glue = ' ' . strtoupper($rhs['order']) . ', ';
                // Добавляем последний порядок
                $s[] = implode($glue, $rhs['properties']) . ' ' . strtoupper($rhs['order']);
            } elseif ($lhs === 'offset') {
                // Смещение
                $offset = floatval($right);
                $values[ ':offset' ] = floatval($right);
            } elseif ($lhs === 'limit') {
                // Лимит
                $limit = floatval($right);
                $values[ ':limit' ] = floatval($right);

            // Ищем
            } elseif ($lhs) {
                $p = [];
                foreach ($lhs['properties'] as $k => $v) {
                    if ($this->isSearchable($v)) {
                        if ($lhs['criterion'] === '*min') {
                            // Создаем запрос '>='
                            $p[] = ' ' . $v . ' >= :value' . $loop . ' ';
                            // Добавляем значение в массив значений именованного поиска Redbean
                            $values[ ':value' . $loop ] = floatval($right);
                        } elseif ($lhs['criterion'] === '*max') {
                            // Создаем запрос '<='
                            $p[] = ' ' . $v . ' <= :value' . $loop . ' ';
                            // Добавляем значение в массив значений именованного поиска Redbean
                            $values[ ':value' . $loop ] = floatval($right);
                        } elseif ($lhs['criterion'] === '*has') {
                            // Создаем запрос 'LIKE'
                            $p[] = ' ' . $v . ' LIKE :value' . $loop . ' ';
                            // Добавляем значение в массив значений именованного поиска Redbean
                            $values[ ':value' . $loop ] = '%' . $right . '%';
                        } elseif ($lhs['criterion'] === '*is') {
                            // Создаем запрос '='
                            $p[] = ' ' . $v . ' = :value' . $loop . ' ';
                            // Добавляем значение в массив значений именованного поиска Redbean
                            $values[ ':value' . $loop ] = $right;
                        }
                    } else {
                        throw new \Exception($v . ' недоступно для поиска.');
                    } // Конец isSearchable($v)
                } // Конец foreach $lhs['properties']


                // Разбиваем массив, чтобы создать хороший запрос «ИЛИ»
                $q[] = implode('OR', $p);

                $loop++;
            } // Конец if else
        } // Конец foreach $params

        // Запрос

        // Разбиваем массив, чтобы создать хороший '( #query ) AND ( #query )'
        $query = '';
        if (count($q) > 1) {
            $query = '(' . implode(') AND (', $q) . ')';
        } elseif (count($q) > 0) {
            $query = $q[0];
        }

        // Разбиваем различные сортировочные массивы
        $sort = '';
        if (count($s) > 0) {
            $sort = ' ORDER BY ' . implode(', ', $s);
        }

        $part = '';
        if (isset($limit)) {
            $part = ' LIMIT :limit';
            if (isset($offset)) {
                $part .= ' OFFSET :offset';
            }
        } else {
            unset($values[ ':limit' ]);
            unset($values[ ':offset' ]);
        }
        // Результат поиска
        $return['result'] = R::find($this->type, $query . $sort . $part, $values);

        // Общее количество результатов по этому запросу
        unset($values[ ':limit' ]);
        unset($values[ ':offset' ]);
        $return['total'] = R::count($this->type, $query . $sort, $values);

        // Страницы
        if (isset($limit)) {
            $return['limit'] = $limit;
            // Всего страниц
            $return['pages'] = ceil($return['total'] / $limit);
            if (isset($offset)) {
                $return['offset'] = $offset;
                // Текущая страница
                $return['page'] = ceil($offset / $limit);
            }
        }

        // Отделяем поисковый запрос от запроса раздела
        foreach ($params as $left => $right) {
            if ($left == 'limit' || $left == 'offset') {
                if (!isset($section)) {
                    $section = '';
                }
                $section .= '&' . $left . '=' . $right;
            } else {
                if (!isset($search)) {
                    $search = '';
                }
                $search .= '&' . $left . '=' . $right;
            }
        }

        if (isset($section)) {
            $return['section'] = substr($section, 1);
        }
        if (isset($search)) {
            $return['query'] = substr($search, 1);
        }

        return $return;
    }

    /*
    * Проверяет, существует ли свойство и доступно ли для поиска
    *
    * @param string $propertyname
    *
    * @return boolean
    */
    private function isSearchable($propertyname)
    {
        foreach ($this->model->properties as $property) {
            if ($property['name'] == $propertyname) {
                if ($property['searchable']) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /*
    * Анализируем левую часть уравнения поиска
    *
    * @param string $input
    *
    * @return string[] Массив, содержащий критерий и вложенный массив со свойствами для поиска
    */
    private function lefthandside($input)
    {
        if ($input == 'sort') { // Сортировка происходит после поиска
            return 'sort';
        } elseif ($input == 'offset') {
            return 'offset';
        } elseif ($input == 'limit') {
            return 'limit';
        } else {
            foreach ($this->criteria as $criterion) {
                if (substr($input, strlen($criterion) * -1) == $criterion) {
                    $return = [ 'criterion' => $criterion ];
                    if (strlen($input) > strlen($criterion)) {
                        // Массив свойств
                        $return['properties'] = explode('*', substr($input, 0, strlen($criterion) * -1));
                    } else {
                        // Если свойства не определены, вернуть все доступные для поиска свойства.
                        foreach ($this->model->properties as $property) {
                            if (isset($property['searchable'])) {
                                $return['properties'][] = $property['name'];
                            }
                        }

                        if (count($return['properties']) == 0) {
                            throw new \Exception('У этой модели нет доступных для поиска свойств.');
                        }
                    }
                    return $return;
                }
            }
        }
        return false;
    }

    /*
    * Анализируем правую часть уравнения поиска
    *
    * @param string $input
    *
    * @return string[] Массив, содержащий порядок и вложенный массив со свойствами для сортировки
    */
    private function righthandside($input)
    {
        foreach ($this->sequences as $order) {
            if (substr($input, strlen($order) * -1) == $order) {
                $return = [ 'order' => substr($order, 1) ];
                if (strlen($input) > strlen($order)) {
                    $return['properties'] = explode('*', substr($input, 0, strlen($order) * -1)); // Массив свойств
                } else {
                    return false;
                }
                return $return;
            }
        }
        return false;
    }
}

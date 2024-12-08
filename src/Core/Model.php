<?php

namespace Plyn\Core;

use RedBeanPHP\R as R;

/**
 * Базовая модель Plyn для всех моделей Plyn.
 * Каждый тип контента имеет свою собственную модель, которая расширяет эту модель.
 * Каждая модель имеет тип, описание и свойства.
 * Правила проверки являются необязательными.
 *
 */

class Model
{
    /** @var string $type Тип модели. Он такой же, как modelname в нижнем регистре, и определяет имя сущности RedBean и имя таблицы в базе данных. */
    protected $type;

    /** @var string $description Описание модели, отображаемое в интерфейсе администратора. */
    public $description;

    /** @var array $properties Массив, определяющий различные поля данных содержимого модели. Каждое свойство представляет собой массив как минимум со следующими ключами: name, description, type, input. Могут быть и другие необязательные ключи. */
    public $properties;

    public $module;

    public function __construct()
    {
        //достаем название модуля из пространства имен через рефлекию
        $this->module = $this->getModule();
    }

    /**
     * Выдает сущность Redbean и устанавливает дату ее создания.
     *
     * @return bean
     */
    protected function universalCreate()
    {

        $bean = R::dispense($this->type);
        $bean->created = R::isoDateTime();

        return $bean;
    }


    /**
     * Задает значения для сущности. Используется Create и Update.
     * Проверяет для каждого свойства, существует ли метод "set" для его типа.
     * Если да, он выполняет его.
     *
     * @param array $data Необработанные данные, обычно из Slim $request->getParsedBody()
     * @param bean $bean
     *
     * @return bean Сущность со значениями на основе $data.
     */
    public function set($data, $bean)
    {

        // Добавляем все свойства к сущности
        foreach ($this->properties as $property) {
            $value = false; // Нам нужно очистить возможное предыдущее значение $value

            // Определяем контроллер свойств
            $c = new $property['type']();

            // Новый данные для свойства
            if (
                isset($data[ $property['name'] ])
                ||
                (isset($_FILES[ $property['name'] ]) && $_FILES[ $property['name'] ]['size'] > 0)
                ||
                $property['autovalue'] == true
            ) {
                // Проверяем, существует ли метод set для типа заданного свойства
                if (method_exists($c, 'set')) {
                    //добавлем название модуля
                    $property['module'] = $this->module;
                    $value = $c->set($bean, $property, $data[ $property['name'] ]);
                } else {
                    $value = $data[ $property['name'] ];
                }

                if ($value) {
                    $hasvalue = true;
                } else {
                    $hasvalue = false;
                }

            // Нет новых данных для свойств
            } else {
                // Проверяем, существует ли метод read для типа заданного свойства
                if (isset($property['required'])) {
                    if (method_exists($c, 'read') && $c->read($bean, $property)) {
                        $hasvalue = true;
                    } elseif ($bean->{ $property['name'] }) {
                        $hasvalue = true;
                    } else {
                        $hasvalue = false;
                    }
                }
            }

            // Проверяем обязательно ли свойство
            if (isset($property['required'])) {
                if ($property['required'] && !$hasvalue) {
                    throw new \Exception('Ошибка проверки. ' . $property['description'] . ' обязательно.');
                }
            }

            // Результаты методов, возвращающих логические значения, не сохраняются.
            // Например, отношения «многие ко многим» хранятся в отдельной таблице.
            if (!is_bool($value)) {
                // Проверяем является ли значение свойства уникальным
                if (isset($property['unique'])) {
                    $duplicate = R::findOne($this->type, $property['name'] . ' = :val ', [ ':val' => $value ]);
                    if ($duplicate && $duplicate->id != $bean->id) {
                        throw new \Exception('Ошибка проверки. '
                            . $property['description'] . ' должно быть уникально.');
                    }
                }
                $bean->{ $property['name'] } = $value;
            }
        }

        $bean->modified = R::isoDateTime();
        R::store($bean);

        return $bean;
    }


    // CRUD:
    // Методы Create, Read, Update и Delete

    /**
     * Create
     *
     * @param array $data Необработанные данные для создания сущности Redbeean.
     *
     * @return bean Новая запись со значениями на основе $data.
     */
    public function create($data)
    {

        // Create
        $bean = $this->universalCreate();

        // Перехватываем исключение, поскольку компонент мог быть уже создан методом свойства.
        try {
            return $this->set($data, $bean);
        } catch (\Exception $e) {
            // Удаляем запись
            $this->delete($bean->id);
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Read
     *
     * Поиск bean-компонента по уникальному свойству, например, идентификатору или символьному коду.
     * Если $value не задано, он возвращает все bean-компоненты этого типа.
     *
     * @param mixed $value Значение свойства
     * @param string $property Имя свойства, по умолчанию id
     *
     * @return mixed Может возвращать один bean-компонент или массив bean-компонентов, если $value не задано.
     */
    public function read($value = false, $property_name = 'id')
    {
        if ($value) {
            // Один bean-компонент
            $bean = R::findOne($this->type, $property_name . ' = :value ', [ ':value' => $value ]);
            if (!$bean) {
                throw new \Exception('Модель ' . $this->type . ' не существует.');
            }

            // Проверяем методы чтения, специфичные для типа свойства
            foreach ($this->properties as $property) {
                //добавлем название модуля
                $property['module'] = $this->module;
                // Проверяем, существует ли определенный метод чтения свойства
                $c = new $property['type']();
                if (method_exists($c, 'read')) {
                    $bean->{ $property['name'] } = $c->read($bean, $property);
                }
            }
            return $bean;
        } else {
            // Все bean-компоненты этого типа
            // Упорядоченные по позиции(ям), если выходы
            // NOTE: Мы не выполняем метод чтения для каждого бина.
            // Перед тем, как реализовать это, я хочу проверить потенциальные проблемы с производительностью.
            $add_to_query = '';
            foreach ($this->properties as $property) {
                if ($property['type'] === '\\Plyn\\Property\\Position') {
                    $add_to_query = $property['name'] . ' ASC, ';
                }
            }
            return R::findAll($this->type, ' ORDER BY ' . $add_to_query . 'title ASC ');
        }
    }

    /**
     * Update
     *
     * Обновляет данные bean-компонента.
     *
     * @param array $data Необработанные данные для создания bean-компонента Redbeean.
     * @param integer $id
     *
     * @return bean Компонент с обновленными значениями на основе $data.
     */
    public function update($data, $id)
    {
        $bean = R::findOne($this->type, ' id = :id ', [ ':id' => $id ]);
        if (!$bean) {
            throw new \Exception('Модель ' . $this->type . ' не существует.');
        }
        return $this->set($data, $bean);
    }

    /**
     * Delete
     *
     * Удаление bean-компонента
     *
     * @param integer $id
     */
    public function delete($id)
    {
        $bean = R::findOne($this->type, ' id = :id ', [ ':id' => $id ]);
        if (!$bean) {
            throw new \Exception('Модель ' . $this->type . ' не существует.');
        }

        // Проверяем методы удаления, специфичные для типа свойства
        foreach ($this->properties as $property) {
            $c = new $property['type']();
            if (method_exists($c, 'delete')) {
                $c->delete($bean, $property);
            }
        }
        R::trash($bean);
    }



    // МЕТОДЫ ПОМОШНИКИ

    /**
     * Заполнение свойств
     *
     * Свойства могут иметь необязательные значения, например relation и file_select.
     * Этот метод, если применимо, запрашивает свойства для необязательных значений и заполняет их ими.
     * Выполняет поиск bean-компонента по уникальному свойству, например id или slug.
     * Если $value не задано, bean-компонент не предоставляется методу параметров свойства.
     *
     * @param mixed $value Значение свойства
     * @param string $property Имя свойства, по умолчанию id
     */
    public function populateProperties($value = false, $property_name = 'id')
    {
        if ($value) {
            $bean = R::findOne($this->type, $property_name . ' = :value ', [ ':value' => $value ]);
            if (!$bean) {
                throw new \Exception('Модель ' . $this->type . ' не существует.');
            }
        } else {
            $bean = false;
        }
        foreach ($this->properties as $key => $property) {
            // Проверяем метод options в контроллере типа свойства
            $c = new $property['type']();
            if (method_exists($c, 'options')) {
                $this->properties[$key]['options'] = $c->options($bean, $property);
            }
        }
    }

    public function getModule()
    {
        $namespace = explode('\\', (new \ReflectionClass($this))->getNamespaceName());
        return end($namespace);
    }
}

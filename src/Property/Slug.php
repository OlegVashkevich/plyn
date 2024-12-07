<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Контроллер для свойства символьного кода Plyn.
 * Создает символьный код из строки и проверяет, является ли он уникальным.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Slug
{
    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     * Если $new_value не установлен - символьный код берется из заголовка.
     *
     * @param bean $bean Объект компонента Redbean со свойством.
     * @param array $property Массив свойств модели Plyn.
     * @param string $new_value Входная строка для символьного кода объекта с этим свойством.
     *
     * @return string Новый символьный код объекта с этим свойством.
     */
    public function set($bean, $property, $new_value)
    {
        if ($new_value && strlen($new_value) > 0) {
            return $this->makeSlug($bean, $property['name'], $new_value);
        } elseif ($bean->title) {
            return $this->makeSlug($bean, $property['name'], $bean->title);
        } else {
            return $bean->id;
        }
    }

    /**
     * Обрезаем строку, не обрезая слова.
     *
     * @param string $str Строка, с которой мы работаем
     * @param inyteger $n Количество символов для обрезки
     * @param string $delim Разделитель. По умолчанию: ''
     *
     * @return string Обрезанная строка.
     */
    private function nTrim($str, $n, $delim = '')
    {
        $len = strlen($str);
        if ($len > $n) {
            preg_match('/(.{' . $n . '}.*?)\b/', $str, $matches);
            return rtrim($matches[1]) . $delim;
        } else {
            return $str;
        }
    }

    /**
     * Проверяет, является ли символьный код компонента уникальным
     *
     * @param bean $bean Компонент, на наличие которого проверяется символьный код.
     * @param string $property_name Имя свойства, на наличие которого проверяется символьный код.
     * @param string $slug Символьный код, который проверяется.
     *
     * @return boolean Возвращает true, если символьный код уникален, и false в противном случае.
     */
    private function uniqueSlug($bean, $property_name, $slug)
    {
        $other = R::findOne($bean->getMeta('type'), $property_name . ' = ? ', [ $slug ]);
        if ($other) {
            if ($other->id == $bean->id) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Преобразовать текстовую строку в допустимую строку, пригодную для чтения в URL.
     *
     * @param string $text Строка для преобразования.
     *
     * @return string Преобразованная строка.
     */
    private function slugify($text)
    {
        // заменить не букву или цифру на -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // транслитерирование
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // удалить нежелательные символы
        $text = preg_replace('~[^-\w]+~', '', $text);

        // подрезка
        $text = trim($text, '-');

        // удаляем дубликаты -
        $text = preg_replace('~-+~', '-', $text);

        // строчные буквы
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Возвращает уникальный символьный код для компонента с максимальным количеством символов 100 с полными словами.
     *
     * @param bean $bean Компонент, для которого создается символьный код.
     * @param string $property_name Имя свойства, для которого создается символьный код.
     * @param string $slug_string Входная строка для символьного кода.
     *
     * @return string Символьный код.
     */
    private function makeSlug($bean, $property_name, $slug_string)
    {
        $string = $this->nTrim($slug_string, 100); // Максимум 100 символов с полными словами
        $slug = $this->slugify($string);
        if ($this->uniqueSlug($bean, $property_name, $slug)) {
            return $slug;
        } else {
            // Создаем символьный код с uniqid() и проверяем еще раз
            return $this->makeSlug($bean, $property_name, $slug . '-' . uniqid());
        }
    }
}

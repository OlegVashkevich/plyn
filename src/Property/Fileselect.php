<?php

namespace Plyn\Property;

/**
 * Контроллер для свойства выбора файлов Plyn.
 * Позволяет пользователю выбрать файл из каталога.
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Fileselect
{
    /**
     * Метод options возвращает все необязательные значения для этого свойства.
     * $property['pattern'] содержит шаблон glob для поиска соответствующих путей.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return array Массив с вложенными массивами, содержащими путь и имя файла.
     */
    public function options($bean, $property)
    {
        $path = __DIR__ . '/../../public';
        $return = [];
        $extensions = str_replace('', ' ', $property['extensions']); // Удаляем пробелы
        $pattern = $path . $property['directory'] . '/*.{' . $extensions . '}'; // glob шаблон
        $files = glob($pattern, GLOB_BRACE);
        foreach ($files as $file) {
            $return[] = [
                'path' => substr($file, strlen($path)),
                'name' => basename($file)
            ];
        }
        return $return;
    }
}

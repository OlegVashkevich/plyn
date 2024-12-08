<?php

namespace Plyn\Property;

use Sirius\Upload\Handler as UploadHandler;

/**
 * Контроллер для свойства загрузки.
 * Позволяет пользователю загружать файл. Использует https://github.com/siriusphp/upload
 *
 * Контроллер типа свойства может содержать методы set, read, delete и options. Все методы являются необязательными.
 */

class Upload
{
    /**
     * Метод set выполняется каждый раз, когда устанавливается свойство с этим типом.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     *
     * @return string Если загружен новый файл, он возвращает новый путь к файлу относительно PATH.
     * Для целей проверки, если новый файл не загружен, он возвращает текущее значение.
     */
    public function set($bean, $property, $new_value)
    {
        if (isset($_FILES[ $property['name'] ]) && $_FILES[ $property['name'] ]['size'] > 0) {
            $uploadHandler = new UploadHandler(__DIR__ . '/../../public' . $property['directory']);

            // Проверка
            if (isset($property['validate'])) {
                foreach ($property['validate'] as $rule) {
                    $uploadHandler->addRule($rule[0], $rule[1]);
                }
            }

            $result = $uploadHandler->process($_FILES[ $property['name'] ]);

            if ($result->isValid()) {
                try {
                    $result->confirm(); // это удалит файл .lock
                    $this->delete($bean, $property); // Удалияем старый файл
                    return $property['directory'] . '/' . $result->name;
                } catch (\Exception $e) {
                    // что-то пошло не так, загруженные файлы нам больше не нужны
                    $result->clear();
                    throw $e;
                }
            } else {
                // Файл не был перемещен в контейнер
                throw new \Exception('Ошибка загрузки. ' . implode(', ', $result->getMessages()));
            }
        } elseif ($bean->{ $property['name'] }) {
            return $bean->{ $property['name'] };
        }
    }

    /**
     * Метод delete выполняется каждый раз при удалении объекта со свойством этого типа.
     *
     * @param bean $bean Объект bean Readbean для этого свойства.
     * @param array $property Массив свойств модели Plyn.
     */
    public function delete($bean, $property)
    {
        // Удаляем файл
        $path = __DIR__ . '/../../public' . $property['directory'];
        $file = __DIR__ . '/../../public' . $bean->{ $property['name'] };
        if (file_exists($file) && substr($file, 0, strlen($path)) == $path) {
            unlink($file);
        }
    }
}

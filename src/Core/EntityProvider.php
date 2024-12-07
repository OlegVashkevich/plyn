<?php

namespace Plyn\Core;

/**
 * Функции поставщика сущностей
 */

class EntityProvider
{
    /**
     * Подключение модели для типа сущности.
     *
     * @var string $beantype Тип сущности.
     *
     * @return object Модель.
     */
    public static function setupBeanModel($path, $module, $beantype)
    {
        //$module = strtolower( $module );
        $beantype = ucfirst(strtolower($beantype));
        if (!file_exists($path . '/Models/' . $module . '/' . $beantype . '.php')) {
            throw new \Exception('Модель ' . $beantype . ' не существует.');
        }
        // Return model
        $model_name = '\Plyn\Models\\' . ucfirst($module) . '\\' . $beantype;
        return new $model_name();
    }

    /**
     * Получает все типы сущностей из каталога models/module/plyn
     *
     * @return string[] Массив с именами всех типов сущностей
     */
    public static function getBeantypes($path, $module)
    {
        //$module = strtolower( $module );
        $beantypes = glob($path . '/Models/' . $module . '/*.php');

        foreach ($beantypes as $key => $value) {
            $beantypes[$key] = strtolower(substr(
                $value,
                strlen($path . '/Models/' . $module . '/'),
                strlen($value) - strlen($path . '/Models/' . $module . '/') - 4
            ));
        }

        return $beantypes;
    }
}

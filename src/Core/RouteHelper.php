<?php

namespace Plyn\Core;

/**
 * Route helper functions
 */

class RouteHelper
{
    /**
     * Set up a controller for a bean type.
     *
     * @var string $beantype The type of bean.
     *
     * @return object The controller.
     */
    public static function setupBeanModel($path, $module, $beantype)
    {
        //$module = strtolower( $module );
        $beantype = ucfirst(strtolower($beantype));
        if (!file_exists($path . '/Models/' . $module . '/' . $beantype . '.php')) {
            throw new \Exception('The ' . $beantype . ' model does not exist.');
        }
        // Return model
        $model_name = '\Plyn\Models\\' . ucfirst($module) . '\\' . $beantype;
        return new $model_name();
    }

    /**
     * Get all bean types from the models/plyn directory
     *
     * @return string[] Array with names of all bean types
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

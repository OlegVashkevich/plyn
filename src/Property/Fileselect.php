<?php

namespace Plyn\Property;

/**
 * Controller for the Plyn fileselect property.
 * Lets the user select a file from a directory.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Fileselect
{
    /**
     * The options method returns all the optional values for this property.
     * $property['pattern'] contains the glob pattern to find the matching pathnames.
     *
     * @param bean $bean The Redbean bean object with the property.
     * @param array $property Plyn model property arrray.
     *
     * @return array Array with nested arrays containing the path and the name of the file.
     */
    public function options($bean, $property)
    {
        $path = __DIR__ . '/../../public';
        $return = [];
        $extensions = str_replace('', ' ', $property['extensions']); // Remove spaces
        $pattern = $path . $property['directory'] . '/*.{' . $extensions . '}'; // glob pattern
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

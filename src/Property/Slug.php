<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Controller for the Plyn slug property.
 * Creates a slug from a string, and checks if it's unique.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Slug
{
    /**
     * The set method is executed each time a property with this type is set.
     * If $new_value is not set, the slug is based on bean title.
     *
     * @param bean $bean The Redbean bean object with the property.
     * @param array $property Plyn model property arrray.
     * @param string $new_value The input string for the slug of the object with this property.
     *
     * @return string The new slug of the object with this property.
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
     * Trim a string without cutting words.
     * from http://www.justin-cook.com/wp/2006/06/27/php-trim-a-string-without-cutting-any-words/
     *
     * @param string $str String we are operating with
     * @param inyteger $n Character count to cut to
     * @param string $delim Delimiter. Default: ''
     *
     * @return string The trimmed string.
     */
    private function neatTrim($str, $n, $delim = '')
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
     * Checks if the slug of a bean is unique
     *
     * @param bean $bean The bean to check the slug for.
     * @param string $property_name The name of the property to check the slug for.
     * @param string $slug The slug to check.
     *
     * @return boolean Returns true if the slug is unique, false otherwise.
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
     * Turn text string into a valid URL readable string.
     * From http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
     *
     * @param string $text String to convert.
     *
     * @return string Converted string.
     */
    private function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Returns a unique slug for a bean with a maximum of 100 characters with complete words.
     *
     * @param bean $bean The bean to create the slug for.
     * @param string $property_name The property name to create the slug for.
     * @param string $slug_string The input string for the slug.
     *
     * @return string The slug.
     */
    private function makeSlug($bean, $property_name, $slug_string)
    {
        $string = $this->neatTrim($slug_string, 100); // Maximum of 100 characters with complete words
        $slug = $this->slugify($string);
        if ($this->uniqueSlug($bean, $property_name, $slug)) {
            return $slug;
        } else {
            // Create slug with uniqid() and check again
            return $this->makeSlug($bean, $property_name, $slug . '-' . uniqid());
        }
    }
}

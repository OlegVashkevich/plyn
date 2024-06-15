<?php

namespace Plyn\Property;

use Sirius\Validation\Validator;

/**
 * Controller for the Plyn string property.
 * Uses the Siriusphp validation library to validate strings.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Str {

	/**
	 * The set method is executed each time a property with this type is set.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 * @param string	$new_value	The input string of this property.
	 *
	 * @return string	The validated string.
	 */
	public function set($bean, $property, $new_value) {

		if ( isset( $property['validate'] ) ) {

			$validator = new Validator();
			$validator->add( [ $property['name'] => $property['validate'] ] ); // Validator rule(s) need to be an array

			if ( $validator->validate( [ $property['name'] => $new_value ] ) ) { // Validator needs an array as input
				return $new_value;
			} else {
				$messages = $validator->getMessages();
				throw new \Exception( 'Vaildation error. ' . implode( ', ', $messages[ $property['name'] ] ) );
			}

		} else {
			return $new_value;
		}

	}

}

?>
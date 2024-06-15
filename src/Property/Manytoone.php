<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Controller for the Plyn mamy-to-one property.
 * Lets the user define a may-to-one relation between two content objects.
 * The Manytoone property type controller enables a many-to-one relation between 2 Plyn models,
 * as described here: http://redbeanphp.com/index.php?p=/many_to_one
 * The name of the property should be the name of the Plyn model this model can have a many-to-one
 * relation with. For this to work properly the other model should have a one-to-many relation with this model.
 * So in our example in the Plyn project the Plyn Author model has a many-to-one relation
 * with the Plyn Book model, and the Plyn Book model has a one-to-many relation
 * with the Plyn Author model.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Manytoone {

	/**
	 * The read method is executed each time a property with this type is read.
	 *
	 * @param bean		$bean		The Readbean bean object with this property.
	 * @param string[]	$property	Plyn model property arrray.
	 *
	 * @return bean[]	Array with Redbean beans with a many-to-many relation with the entry with this property.
	 */
	public function read($bean, $property) {
		return R::findOne( $property['name'], ' id = :id ', [ ':id' => $bean[$property['name'].'_id'] ] );
	}

	/**
	 * The set method is executed each time a property with this type is set.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 * @param integer	$new_value	The id of the object the object with this property has a many-to-one relation with.
	 *
	 * @return bean		The Redbean bean object the object with this property has a many-to-one relation with.
	 */
	public function set($bean, $property, $new_value) {
		echo '<pre>'; print_r('ttt2'); echo '<pre>'; 
		// Check and set relation
		$relation = R::findOne( $property['name'], ' id = :id ', [ ':id' => $new_value ] );
		if ( !$relation ) {
			throw new \Exception('This '.$property['name'].' does not exist.');
		} else {
			return $relation;
		}

	}

	/**
	 * The options method returns all the optional values this property can have,
	 * including the one it currently has.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 *
	 * @return array	Array with all beans of the $property['name'] Plyn model.
	 */
	public function options($bean, $property) {
		return R::findAll( $property['name'] );
	}

}

?>
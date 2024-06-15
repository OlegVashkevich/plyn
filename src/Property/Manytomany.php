<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Controller for the Plyn many-to-many property.
 * Lets the user define a many-to-many relation between two content entries.
 * The Manytomany property type controller enables a many-to-many relation between 2 Plyn models.
 * The name of the property should be the name of the Plyn model this model can have a many-to-many
 * relation with. For this to work properly the other model should have a many-to-many relation with this model as well.
 * So in our example in the Plyn project the Plyn Book model has a many-to-many relation
 * with the Plyn Genre model, and the Plyn Genre model has a many-to-many relation with the Plyn Book model.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Manytomany {

	/**
	 * The set method is executed each time a property with this type is set.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 * @param integer[]	$new_value	An array with id's of the objects the object with this property has a many-to-many relation with.
	 *
	 * @return boolean	Returns a boolean because a many-to-many relation is automaticaly stored in a separate database table. Returns true if any relations are set, false if not.
	 */
	public function set($bean, $property, $new_value) {

		$list = [];
		foreach ($new_value as $id) {
			if ($id) {
				$list[] = R::load( $property['name'], $id );
			}
		}

		if ( count( $list ) > 0 ) {

			$bean->{ 'shared'.ucfirst($property['name']).'List' } = $list;
			R::store($bean);

			return true;

		} else {

			return false;

		}

	}

	/**
	 * The read method is executed each time a property with this type is read.
	 *
	 * @param bean		$bean		The Readbean bean object with this property.
	 * @param string[]	$property	Plyn model property arrray.
	 *
	 * @return bean[]	Array with Redbean beans with a many-to-many relation with the entry with this property.
	 */
	public function read($bean, $property) {

		// NOTE: We're not executing the read method for each bean. Before I implement this I want to check potential performance issues.
		return  $bean->{ 'shared'.ucfirst($property['name']).'List' };

	}

	/**
	 * The options method returns all the optional values this property can have,
	 * but NOT the ones it currently has.
	 *
	 * @param bean		$bean		The Readbean bean object with this property.
	 * @param array		$property	Plyn model property arrray.
	 *
	 * @return bean[]	Array with all beans of the $property['name'] Plyn model.
	 */
	public function options($bean, $property) {

		if ( $bean ) {

			// List of beans who allready have a many-to-many ralation with this bean
			$relations = $bean->{ 'shared'.ucfirst($property['name']).'List' };
			if ($relations) {

				$relations_ids = [];
				foreach ($relations as $relation) {
					$relations_ids[] = $relation->id;
				}

				return	R::find( $property['name'],
						' id NOT IN ('.R::genSlots( $relations_ids ).') ',
						$relations_ids );
			} else {
				return R::findAll( $property['name'] );
			}

		} else {

			return R::findAll( $property['name'] );

		}

	}

}

?>
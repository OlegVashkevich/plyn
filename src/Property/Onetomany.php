<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Controller for the Plyn one-to-many property.
 * Lets the user define a one-to-many relation between two content objects.
 * The Onetomany property type controller enables a one-to-many relation between 2 Plyn models.
 * The name of the property should be the name of the Plyn model this model can have a one-to-many
 * relation with. For this to work properly the other model should have a many-to-one relation with this model.
 * So in our example in the Plyn project the Plyn Book model has a one-to-many relation
 * with the Plyn Author model, and the Plyn Author model has a many-to-one relation with the Plyn Book model.
 *
 */

class Onetomany {

	/**
	 * The set method is executed each time a property with this type is set.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 * @param integer[]	$new_value	An array with id's of the objects the object with this property has a one-to-many relation with.
	 *
	 * @return boolean	Returns a boolean because a one-to-many relation is only set in the bean with the one-to-many relation. Returns true if any relations are set, false if not.
	 */
	public function set($bean, $property, $new_value) {

		// List of child beans to store
		$children = [];

		// Set up child model to read properties
		$model_name = '\Plyn\Models\\' . ucfirst($property['module']).'\\' . ucfirst($property['name']);
		$child = new $model_name();

		$relative_position = false;
		foreach($child->properties as $p) {
			if ( $p['type'] === '\\Plyn\\Property\\Position' && $p['manytoone'] ) {
				$relative_position = true;
				$position_property_name = $p['name'];
				break;
			}
		}

		if ( $relative_position ) {

			// Check if the parent of children has changed.
			// If so update all positions for old and new parent.

			//$old_children = $bean->{ 'own'.ucfirst($property['name']).'List' };
			$old_children = R::find( $property['name'], $bean->getMeta('type').'_id = :id ORDER BY '.$position_property_name.' ASC ', [ ':id' => $bean->id ] );
			$old_children_ids = [];
			$position = 0;
			foreach ( $old_children as $old_child ) {

				// Reset position of remaining old children, set position of removed old children to 0
				if ( in_array( $old_child->id, $new_value ) ) {

					$old_child->{ $position_property_name } = $position;
					$children[] = $old_child;
					$position++;

					// Create array with id's for next step
					$old_children_ids[] = $old_child->id;

				} else {

					$old_child->{ $position_property_name } = 0;
					$old_child->{ $bean->getMeta('type') } = NULL; // Remove parent before storing
					R::store($old_child);

				}

			}

			// Check if new children have been added
			$bottom_position = count($old_children_ids);
			foreach ( $new_value as $new_child_id ) {
				if ( $new_child_id && !in_array( $new_child_id, $old_children_ids ) ) {

					// Add new child to bottom position
					$new_child = R::load( $property['name'], $new_child_id );
					$new_child->{ $position_property_name } = $bottom_position;
					$children[] = $new_child;
					$bottom_position++;

				}
			}

		} else {

			// No relative position
			foreach ($new_value as $id) {
				if ($id) {
					$children[] = R::load( $property['name'], $id );
				}
			}

		}

		// Store list
		if ( count( $children ) > 0 ) {

			$bean->{ 'own'.ucfirst($property['name']).'List' } = $children;
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
	 * @param string[]		$property	Plyn model property arrray.
	 *
	 * @return bean[]	Array with Redbean beans with a many-to-one relation with the object with this property.
	 */
	public function read($bean, $property) {

		// NOTE: We're not executing the read method for each bean. Before I implement this I want to check potential performance issues.
		//return  $bean->{ 'own'.ucfirst($property['name']).'List' };

		// Set up child model to read properties
		$model_name = '\Plyn\Models\\' . ucfirst($property['module']).'\\' . ucfirst($property['name']);
		$child = new $model_name();

		// All beans with this parent
		// Oder by position(s) if exits
		// NOTE: We're not executing the read method for each bean. Before I implement this I want to check potential performance issues.
		$add_to_query = '';
		foreach($child->properties as $p) {
			if ( $p['type'] === '\\Plyn\\Property\\Position' ) {
				$add_to_query = $p['name'].' ASC, ';
			}
		}
		return R::find( $property['name'], $bean->getMeta('type').'_id = :id ORDER BY '.$add_to_query.'title ASC ', [ ':id' => $bean->id ] );

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

			// Return only beans with other or now $col_name id
			$col_name = $bean->getMeta( 'type' ) . '_id';
			return	R::find( $property['name'],
					' '.$col_name.' != ? OR  '.$col_name.' IS NULL ',
					[ $bean->id ] );

		} else {

			return R::findAll( $property['name'] );

		}

	}

}

?>
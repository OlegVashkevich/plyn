<?php

namespace Plyn\Property;

use RedBeanPHP\R as R;

/**
 * Controller for the Plyn position property.
 * Makes room for the newly positioned Redbean bean by updating the positions of other beans,
 * and returns the new position of the bean.
 *
 * A property type controller can contain a set, read, delete and options method. All methods are optional.
 */

class Position {

	/**
	 * The set method is executed each time a property with this type is set.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 * @param integer	$new_value	The input position of the object with this property.
	 *
	 * @return integer	The new position of the object with this property.
	 */
	public function set($bean, $property, $new_value) {

		if ( !empty($new_value) || $new_value === 0 || $new_value === '0' ) {
			$new_value = intval( $new_value ); // Convert to integer
		}

		// Absolute position or relative to many-to-one relation?
		if ( isset( $property['manytoone'] ) ) {

			// Relative

			// Check if parent has changed
			$old_parent_id = $bean->{ $property['manytoone'] . '_id' };
			$new_parent_id = $bean->{ $property['manytoone'] }->id;

			$all = R::find( $bean->getMeta('type'), $property['manytoone'] . '_id = ? ', [ $new_parent_id ] );
			$count_all = R::count( $bean->getMeta('type'), $property['manytoone'] . '_id = ? ', [ $new_parent_id ] );

			// Check if parent has changed
			if ( $old_parent_id !== $new_parent_id ) {

				$count_all++; // Add 1 because bean has new parent
				$all[] = $bean; // Add bean to result for new parent
				$new_value = $count_all - 1; // Add bean to the bottom of new parent

				// Parent has changed, update old parent positions
				$old_siblings = R::find( $bean->getMeta('type'), $property['manytoone'] . '_id = :parent_id AND id != :bean_id ORDER BY :property ASC ', [ ':parent_id' => $old_parent_id, ':bean_id' => $bean->id, ':property' => $property['name'] ] );
				$pos = 0;
				foreach ( $old_siblings as $s ) {
					$s->{ $property['name'] } = $pos;
					$s->modified = R::isoDateTime();
					R::store($s);
					$pos++;
				}

			}

		} else {

			// Absolute
			$all = R::findAll( $bean->getMeta('type') );
			$count_all = R::count( $bean->getMeta('type') );

		}

		$curr_value = $bean->{ $property['name'] };

		// New bean
		if ( empty($curr_value) && $curr_value !== 0 && $curr_value !== '0' ) {
		
			// Position at the bottom
			$curr_value = $count_all;
		
		}
		
		// No new input
		if ( ( empty($new_value) && $new_value !== 0 ) || $new_value == $curr_value ) {
		
			return $curr_value;
		
		} else {
		
			if ( $new_value > $count_all - 1 ) $new_value = $count_all - 1;
			if ( $new_value < 0 ) $new_value = 0;
			if ( $new_value < $curr_value ) {
				foreach ( $all as $b ) {
					if ($b->{ $property['name'] } >= $new_value AND $b->{ $property['name'] } < $curr_value) {
						$b->{ $property['name'] } = $b->{ $property['name'] } + 1;
						$b->modified = R::isoDateTime();
						R::store($b);
					}
				}
			} else if ( $new_value > $curr_value ) {
				foreach ( $all as $b ) {
					if ( $b->{ $property['name'] } <= $new_value AND $b->{ $property['name'] } > $curr_value ) {
						$b->{ $property['name'] } = $b->{ $property['name'] } - 1;
						$b->modified = R::isoDateTime();
						R::store($b);
					}
				}
			}
		
			return $new_value;
		
		}

	}

	/**
	 * The delete method is executed each time a an object with a property with this type is deleted.
	 *
	 * @param bean		$bean		The Redbean bean object with the property.
	 * @param array		$property	Plyn model property arrray.
	 */
	public function delete($bean, $property) {

		// Absolute position or relative to many-to-one relation?
		if ( isset( $property['manytoone'] ) ) {

			$count_all = R::count( $bean->getMeta('type'), $property['manytoone'] . '_id = ? ', [ $bean->{ $property['manytoone'] . '_id' } ] );

		} else {

			$count_all = R::count( $bean->getMeta('type') );

		}

		$bottom = $count_all - 1;
		$this->set($bean, $property, $bottom ); // No need to store new position of this bean

	}

}

?>
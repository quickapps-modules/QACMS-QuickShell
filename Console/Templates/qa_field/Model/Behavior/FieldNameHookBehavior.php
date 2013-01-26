<?php
/**
 * FieldName Hook-Behavior
 *
 * Storage handler for `FieldName` Field.
 *
 * @link https://github.com/QuickAppsCMS/QuickApps-CMS/wiki/Field-API
 */
class FieldNameHookBehavior extends ModelBehavior {
/**
 * Before entity's find method.
 * Use this method to modify the find query and alter find's results.
 *
 * @param array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `query`: Array, find query
 *  `field`: Array, field instance information (from `fields` table)
 *  `settings`: Array, fieldable behavior settings of the given entity
 * @return void
 */
	public function field_name_before_find($info) {
		return;
	}

/**
 * Attach field data to entity record.
 * Use this method to append Field's data to entity record.
 * Data must be append in `$info['field']['FieldData']`
 *
 * @param Array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `field`: Array, field instance information (from `fields` table)
 *  `result`: Array, single entity record. e.g.: One single user for the `Users` entity.
 *  `settings`: Array, fieldable behavior settings of the given entity
 * @return void
 */
	public function field_name_after_find(&$info) {
		/**
		 * This example uses the `field_data` table as storage system.
		 * Field are allows to save their data in any table or storage system,
		 */

		// Capture field data for this entity row.
		$data = ClassRegistry::init('Field.FieldData')->find('first',
			array(
				'conditions' => array(
					'FieldData.field_id' => $info['field']['id'],
					'FieldData.belongsTo' => $info['entity']->alias,
					'FieldData.foreignKey' => $info['result'][$data['entity']->alias][$data['entity']->primaryKey]
				)
			)
		);

		// fetch data to entity row
		$info['field']['FieldData'] = $data['FieldData'];

		return;
	}

/**
 * Validate POST field data.
 * Use this method to validate field's POST-data before entity is saved.
 * Returning a non-true value will stop entity saving process.
 *
 * @param Array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `id`: Mixed, storage record ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.id`
 *		A `null` value means create a new storage record.
 *		An integer value will update the existing record.
 *  `data`: Mixed, storage data. From `edit.ctp`: `FieldData.FieldText.{field_id}.data`
 *  `field_id`: Integer, field instance ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.<id|data>`
 *  `settings`: Array, fieldable behavior settings of the given entity
 *  `data`: Mixed, POST data from field's inputs (in edit.ctp)
 * @return boolean
 */
	public function field_name_before_validate(&$info) {
		// get field instance information
		$FieldInstance = ClassRegistry::init('Field.Field')->findById($info['field_id']);

		// validate field's data only if field is required
		if ($FieldInstance['Field']['required'] == 1) {
			if (empty($info['data'])) {
				// error message
				ClassRegistry::init('Field.FieldData')->invalidate("FieldName.{$info['field_id']}.data", 'This field cannot be empty.');

				return false;
			}
		}

		return true;
	}

/**
 * After entity validation process and before data is saved.
 * Returning a non-true value will stop entity's save process.
 *
 * @param Array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `id`: Mixed, storage record ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.id`
 *		A `null` value means create a new storage record.
 *		An integer value will update the existing record.
 *  `data`: Mixed, storage data. From `edit.ctp`: `FieldData.FieldText.{field_id}.data`
 *  `field_id`: Integer, field instance ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.<id|data>`
 *  `settings`: Array, fieldable behavior settings of the given entity
 *  `data`: Mixed, POST data from field's inputs (in edit.ctp)
 * @return boolean
 */
	public function field_name_before_save(&$info) {
		return true;
	}

/**
 * After validation process and after entity has been saved.
 * Here is where field MUST save its data.
 *
 * @param Array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `id`: Mixed, storage record ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.id`
 *		A `null` value means create a new storage record.
 *		An integer value will update the existing record.
 *  `data`: Mixed, storage data. From `edit.ctp`: `FieldData.FieldText.{field_id}.data`
 *  `field_id`: Integer, field instance ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.<id|data>`
 *  `created`: Boolean, TRUE if entity's save created a new record
 *  `settings`: Array, fieldable behavior settings of the given entity
 * @return void
 */
	public function field_name_after_save(&$info) {
		/**
		 * This example uses the `field_data` table as storage system.
		 * Field are allows to save their data in any table or storage system,
		 */

		// save field data in `field_data` table
		ClassRegistry::init('Field.FieldData')->save(array(
			'id' => $info['id'],
			'field_id' => $info['field_id'],
			'data' => $info['data'],
			'belongsTo' => $info['entity']->alias,
			'foreignKey' => $info['entity']->id
		));

		// index field data
		$info['entity']->indexField($info['data'], $info['field_id']);

		return;
	}

/**
 * Before entity records is deleted.
 * Returning a non-true value will stop entity deletion process.
 *
 * @param Array $info Associative array:
 *  `entity`: Object Model, entity reference
 *  `id`: Mixed, storage record ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.id`
 *		A `null` value means create a new storage record.
 *		An integer value will update the existing record.
 *  `data`: Mixed, storage data. From `edit.ctp`: `FieldData.FieldText.{field_id}.data`
 *  `field_id`: Integer, field instance ID. From `edit.ctp`: `FieldData.FieldText.{field_id}.<id|data>`
 *  `settings`: Array, fieldable behavior settings of the given entity
 * @return boolean
 */
	public function field_name_before_delete($info) {
		return true;
	}

/**
 * After entity records has been deleted.
 * Use this method to delete all field data related to
 * the givenentity record.
 *
 * @return void
 */
	public function field_name_after_delete($info) {
		// delete from `field_data` table all the data related to the this entity record
		ClassRegistry::init('Field.FieldData')->deleteAll(
			array(
				'FieldData.belongsTo' => $info['entity']->alias,
				'FieldData.field_id' => $info['field_id'],
				'FieldData.foreignKey' => $info['entity']->id
			)
		);

		return;
	}

/**
 * Before field is attached to entity.
 * Use this method to set default settings for the field instance.
 * Returning a non-true value will stop the attaching process.
 *
 * @param Model $Field Instance object
 * @return boolean
 */
	public function field_name_before_save_instance(Model $Field) {
		return true;
	}

/**
 * After field instance is removed/detached from entity.
 * Use this method to remove all field data in the storage system
 * that is related to the given entity.
 *
 * @param Model $Field Instance object
 * @return boolean
 */
	public function field_name_after_delete_instance(Model $Field) {
		ClassRegistry::init('Field.FieldData')->deleteAll(
			array(
				'FieldData.field_id' => $Field->data['Field']['id']
			)
		);
	}
}
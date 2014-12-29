<?php
/**
 * Meta API
 *
 * plugin api for getting and setting Shopp object meta data
 *
 * @copyright Ingenesis Limited, June 23, 2011
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Meta
 * @version   1.0
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Get a product meta entry by product id, type, and name
 *
 * @api
 * @since 1.2
 *
 * @param int $product product id
 * @param string $name the name of the meta data
 * @param string $type (default: meta) the meta data type
 * @return array of stdClass Object meta values, with parent, type, name, and value properties
 **/
function shopp_product_meta ( $product = false, $name = false, $type = 'meta' ) {
	return shopp_meta( $product, 'product', $name, $type );
}

/**
 * Check for named meta data for a product.
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required) the product id
 * @param string $name (required) the name of the meta entry
 * @param string $type (optional default: meta) the type of meta entry
 * @return bool returns true if meta data exists on the product, false if not
 **/
function shopp_product_has_meta ( $product = false, $name = false, $type = 'meta' ) {
	if ( ! $name || ! $product ) return false;
	$meta = shopp_meta($product, 'product', $name, $type);

	return ( ! empty($meta) );
}

/**
 * Get an array of meta values on the product
 *
 * @api
 * @since 1.2
 *
 * @param int $product The ID of the product record
 * @param string $type The type of meta entries to list
 * @return array list of values keyed by name, false on failure
 **/
function shopp_product_meta_list ( $product = false, $type = 'meta' ) {
	if ( ! $product ) {
		shopp_debug(__FUNCTION__.' failed: product id required');
		return false;
	}
	$metas = shopp_product_meta ( $product, false, $type );

	$results = array();
	foreach ( (array) $metas as $id => $meta ) {
		if ( is_object($meta) ) {
			$results[$meta->name] = $meta->value;
		} else if ( ! empty($meta) ) {
			$results[$id] = $meta;
		}
	}
	return $results;
}

/**
 * shopp_product_meta_count - number of meta entries associated with a product
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required) the product id
 * @param string $type (optional default: meta) the meta type to count
 * @return int count of meta entries, false on failure
 **/
function shopp_product_meta_count ( $product = false, $type = 'meta' ) {
	if ( ! $product ) {
		shopp_debug(__FUNCTION__.' failed: product id required');
		return false;
	}
	$meta = shopp_product_meta ( $product, false, $type );
	return count( $meta );
}

/**
 * Create or update a new product meta record
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required on creation/update) product object to create/update the meta record on
 * @param string $name (required on update) the name of the meta entry, more specific than type
 * @param mixed $value (optional default: false) the value stored to the meta entry
 * @param string $type (optional default: meta) the type or classification of the meta data
 * @param string $valuetype (optional default: 'value') 'numeral' or 'value', if the value is numeric, 'numeric' will store in numeric field.
 * @return bool true on successful save or update, fail on failure
 **/
function shopp_set_product_meta ( $product = false, $name = false, $value = false, $type = 'meta', $valuetype = 'value' ) {
	return shopp_set_meta ( $product, 'product', $name, $value, $type, $valuetype );
}

/**
 * Remove a meta entry by product id and name
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required) - product id of meta entry to remove
 * @param string $name (required with parent object context) - the meta name
 * @param string $type  (optional default: meta) - the meta type
 * @return bool true if the meta entry was removed, false on failure
 **/
function shopp_rmv_product_meta ( $product = false, $name = false, $type = 'meta') {
	if ( ! $product && ! $name ) {
		shopp_debug(__FUNCTION__.' failed: product and name parameters required.');
		return false;
	}
	return shopp_rmv_meta ( $product, 'product', $name, $type );
}

/**
 * Returns meta data assigned to an object.
 *
 * @api
 * @since 1.2
 *
 * @param int $id (optional) of the meta entry, or object id of the object the Shopp meta is attached to
 * @param string $context (optional) the object type that the object id refers to.
 * @param string $name (optional) the name of the meta data
 * @param string $type (optional default: meta) the data type of meta data (examples meta, spec, download, image, yourdatatype )
 * @return array of stdClass Object meta values, with parent, type, name, and value properties
 *
 * Usage Examples:
 * shopp_meta(<id>) - meta record by id
 * shopp_meta([id], [context], [name], [type]) - pick one or more, id is the id of the parent contextual object if context is specified
 *
 * shopp_meta(1) loads meta record 1
 * shopp_meta(5,'product','Producer','spec') loads spec named Producer of product id 5
 * shopp_meta(false, 'product','mydownload.zip','downloads') load the meta record for mydownload.zip product download
 * shopp_meta(5, 'product', false, 'downloads') load all product download meta records for product id 5
 * shopp_meta(false, 'price') loads all meta data associated with variants
 *
 **/
function shopp_meta ( $id = false, $context = false, $name = false, $type = 'meta' ) {
	$values = array();

	if ( ! ( $id || $context || $name ) ) {
		shopp_debug(__FUNCTION__.' failed: No parameters specified.');
		return;
	}

	// Load meta by id
	if ( $id && false === $context ) {
		$meta = new ShoppMetaObject();
		$meta->load($id);

		if ( empty($meta->id) ) {
			shopp_debug(__FUNCTION__ . " failed: No such meta with id $id or missing context.");
		}

		return $meta->value;
	}

	// Load one or more meta
	$loading = array();
	if ( $id && $context ) $loading['parent'] = $id; // if context is specified, id will always be parent object
	if ( $context ) $loading['context'] = $context;
	if ( $type ) $loading['type'] = $type;
	if ( $name ) $loading['name'] = $name;

	$Meta = new ObjectMeta();
	$Meta->load( $loading );

	if ( empty($Meta->meta) ) return array();

	foreach ( $Meta->meta as $meta ) {
		if( ! isset($values[$meta->id]) ) $values[$meta->id] = new stdClass;
		$values[$meta->id]->parent = $meta->parent;
		$values[$meta->id]->type = $meta->type;
		$values[$meta->id]->name = $meta->name;
		if ( empty($meta->value) && $meta->numeral > 0 ) $meta->value = $meta->numeral;
		$values[$meta->id]->value = $meta->value;
	}

	if ( $id && $context && $type && $name and 1 == count($values)) {
		return reset($values)->value;
	}

	return $values;
}

/**
 * Determine if one or more meta records exist based on some combination of context, and/or type, and/or name of the metadata
 *
 * One or more of the parameters must be specified.
 *
 * @api
 * @since 1.2
 *
 * @param string $name (optional) name of the meta entry
 * @param string $context (optional) object context of the meta entry
 * @param string $type (optional default: meta) type of the meta entry
 * @return bool true if one or more meta entries exist
 **/
function shopp_meta_exists ( $name = false, $context = false, $type = 'meta' ) {
	if ( ! ( $name || $context ) ) return false;
	$meta = shopp_meta(false, $context, $name, $type);
	return (bool)( $meta );
}

/**
 * Create or update a new meta record
 *
 * @api
 * @since 1.2
 *
 * @param int $id (required on creation/update) id of an existing meta entry, or with context the parent object of a new meta entry
 * @param string $context (required on update) the parent object type of the meta entry (example product, price, and more)
 * @param string $name (required on update) the name of the meta entry, more specific than type
 * @param mixed $value (optional default: false) the value stored to the meta entry
 * @param string $type (optional default: meta) the type or classification of the meta data
 * @param string $valuetype (optional default: 'value') 'numeral' or 'value', if the value is numeric, 'numeric' will store in numeric field.
 * @return bool true on successful save or update, fail on failure
 **/
function shopp_set_meta ( $id = false, $context = false, $name = false, $value = false, $type = 'meta', $valuetype = 'value' ) {
	if ( ! ( $id || $id && $context ) ) {
		shopp_debug(__FUNCTION__ . " failed: Must specify at least a meta id or parent id and context.");
		return false;
	}

	$record = array();
	if ( $context ) $record['context'] = $context;
	if ( $type ) $record['type'] = $type;
	if ( $name ) $record['name'] = $name;

	$valuefield = array();
	$valuefield[( 'numeral' == $valuetype && is_numeric($value) ? 'numeral' : 'value' )]  = $value;

	// save existing meta record by meta id
	if ( $id && ! $context ) {
		$meta = new ShoppMetaObject();
		$meta->load($id);
		if ( ! empty($meta->id) ) {
			$meta->updates( array_merge($record, $valuefield) );
			$meta->save();
			return true;
		} else {
			shopp_debug(__FUNCTION__ . " failed: No metadata with id $id.");
			return false;
		}
	}

	// fully spec'd meta entry
	if ( $id && $context && $type && $name ) {
		$meta = new ShoppMetaObject();
		$meta->load( array_merge( $record, array( 'parent'=>$id, 'context'=>$context ) ) );
		$meta->updates( array_merge(array( 'parent'=>$id, 'context'=>$context ), $record, $valuefield) );
		$meta->save();
		return true;
	}
	shopp_debug(__FUNCTION__.' failed: id, context, type, and name are required parameters for this context.');
	return false;
}

/**
 * Remove a meta entry by meta id, or parent id, context, type, and name
 *
 * @api
 * @since 1.2
 *
 * @param int $id (required) - meta entry id or, with context, the parent object id
 * @param int $context (required for parent object id) - the parent object context
 * @param string $name (required with parent object context) - the meta name
 * @param string $type  (optional default: meta) - the meta type
 * @return bool true if the meta entry was removed, false on failure
 **/
function shopp_rmv_meta ( $id = false, $context = false, $name = false, $type = 'meta' ) {
	if ( ! ( $id || $id && $context ) ) {
		shopp_debug(__FUNCTION__ . " failed: Must specify at least a meta id or parent id and context.");
		return false;
	}

	// remove existing meta record by meta id
	if ( $id && ! $context ) {
		$meta = new ShoppMetaObject();
		$meta->load($id);
		if ( $meta->id ) $meta->delete();
		return true;
	}

	// fully spec'd meta entry
	if ( $id && $context && $type && $name ) {
		$meta = new ShoppMetaObject();
		$meta->load( array( 'parent'=>$id, 'context'=>$context, 'type' => $type, 'name' => $name ) );

		if ( $meta->id ) $meta->delete();
		return true;
	}

	// general meta entries
	if ( $id && $context ) {
		$table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		$id = db::escape($id);
		$context = db::escape($context);
		$name = db::escape($name);
		$type = db::escape($type);

		$where = "parent=$id AND context='$context'";
		$where .= ( $type && ! empty($type) ? " AND type='$type'" : "" );
		$where .= ( $name && ! empty($name) ? " AND type='$name'" : "" );

		return db::query("DELETE FROM $table WHERE $where");
	}

}
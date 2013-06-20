<?php
/**
 * settings
 *
 * plugin API for getting, setting/creating, and deleting Shopp settings.
 *
 * @author Jonathan Davis, John Dillick
 * @version 1.0
 * @copyright Ingenesis Limited, June 23, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.2
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * shopp_setting - returns a named Shopp setting
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param string $name The name of the setting
 * @return mixed the value saved to the named setting, or false if not set.  returns null if empty name is provided
 **/
function shopp_setting ( $name ) {
	$setting = null;

	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . " failed: Setting name parameter required.");
		return false;
	}

	$setting = ShoppSettings()->get($name);

	return $setting;
}

/**
 * Returns true or false if the setting is toggled on or off
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param string $name The name of the setting
 * @return boolean True is enabled, false is disabled
 **/
function shopp_setting_enabled ( $name ) {
	$setting = shopp_setting($name);
	return Shopp::str_true($setting);
}

/**
 * shopp_set_setting - saves a name value pair as a Shopp setting
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param string $name The name of the setting that is to be stored.
 * @param mixed $value The value saved to the named setting.
 * @return bool true on success, false on failure.
 **/
function shopp_set_setting ( $name, $value ) {
	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . " failed: Setting name parameter required.");
		return false;
	}

	ShoppSettings()->save($name, $value);
	return true;
}

/**
 * shopp_rmv_setting - deletes a named setting
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param string $name Name of the Shopp setting to be deleted
 * @return bool true on success, false on failure
 **/
function shopp_rmv_setting ($name) {
	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . " failed: Setting name parameter required.");
		return false;
	}
	return ShoppSettings()->delete($name);
}

/**
 * shopp_set_formsettings - saves a name value pair as a Shopp setting
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param string $name The name of the setting that is to be stored.
 * @param mixed $value The value saved to the named setting.
 * @return bool true on success, false on failure.
 **/
function shopp_set_formsettings () {
	if (empty($_POST['settings']) || !is_array($_POST['settings'])) {
		shopp_debug(__FUNCTION__ . " failed: Setting name parameter required.");
		return false;
	}
	ShoppSettings()->saveform();
	return true;
}

/**
 * shopp_set_image_setting - saves an image setting
 *
 * The settings accept:
 * 		width => (pixel width)
 * 		height => (pixel height)
 * 		size => (pixels, sets width and height)
 * 		fit => (all,matte,crop,width,height)
 * 		quality => (0-100 quality percentage)
 * 		sharpen => (0-100 sharpen percentage)
 * 		bg => (hex color, such as red: #ff0000)
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param string $name The name of the setting that is to be stored.
 * @param array $settings A named array of settings and values, accepts: width, height, size, fit, quality, sharpen, bg
 * @return bool true on success, false on failure.
 **/
function shopp_set_image_setting ($name,$settings = array()) {
	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . " failed: Setting name parameter required.");
		return false;
	}

	$defaults = array(
		'width' => false,
		'height' => false,
		'fit' => 'all',
		'size' => 96,
		'quality' => 100,
		'sharpen' => 100,
		'bg' => false
	);
	if (isset($settings['size']))
		$settings['width'] = $settings['height'] = $settings['size'];

	$settings = array_merge($defaults,$settings);

	if (in_array($settings['fit'],ImageSetting::$fittings))
		$settings['fit'] = array_search($settings['fit'],ImageSetting::$fittings);

	// Load/update an existing one there is one
	$ImageSetting = new ImageSetting($name,'name');
	$ImageSetting->name = $name;
	foreach ($settings as $prop => $value)
		$ImageSetting->$prop = $value;

	$ImageSetting->save();
	return true;
}
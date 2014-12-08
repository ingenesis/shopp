<?php
/**
 * Storage.php
 *
 * Class library for storage engines support
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, March 2008
 * @package shopp
 * @since 1.1
 * @version 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * StorageEngines class
 *
 * Storage engine file manager to load storage engines that are active.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage storage
 **/
class StorageEngines extends ModuleLoader {

	protected $interface = 'StorageEngine';
	protected $paths =  array(SHOPP_STORAGE, SHOPP_ADDONS);

	public $engines = array();
	public $contexts = array('image', 'download');
	public $activate = false;

	/**
	 * Initializes the shipping module loader
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {

		if ( function_exists('add_action') )
			add_action('shopp_module_loaded', array($this, 'actions'));

		$this->installed();
		$this->activated();
		$this->load();
	}

	/**
	 * Determines the activated storage engine modules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of module names for the activated modules
	 **/
	public function activated () {
		global $Shopp;

		$this->activated = array();

		$systems = array();
		$systems['image'] = shopp_setting('image_storage');
		$systems['download'] = shopp_setting('product_storage');

		foreach ( $systems as $system => $storage ) {
			foreach ( $this->modules as $engine ) {
				if ( $engine->classname == $storage ) {
					$this->activated[] = $engine->classname;
					$this->engines[ $system ] = $engine->classname;
					break; // Check for next system engine
				}
			}
		}

		return $this->activated;
	}

	/**
	 * Get a specific storage engine
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return StorageEngine or false if not able to be loaded
	 **/
	public function &get ( $module ) {
		$false = false;

		if ( empty($this->active) )
			$this->activate($module);

		if ( ! isset($this->active[ $module ]) )
			return $false;

		return $this->active[$module];
	}

	/**
	 * Gets the module name for the StorageEngine context type
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $type The context type
	 * @return string The engine module name or false
	 **/
	public function type ( $type ) {
		if ( ! isset($this->engines[ $type ]) ) return false;
		return $this->engines[ $type ];

	}

	/**
	 * Loads all the installed storage engine modules for the settings page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function settings () {
		$this->load(true);
	}

	/**
	 * Initializes the settings UI for each loaded module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function ui () {
		foreach ( $this->contexts as $context ) {
			foreach ( $this->active as $package => &$module ) {
				$module->context($context);
				$module->initui($package, $context);
			}
		}
	}

	public function templates () {
		foreach ( $this->active as $package => &$module )
			$module->uitemplate($package, $this->modules[ $package ]->name);
	}


	public function actions ( $module ) {
		if ( ! isset($this->active[ $module ]) ) return;

		// Register contexts the module is a handler for
		foreach ( $this->engines as $system => $handler )
			if ($module == $handler) $this->active[ $module ]->contexts[] = $system;

		if ( method_exists($this->active[ $module ], 'actions') )
			$this->active[ $module ]->actions();
	}

}

/**
 * StorageEngine interface
 *
 * Provides a template for storage engine modules to implement
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage storage
 **/
interface StorageEngine {

	/**
	 * Load a resource by the uri
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The uniform resource indicator
	 **/
	public function load( $uri );

	/**
	 * Output the asset data of a given uri
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The uniform resource indicator
	 * @return void
	 **/
	public function output( $uri );

	/**
	 * Checks if the binary data of an asset exists
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The uniform resource indicator
	 * @return boolean
	 **/
	public function exists( $uri );


    /**
     * Returns a web-accessible URL that allows the asset to be accessed and served directly (rather than, for example,
     * passing through Shopp/a Shopp server like the Shopp Image Server). If a direct URL does not exist for this asset
     * then boolean false will be returned.
     *
     * @param int $uri
     * @return mixed false | string
     */
    public function direct( $uri );


	/**
	 * Store the data for an asset
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param FileAsset $asset The parent asset for the data
	 * @param mixed $data The raw data to be stored
	 * @param string $type (optional) Type of data source, one of binary or file (file referring to a filepath)
	 **/
	public function save( $asset, $data, $type = 'binary' );

}

/**
 * StorageModule class
 *
 * A framework for storage engine modules.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage storage
 **/
abstract class StorageModule {

	public $contexts;
	public $settings;


	public function __construct () {
		global $Shopp;
		$this->module = get_class($this);
		$this->settings = shopp_setting($this->module);
	}

	public function context ($setting) {

	}

	public function settings ($context) {

	}

	/**
	 * Generate the settings UI for the module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $module The module class name
	 * @param string $name The formal name of the module
	 * @return void
	 **/
	// public function setupui ($module,$name) {
	// 	$this->ui = new StorageSettingsUI('storage',$module,$name,false,false);
	// 	$this->settings();
	// }

	public function output ( $uri ) {
		$data = $this->load($uri);
		header ("Content-length: " . strlen($data));
		echo $data;
	}

	public function meta ( $arg1 = false, $arg2 = false ) {
		return false;
	}

	public function handles ( $context ) {
		return in_array($context, $this->contexts);
	}

	/**
	 * Generate the settings UI for the module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $module The module class name
	 * @param string $name The formal name of the module
	 * @return void
	 **/
	public function initui ( $name, $context ) {
		$label = isset($this->settings['label']) ? $this->settings['label'] : $name;
		if ( ! isset($this->ui) || ! is_array($this->ui) ) $this->ui = array();
		$this->ui[ $context ] = new StorageSettingsUI($this, $name);
		$this->settings($context);
	}

	public function uitemplate () {
		$this->ui['image']->template();
	}

	public function ui ( $context ) {
		$editor = $this->ui[ $context ]->generate();

		$data = array('${context}' => $context);
		foreach ( $this->settings as $name => $value )
			$data['${'.$name.'}'] = $value[ $context ];

		return str_replace(array_keys($data), $data, $editor);
	}

    /**
     * This method should be overridden by any storage modules that support directly accessible URLs for assets and
     * exists here only as a stub for compatibility-reasons. If it is not overridden it will always return false.
     *
     * @deprecated This is a stub method that ensures compatibility with interface StorageEngine
     * @param string $uri
     * @return bool false
     */
    public function direct( $uri ) {
        return false;
    }

}

class StorageSettingsUI extends ModuleSettingsUI {

	public function generate () {

		$_ = array();
		$_[] = '<div id="'.$this->id.'-settings">';
		foreach ($this->markup as $markup) {
			if (empty($markup)) continue;
			else $_[] = join("\n",$markup);
		}

		$_[] = '</div>';

		return join("\n",$_);

	}

	/**
	 * Renders a checkbox input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; use 'checked' to set whether the element is toggled on or not
	 *
	 * @return void
	 **/
	public function checkbox ( $column = 0, array $attributes = array() ) {
		if (isset($attributes['name']))
			$attributes['name'] .= '][${context}';
		parent::checkbox($column, $attributes);

		$id = "{$this->id}-" . sanitize_title_with_dashes($attributes['name']);
		$fix = str_replace('context','-${context}', $id);
		foreach ( $this->markup as &$markup )
			$markup = str_replace($id, $fix, $markup);
	}

	/**
	 * Renders a drop-down menu element
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; use 'selected' to set the selected option
	 * @param array $options The available options in the menu
	 *
	 * @return void
	 **/
	public function menu ( $column = 0, array $attributes = array(), array $options = array()) {
		$attributes['title'] = '${'.$attributes['name'].'}';
		if (isset($attributes['name']))
			$attributes['name'] .= '][${context}';
		parent::menu($column,$attributes,$options);
	}

	/**
	 * Renders a multiple-select widget
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; pass a 'selected' attribute as an array to set the selected options
	 * @param array $options The available options in the menu
	 *
	 * @return void
	 **/
	public function multimenu ( $column = 0, array $attributes = array(), array $options = array() ) {
		if (isset($attributes['name']))
			$attributes['name'] .= '][${context}';
		parent::multimenu($column,$attributes,$options);
	}

	/**
	 * Renders a text input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function input ( $column = 0, array $attributes = array() ) {
		if (isset($attributes['name'])) {
			$name = $attributes['name'];
			$attributes['value'] = '${'.$name.'}';
			$attributes['name'] .= '][${context}';
		}
		parent::input($column,$attributes);
	}


	/**
	 * Renders a text input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function textarea ( $column = 0, array $attributes = array() ) {
		if (isset($attributes['name'])) {
			$name = $attributes['name'];
			$attributes['value'] = '${'.$name.'}';
			$attributes['name'] .= '][${context}';
		}
		parent::textarea($column,$attributes);
	}


	/**
	 * Renders a styled button element
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function button ( $column = 0, array $attributes = array() ) {
		if (isset($attributes['name'])) {
			$name = $attributes['name'];
			$attributes['value'] = '${'.$name.'}';
			$attributes['name'] .= '][${context}';
		}
		parent::button($column,$attributes);
	}

	public function behaviors ( $script ) {
		shopp_custom_script('system-settings',$script);
	}

}
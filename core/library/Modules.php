<?php
/**
 * Modules.php
 *
 * Controller and framework classes for Shopp modules
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 15, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage modules
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ModuleLoader
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage modules
 **/
abstract class ModuleLoader {

	protected $loader = 'ModuleFile'; // Module File load manager

	public $legacy = array();		// Legacy module checksums
	public $modules = array();		// Installed available modules
	public $activated = array();	// List of selected modules to be activated
	public $active = array();		// Instantiated module objects
	public $path = false;			// Source path for target module files

	/**
	 * Indexes the install module files
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function installed () {
		if (!is_dir($this->path)) return false;

		$path = $this->path;
		$files = array();
		self::find_files('.php',$path,$path,$files);
		if (empty($files)) return $files;

		foreach ($files as $file) {
			// Skip if the file can't be read or isn't a real file at all
			if (!is_readable($path.$file) && !is_dir($path.$file)) continue;
			// Add the module file to the registry
			$Loader = $this->loader;
			$module = new $Loader($path, $file);
			if ($module->addon) $this->modules[$module->subpackage] = $module;
			else $this->legacy[] = md5_file($path.$file);
		}

	}

	/**
	 * Loads the activated module files
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param boolean $all Loads all installed modules instead
	 * @return void
	 **/
	public function load ($all=false) {
		if ($all) $activate = array_keys($this->modules);
		else $activate = $this->activated;

		foreach ($activate as $module) {
			// Module isn't available, skip it
			if ( ! isset($this->modules[ $module ]) ) continue;

			$ModuleFile = $this->modules[ $module ];
			ShoppLoader::add($module, $ModuleFile->file);
			$this->active[ $module ] = $ModuleFile->load();

			if ( function_exists('do_action_ref_array') )
				do_action_ref_array('shopp_module_loaded', array($module));
		}

		if ( function_exists('do_action') )
			do_action('shopp_' . strtolower(get_class($this)) . '_loaded');
	}

	/**
	 * Hashes module files
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of checksums
	 **/
	public function checksums () {
		$hashes = array();
		foreach ($this->modules as $module) $hashes[] = md5_file($module->file);
		if (!empty($this->legacy)) $hashes = array_merge($hashes,$this->legacy);
		return $hashes;
	}

	/**
	 * Finds files of a specific extension
	 *
	 * Recursively searches directories and one-level deep of sub-directories for
	 * files with a specific extension
	 *
	 * NOTE: Files are saved to the $found parameter, an array passed by
	 * reference, not a returned value
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $extension File extension to search for
	 * @param string $directory Starting directory
	 * @param string $root Starting directory reference
	 * @param string &$found List of files found
	 * @return boolean Returns true if files are found
	 **/
	static function find_files ($extension, $directory, $root, &$found) {
		if (is_dir($directory)) {

			$Directory = @dir($directory);
			if ($Directory) {
				while (( $file = $Directory->read() ) !== false) {
					if (substr($file,0,1) == "." || substr($file,0,1) == "_") continue;				// Ignore .dot files and _directories
					if (is_dir($directory.DIRECTORY_SEPARATOR.$file) && $directory == $root)		// Scan one deep more than root
						self::find_files($extension,$directory.DIRECTORY_SEPARATOR.$file,$root, $found);	// but avoid recursive scans
					if (substr($file,strlen($extension)*-1) == $extension)
						$found[] = substr($directory,strlen($root)).DIRECTORY_SEPARATOR.$file;		// Add the file to the found list
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Gets a ModuleFile entry
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $module The module file class/package name
	 * @return StorageEngine or false
	 **/
	function module ( $module ) {
		if ( isset($this->modules[ $module ]) )
			return $this->modules[ $module ];
		return false;
	}

	/**
	 * Activates a specified module
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $module The module file class/package name
	 * @return Object The activated module object or false if it failed to load
	 **/
	function activate ( $module ) {
		$ModuleFile = $this->module($module);
		if ( false === $ModuleFile ) return false;
		ShoppLoader::add($module, $ModuleFile->file);
		$this->active[ $module ] = $ModuleFile->load();
		return $this->active[ $module ];
	}



} // END class ModuleLoader

/**
 * ModuleFile class
 *
 * Manages a module file
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage modules
 **/
class ModuleFile {

	public $file = false;			// The full path to the file
	public $filename = false;		// The name of the file
	public $name = false;			// The proper name of the module
	public $description = false;	// A description of the module
	public $subpackage = false;	// The class name of the module
	public $version = false;		// The version of the module
	public $since = false;			// The core version required
	public $addon = false;			// The valid addon flag

	/**
	 * Parses the module file meta data and validates it
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $path The directory the file lives in
	 * @param string $file The file name
	 * @return void
	 **/
	public function __construct ($path,$file) {
		if (!is_readable($path.$file)) return;

		$this->filename = $file;
		$this->file = $path.$file;
		$meta = $this->readmeta($this->file);

		if ($meta) {
			$meta = preg_replace('/\r\n/',"\n",$meta); // Normalize line endings
			$lines = explode("\n",substr($meta,1));
			foreach($lines as $line) {
				preg_match("/^(?:[\s\*]*?\b([^@\*\/]*))/",$line,$match);
				if (!empty($match[1])) $data[] = $match[1];

				preg_match("/^(?:[\s\*]*?@([^\*\/]+?)\s(.+))/",$line,$match);
				if (!empty($match[1]) && !empty($match[2])) $tags[$match[1]] = $match[2];
			}

			$this->name = $data[0];
			$this->description = (!empty($data[1]))?$data[1]:"";

			foreach ($tags as $tag => $value)
				$this->{$tag} = trim($value);
		}
		if ($this->valid() !== true) return;
		$this->addon = true;

	}

	/**
	 * Loads the module file and instantiates the module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function load () {
		if ( ! $this->addon ) return;
		return new $this->subpackage();
	}

	/**
	 * Determines if the module is a valid and compatible Shopp module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function valid () {
		if (empty($this->version) || empty($this->since) || empty($this->subpackage))
			return new ShoppError(sprintf(
				__('%s could not be loaded because the file descriptors are incomplete.','Shopp'),
				basename($this->file)),
				'addon_missing_meta',SHOPP_ADDON_ERR);

		if (!defined('Shopp::VERSION')) return true;
		$coreversion = '/^([\d\.])\b.*?$/';
		$shopp = preg_replace($coreversion,"$1",Shopp::VERSION);
		$since = preg_replace($coreversion,"$1",$this->since);
		if (version_compare($shopp,$since) == -1)
			return new ShoppError(sprintf(
				__('%s could not be loaded because it requires version %s (or higher) of Shopp.','Shopp'),
				$this->name, $this->since),
				'addon_core_version',SHOPP_ADDON_ERR);
		return true;
	}

	/**
	 * Read the file docblock for Shopp addons
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $file The target file
	 * @return string The meta block from the file
	 **/
	public function readmeta ($file) {
		if (!file_exists($file)) return false;
		if (!is_readable($file)) return false;

		$meta = false;
		$string = "";

		$f = @fopen($file, "r");
		if (!$f) return false;
		while (!feof($f)) {
			$buffer = fgets($f,80);
			if (preg_match("/\/\*/",$buffer)) $meta = true;
			if ($meta) $string .= $buffer;
			if (preg_match("/\*\//",$buffer)) break;
		}
		fclose($f);

		return $string;
	}


} // END class ModuleFile

/**
 * ModuleSettingsUI class
 *
 * Provides a PHP interface for building JavaScript based module setting
 * widgets using the ModuleSetting Javascript class.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ModuleSettingsUI {

	public $module;
	public $name;
	public $label;
	public $markup = array(
		array(),array(),array()
	);
	public $script = '';

	/**
	 * Registers a new module setting interface
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct ($Module,$name) {
		$this->name = $name;
		$this->module = $Module->module;
		$this->id = sanitize_title_with_dashes($this->module);
		$this->label = isset($Module->settings['label'])?$Module->settings['label']:$name;
	}

	public function generate () {

		$_ = array();
		$_[] = '<tr><td colspan="5">';
		$_[] = '<table class="form-table shopp-settings"><tr>';

		foreach ($this->markup as $markup) {
			$_[] = '<td>';
			if (empty($markup)) $_[] = '&nbsp;';
			else $_[] = join('',$markup);
			$_[] = '</td>';
		}

		$_[] = '</tr></table>';
		$_[] = '</td></tr>';

		return join('',$_);

	}

	public function template ( $id = null ) {
		$_ = array('<script id="'.$this->id.'-editor" type="text/x-jquery-tmpl">');
		$_[] = $this->generate();
		$_[] = '</script>';

		echo join('',$_)."\n\n";
	}

	public function ui ( $markup, $column = 0 ) {
		if ( ! isset($this->markup[ $column ]) ) $this->markup[ $column ] = array();
		$this->markup[ $column ][] = $markup;
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
	public function checkbox ($column=0,$attributes=array()) {
		$defaults = array(
			'label' => '',
			'type' => 'checkbox',
			'normal' => 'off',
			'value' => 'on',
			'checked' => false,
			'class' => '',
		);
		$attributes = array_merge($defaults,$attributes);
		$attributes['checked'] = (Shopp::str_true($attributes['checked'])?true:false);
		extract($attributes);
		$id = "{$this->id}-".sanitize_title_with_dashes($name);
		if (!empty($class)) $class = ' class="'.esc_attr($class).'"';

		$this->ui('<div><label for="'.$id.'">',$column);
		$this->ui('<input type="hidden" name="settings['.$this->module.']['.$name.']" value="'.$normal.'" id="'.$id.'-default" />',$column);
		$this->ui('<input type="'.$type.'" name="settings['.$this->module.']['.$name.']" value="'.$value.'"'.$class.' id="'.$id.'"'.($checked?' checked="checked"':'').' />',$column);
		if (!empty($label)) $this->ui('&nbsp;'.$label,$column);
		$this->ui('</label></div>',$column);

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
	public function menu ($column=0,$attributes=array(),$options=array()) {
		$defaults = array(
			'label' => '',
			'selected' => '',
			'keyed' => false
		);
		$attributes = array_merge($defaults,$attributes);
		extract($attributes);
		$id = "{$this->id}-".sanitize_title_with_dashes($name);

		$this->ui('<div>',$column);
		$this->ui('<select name="settings['.$this->module.']['.$name.']" id="'.$id.'"'.inputattrs($attributes).'>',$column);

		if (is_array($options)) {
			foreach ($options as $val => $option) {
				$value = $keyed?' value="'.$val.'"':'';
				$select = ($selected == (string)$val || $selected == $option)?' selected="selected"':'';
				$this->ui('<option'.$value.$select.'>'.$option.'</option>',$column);
			}
		}
		$this->ui('</select>',$column);
		if (!empty($label)) $this->ui('<br /><label for="'.$id.'">'.$label.'</label>',$column);
		$this->ui('</div>',$column);

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
	public function multimenu ($column=0,$attributes=array(),$options=array()) {

		$defaults = array(
			'label' => '',
			'selected' => array(),
			'disabled' => array(),
			'readonly' => array(),
			'class' => ''
		);
		$attributes = array_merge($defaults,$attributes);
		$attributes['id'] = "{$this->id}-{$attributes['name']}";
		extract($attributes);


		$classes = empty($class)?'':' class="'.$class.'"';

		$this->ui('<div'.$classes.'><div class="multiple-select">',$column);
		$this->ui('<ul '.inputattrs($attributes).'>',$column);
		if (is_array($options)) {
			$checked = '';
			$alt = false;
			$this->ui('<li class="hide-if-no-js"><input type="checkbox" name="select-all" id="'.$id.'-select-all" class="selectall-toggle" /><label for="'.$id.'-select-all"><strong>'.__('Select All','Shopp').'</strong></label></li>',$column);
			foreach ($options as $key => $l) {
				$attrs = '';
				$boxid = $id.'-'.sanitize_title_with_dashes($key);

				if (in_array($key,(array)$selected)) $attrs .= ' checked="checked"';
				if (in_array($key,(array)$disabled)) $attrs .= ' disabled="disabled"';
				if (in_array($key,(array)$readonly)) $attrs .= ' readonly="readonly"';

				$this->ui('<li'.($alt = !$alt?' class="odd"':'').'><input type="checkbox" name="settings['.$this->module.']['.$name.'][]" value="'.$key.'" id="'.$boxid.'"'.$attrs.' /><label for="'.$boxid.'">'.$l.'</label></li>',$column);
			}
		}
		$this->ui('</ul></div>',$column);
		if (!empty($label)) $this->ui('<br /><label for="'.$id.'">'.$label.'</label>',$column);
		$this->ui('</div>',$column);

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
	public function input ($column=0,$attributes=array()) {
		$defaults = array(
			'type' => 'hidden',
			'label' => '',
			'readonly' => false,
			'value' => '',
			'size' => 20,
			'class' => ''
		);
		$attributes = array_merge($defaults,array_filter($attributes));
		$attributes['id'] = "{$this->id}-".sanitize_title_with_dashes($attributes['name']);
		extract($attributes);

		$this->ui('<div>',$column);
		$this->ui('<input type="'.$type.'" name="settings['.$this->module.']['.$name.']" id="'.$id.'"'.inputattrs($attributes).' />',$column);
		if (!empty($label)) $this->ui('<br /><label for="'.$id.'">'.$label.'</label>',$column);
		$this->ui('</div>',$column);
	}

	/**
	 * Renders a password input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function text ($column=0,$attributes=array()) {
		$attributes['type'] = 'text';
		$this->input($column,$attributes);
	}

	/**
	 * Renders a password input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function password ($column=0,$attributes=array()) {
		$attributes['type'] = 'password';
		$this->input($column,$attributes);
	}

	/**
	 * Renders a hidden input
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function hidden ($column=0,$attributes=array()) {
		$attributes['type'] = 'hidden';
		$this->input($column,$attributes);
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
	public function textarea ($column=0,$attributes=array()) {
		$defaults = array(
			'label' => '',
			'readonly' => false,
			'value' => '',
			'cols' => 30,
			'rows' => 3,
			'class' => '',
			'id' => ''
		);
		$attributes = array_merge($defaults,$attributes);
		if (!empty($attributes['id']))
			$attributes['id'] = "{$this->id}-".sanitize_title_with_dashes($attributes['id']);
		extract($attributes);

		$this->ui('<div><textarea name="settings['.$this->module.']['.$name.']" '.inputattrs($attributes).'>'.esc_html($value).'</textarea>',$column);
		if (!empty($label)) $this->ui('<br /><label for="'.$id.'">'.$label.'</label>',$column);
		$this->ui('</div>',$column);
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
	public function button ($column=0,$attributes=array()) {
		$defaults = array(
			'type' => 'button',
			'label' => '',
			'disabled' => false,
			'content' =>__('Button','Shopp'),
			'value' => '',
			'class' => ''
		);
		$attributes = array_merge($defaults,$attributes);
		$attributes['id'] = "{$this->id}-".sanitize_title_with_dashes($attributes['name']);
		$attributes['class'] = 'button-secondary'.('' == $attributes['class']?'':' '.$attributes['class']);
		extract($attributes);

		$this->ui('<button type="'.$type.'" name="'.$name.'" id="'.$id.'"'.inputattrs($attributes).'>'.$content.'</button>',$column);
		if (!empty($label)) $this->ui('<br /><label for="'.$id.'">'.$label.'</label>',$column);

	}

	/**
	 * Renders a paragraph element
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; requires a 'name' attribute
	 *
	 * @return void
	 **/
	public function p ($column=0,$attributes=array()) {
		$defaults = array(
			'id' => '',
			'label' => '',
			'content' => '',
			'class' => ''
		);
		$attributes = array_merge($defaults,$attributes);
		if (!empty($attributes['id']))
			$attributes['id'] = " id=\"{$this->id}-".sanitize_title_with_dashes($attributes['id'])."\"";
		extract($attributes);

		if (!empty($class)) $class = ' class="'.$class.'"';

		if (!empty($label)) $label = '<p><label><strong>'.$label.'</strong></label></p>';
		$this->ui('<div'.$id.$class.'>'.$label.$content.'</div>',$column);
	}

	public function behaviors ($script) {
		shopp_custom_script('shopp',$script);
	}

} // END class ModuleSettingsUI

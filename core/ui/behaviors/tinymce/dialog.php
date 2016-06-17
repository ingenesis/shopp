<?php $error = false; $wpadmin = ShoppTMCELoader::load(); if ($wpadmin) require($wpadmin); ShoppTMCELoader::setup(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#Shopp.title}</title>
	<?php if (!$error): ?>
	<script language="javascript" type="text/javascript" src="<?php echo TINYMCE_URL; ?>tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo TINYMCE_URL; ?>utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo TINYMCE_URL; ?>utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo WPINC_URL; ?>/js/jquery/jquery.js"></script>
	<?php endif; ?>

	<style type="text/css">
		body { font-family: Arial, Helvetica; }
		#error { background: white; margin: 10px; }
		table th { vertical-align: top; }
		.panel_wrapper { border-top: 1px solid #909B9C; }
		.panel_wrapper div.current { height:auto !important; }
		#product-menu { width: 180px; }
	</style>

</head>
<body>

<div id="wpwrap">
<?php if (!$error): ?>
<form action="#" id="dialog">
	<div class="panel_wrapper">
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
		<th nowrap="nowrap"><label for="category-menu"><?php _e('Category', 'Shopp'); ?></label></th>
		<td><?php wp_dropdown_categories( array( 'id' => 'category-menu','taxonomy' => ProductCategory::$taxon, 'hide_empty' => 0, 'name' => ProductCategory::$taxon, 'orderby' => 'name', 'hierarchical' => 1, 'show_option_all' => __('Select a category&hellip;','Shopp'), 'show_option_none' => __('Uncategorized','Shopp'), 'tab_index' => 1 ) ); ?></td>
		</tr>
		<tr id="product-selector">
		<th nowrap="nowrap"><label for="product-menu"><?php _e('Product', 'Shopp'); ?></label></th>
		<td><select id="product-menu" name="product" size="7"><option value=""><?php _e('Insert entire catalog&hellip;','Shopp'); ?></option></select></td>
		</tr>
		</table>
	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" />
		</div>

		<div style="float: right">
			<input type="button" id="insert" name="insert" value="{#insert}" />
		</div>
	</div>
</form>
<?php else: ?>
<div id="error">
	<h3>Error</h3>
	<p><?php echo $error; ?></p>
</div>
<?php endif; ?>
</div>

<?php if (!$error): ?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
tinyMCEPopup.onInit.add(function(ed) {
	jQuery.noConflict()(function($){
		var pm = $('#product-menu'),

			cm = $('#category-menu').change(function () {
				var sc = '<option value="0"><?php _e('Insert','Shopp'); ?> "'+cm.find('option:selected').text().trim()+'" <?php _e('category','Shopp'); ?></option>';
				$.get("<?php echo wp_nonce_url(admin_url('admin-ajax.php'),'wp_ajax_shopp_category_products'); ?>&action=shopp_category_products",
					{category:cm.val()},function (r) { pm.empty().html(sc+r); },'html'
				);
			}),

			insert = $('#insert').click(function () {
				var tag = '';
				// Category shortcodes
				if (parseInt(cm.val()) > 0) tag = '[catalog-collection id="'+cm.val()+'"]';
				else if (cm.val() != '') tag = '[catalog-collection slug="catalog"]';
				// Product shortcodes
				if (pm.val() != 0 && pm.val() != null) tag = '[catalog-product id="'+pm.val()+'"]';

				if (window.tinyMCE) {
					var tmce_ver = window.tinyMCE.majorVersion;
					if ( tmce_ver >= "4" ) {
						window.tinyMCE.execCommand('mceInsertContent', false, tag);
					} else {
						window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tag);
					}
					tinyMCEPopup.editor.execCommand('mceRepaint');
					tinyMCEPopup.close();
				}
			}),

			cancel = jQuery('#cancel').click(function () {
				tinyMCEPopup.close();
			});
	});
});

/* ]]> */
</script>
<?php endif; ?>

</body>
</html>
<?php
class ShoppTMCELoader {

	static function path () {
		if ( ! isset($_GET['p']) || empty($_GET['p']) ) return 0;
		$path = ''; $p = explode('x', $_GET['p']); $d = count($p);
		for ( $i = 0; $i < $d; $i++ ) $path .= empty($p[ $i ]) ? '' : '%' . dechex(hexdec($p[ $i ]) - ($d - 1));
		if ( empty($path) ) return 1;
		return urldecode($path);
	}

	static function load () {
		global $error, $pagenow;

		$path = self::path();
		if ( is_int($path) ) return ! ( $error = self::errors($path) );
		$wpadmin = $path.'wp-admin/admin.php';
		if ( ! file_exists($wpadmin) ) return ! ( $error = self::errors(2) );
		define('WP_ADMIN', true);
		return $wpadmin;
	}

	static function setup () {
		define('WPINC_URL', get_bloginfo('wpurl') . '/' . WPINC);
		define('TINYMCE_URL', WPINC_URL . '/js/tinymce/');
		if( ! current_user_can('edit_posts') ) ! ( $error = self::errors(3) );
	}

	static function errors ( $code ) {
		$errors = array(
			'Shopp could not locate WordPress.',
			'Shopp could not read the path to WordPress.',
			'Could not load the WordPress environment.',
			'You do not have permission to edit posts.'
		);
		return $errors[ $code ];
	}
}

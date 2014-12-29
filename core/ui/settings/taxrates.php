<div class="wrap shopp">
	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>


	<?php if (count(shopp_setting('target_markets')) == 0) echo '<div class="error"><p>'.__('No target markets have been selected in your store setup.','Shopp').'</p></div>'; ?>

	<?php $this->taxes_menu(); ?>
	<form action="<?php echo esc_url($this->url); ?>" id="taxrates" method="post" enctype="multipart/form-data" accept="text/plain,text/xml">

	<div>
		<?php wp_nonce_field('shopp-settings-taxrates'); ?>
	</div>

	<div class="tablenav">
		<div class="actions">
		<button type="submit" name="addrate" id="addrate" class="button-secondary" tabindex="9999" <?php if (empty($countries)) echo 'disabled="disabled"'; ?>><?php _e('Add Tax Rate','Shopp'); ?></button>
		</div>
	</div>

	<script id="property-menu" type="text/x-jquery-tmpl"><?php
		$propertymenu = array(
			'product-name' => __('Product name is','Shopp'),
			'product-tags' => __('Product is tagged','Shopp'),
			'product-category' => __('Product in category','Shopp'),
			'customer-type' => __('Customer type is','Shopp')
		);
		echo Shopp::menuoptions($propertymenu,false,true);
	?></script>

	<script id="countries-menu" type="text/x-jquery-tmpl"><?php
		echo Shopp::menuoptions($countries,false,true);
	?></script>


	<script id="conditional" type="text/x-jquery-tmpl">
	<?php ob_start(); ?>
	<li>
		<?php echo ShoppUI::button('delete','deleterule'); ?>
		<select name="settings[taxrates][${id}][rules][${ruleid}][p]" class="property">${property_menu}</select>&nbsp;<input type="text" name="settings[taxrates][${id}][rules][${ruleid}][v]" size="25" class="value" value="${rulevalue}" />
		<?php echo ShoppUI::button('add','addrule'); ?></li>
	<?php $conditional = ob_get_contents(); ob_end_clean(); echo str_replace(array("\n","\t"),'',$conditional); ?>
	</script>

	<script id="localrate" type="text/x-jquery-tmpl">
	<?php ob_start(); ?>
	<li><label title="${localename}"><input type="text" name="settings[taxrates][${id}][locals][${localename}]" size="6" value="${localerate}" />&nbsp;${localename}</label></li>
	<?php $localrateui = ob_get_contents(); ob_end_clean(); echo $localrateui; ?>
	</script>

	<script id="editor" type="text/x-jquery-tmpl">
	<?php ob_start(); ?>
	<tr class="inline-edit-row ${classnames}" id="${id}">
		<td colspan="5"><input type="hidden" name="id" value="${id}" /><input type="hidden" name="editing" value="true" />
		<table id="taxrate-editor">
			<tr>
			<td scope="row" valign="top" class="rate"><input type="text" name="settings[taxrates][${id}][rate]" id="tax-rate" value="${rate}" size="7" class="selectall" tabindex="1" /><br /><label for="tax-rate"><?php _e('Tax Rate','Shopp'); ?></label><br />
			<input type="hidden" name="settings[taxrates][${id}][compound]" value="off" /><label><input type="checkbox" id="tax-compound" name="settings[taxrates][${id}][compound]" value="on" ${compounded} tabindex="4" />&nbsp;<?php Shopp::_e('Compound'); ?></label></td>
			<td scope="row" class="conditions">
			<select name="settings[taxrates][${id}][country]" class="country" tabindex="2">${countries}</select><select name="settings[taxrates][${id}][zone]" class="zone no-zones" tabindex="3">${zones}</select>
			<?php echo ShoppUI::button('add','addrule'); ?>
			<?php
				$options = array('any' => Shopp::__('any'), 'all' => strtolower(Shopp::__('All')));
				$menu = '<select name="settings[taxrates][${id}][logic]" class="logic">'.menuoptions($options,false,true).'</select>';
			?>
				<div class="conditionals no-conditions">
					<p><label><?php printf(__('Apply tax rate when %s of the following conditions match','Shopp'),$menu); ?>:</label></p>
					<ul>
					${conditions}
					</ul>
				</div>
			</td>
				<td>
					<div class="local-rates panel subpanel no-local-rates">
						<div class="label"><label><?php _e('Local Rates','Shopp'); echo ShoppAdmin()->boxhelp('settings-taxes-localrates'); ?> <span class="counter"></span><input type="hidden" name="settings[taxrates][${id}][haslocals]" value="${haslocals}" class="has-locals" /></label></div>
						<div class="ui">
							<p class="instructions"><?php Shopp::_e('No local regions have been setup for this location. Local regions can be specified by uploading a formatted local rates file.'); ?></p>
							${errors}
							<ul>${localrates}</ul>
							<div class="upload">
								<h3><?php Shopp::_e('Upload Local Tax Rates'); ?></h3>
								<input type="hidden" name="MAX_FILE_SIZE" value="1048576" />
								<input type="file" name="ratefile" class="hide-if-js" />
								<button type="submit" name="upload" class="button-secondary upload"><?php Shopp::_e('Upload'); ?></button>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="3">
				<p class="textright">
				<a href="<?php echo $this->url; ?>" class="button-secondary cancel alignleft"><?php Shopp::_e('Cancel'); ?></a>
				<button type="submit" name="add-locals" class="button-secondary locals-toggle add-locals has-local-rates"><?php Shopp::_e('Add Local Rates'); ?></button>
				<button type="submit" name="remove-locals" class="button-secondary locals-toggle rm-locals no-local-rates"><?php Shopp::_e('Remove Local Rates'); ?></button>
				<input type="submit" class="button-primary" name="submit" value="<?php Shopp::_e('Save Changes'); ?>" />
				</p>
				</td>
			</tr>
		</table>
		</td>
	</tr>
	<?php $editor = ob_get_contents(); ob_end_clean(); echo str_replace(array("\n","\t"),'',$editor); ?>
	</script>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers('shopp_page_shopp-settings-taxrates'); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers('shopp_page_shopp-settings-taxrates',false); ?></tr>
		</tfoot>
		<tbody id="taxrates-table" class="list">
		<?php
			if ($edit !== false && !isset($rates[$edit])) {
				$defaults = array(
					'rate' => 0,
					'country' => false,
					'zone' => false,
					'rules' => array(),
					'locals' => array(),
					'haslocals' => false,
					'compound' => false
				);
				extract($defaults);
				echo ShoppUI::template($editor,array(
					'${id}' => $edit,
					'${rate}' => percentage($rate,array('precision'=>4)),
					'${countries}' => menuoptions($countries,$country,true),
					'${zones}' => !empty($zones[$country])?menuoptions($zones[$country],$zone,true):'',
					'${conditions}' => join('',$conditions),
					'${haslocals}' => $haslocals,
					'${localrates}' => join('',$localrates),
					'${instructions}' => $localerror ? '<p class="error">'.$localerror.'</p>' : $instructions,
					'${compounded}' => Shopp::str_true($compound) ? 'checked="checked"' : ''
				));
			}

			if (count($rates) == 0 && $edit === false): ?>
				<tr id="no-taxrates"><td colspan="5"><?php _e('No tax rates, yet.','Shopp'); ?></td></tr>
			<?php
			endif;

			$hidden = get_hidden_columns('shopp_page_shopp-settings-taxrates');
			$even = false;
			foreach ($rates as $index => $taxrate):
				$defaults = array(
					'rate' => 0,
					'country' => false,
					'zone' => false,
					'rules' => array(),
					'locals' => array(),
					'haslocals' => false
				);
				$taxrate = array_merge($defaults,$taxrate);
				extract($taxrate);

				$rate = Shopp::percentage(Shopp::floatval($rate), array('precision'=>4));
				$location = $countries[ $country ];

				if (isset($zone) && !empty($zone))
					$location = $zones[$country][$zone].", $location";

				$editurl = wp_nonce_url(add_query_arg(array('id'=>$index),$this->url));
				$deleteurl = wp_nonce_url(add_query_arg(array('delete'=>$index),$this->url),'shopp_delete_taxrate');

				$classes = array();
				if (!$even) $classes[] = 'alternate'; $even = !$even;
				if ($edit !== false && $edit === $index) {

					$conditions = array();
					foreach ($rules as $ruleid => $rule) {
						$condition_template_data = array(
							'${id}' => $edit,
							'${ruleid}' => $ruleid,
							'${property_menu}' => menuoptions($propertymenu,$rule['p'],true),
							'${rulevalue}' => esc_attr($rule['v'])
						);
						$conditions[] = str_replace(array_keys($condition_template_data),$condition_template_data,$conditional);

					}

					$localrates = array();
					foreach ($locals as $localename => $localerate) {
						$localrateui_data = array(
							'${id}' => $edit,
							'${localename}' => $localename,
							'${localerate}' => (float)$localerate,
						);
						$localrates[] = str_replace(array_keys($localrateui_data),$localrateui_data,$localrateui);
					}

					$data = array(
						'${id}' => $edit,
						'${rate}' => $rate,
						'${countries}' => menuoptions($countries,$country,true),
						'${zones}' => !empty($zones[$country])?menuoptions(array_merge(array(''=>''),$zones[$country]),$zone,true):'',
						'${conditions}' => join('',$conditions),
						'${haslocals}' => $haslocals,
						'${localrates}' => join('',$localrates),
						'${errors}' => $localerror ? '<p class="error">'.$localerror.'</p>' : '',
						'${compounded}' => Shopp::str_true($compound) ? 'checked="checked"' : '',
						'${cancel_href}' => $this->url
					);
					if ($conditions) $data['no-conditions'] = '';
					if (!empty($zones[$country])) $data['no-zones'] = '';

					if ($haslocals) $data['no-local-rates'] = '';
					else $data['has-local-rates'] = '';

					if (count($locals) > 0) $data['instructions'] = 'hidden';

					echo ShoppUI::template($editor,$data);
					if ($edit === $index) continue;
				}

				$label = "$rate &mdash; $location";
			?>
		<tr class="<?php echo join(' ',$classes); ?>" id="taxrates-<?php echo $index; ?>">
			<td class="name column-name"><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($rate); ?>&quot;" class="edit row-title"><?php echo esc_html($label); ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($label); ?>&quot;" class="edit"><?php _e('Edit','Shopp'); ?></a> | </span><span class='delete'><a href="<?php echo esc_url($deleteurl); ?>" title="<?php _e('Delete','Shopp'); ?> &quot;<?php echo esc_attr($label); ?>&quot;" class="delete"><?php _e('Delete','Shopp'); ?></a></span>
				</div>
			</td>
			<td class="local column-local">
				<div class="checkbox <?php if ( $haslocals ) echo 'checked'; ?>">&nbsp;</div>
			</td>
			<td class="conditional column-conditional">
				<div class="checkbox <?php if ( count($rules) > 0 ) echo 'checked'; ?>">&nbsp;</div>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	</form>

</div>


<script type="text/javascript">
/* <![CDATA[ */
var suggurl = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'),'wp_ajax_shopp_suggestions'); ?>',
	rates = <?php echo json_encode($rates); ?>,
	base = <?php echo json_encode($base); ?>,
	zones = <?php echo json_encode($zones); ?>,
	localities = <?php echo json_encode(Lookup::localities()); ?>,
	taxrates = [];
/* ]]> */
</script>

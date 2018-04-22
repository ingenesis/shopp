<div id="misc-publishing-actions">
	<div class="misc-pub-section misc-pub-section-last">

	<label for="discount-status"><input type="hidden" name="status" value="disabled" /><input type="checkbox" name="status" id="discount-status" value="enabled"<?php echo $Promotion->status == 'enabled' ? ' checked="checked"' : ''; ?> /> &nbsp;<?php Shopp::_e('Enabled'); ?></label>
	</div>

	<div class="misc-pub-section misc-pub-section-last">

	<div id="start-position" class="calendar-wrap"><?php datefields('starts', $Promotion->starts); ?></div>
	<p><?php Shopp::_e('Start promotion on this date.'); ?></p>

	<div id="end-position" class="calendar-wrap"><?php datefields('ends', $Promotion->ends); ?></div>
	<p><?php Shopp::_e('End the promotion on this date.'); ?></p>

	</div>

</div>

<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save'); ?>" />
</div>
<?php

function datefields( $name, $property ) {
	$dateorder = array_keys(Shopp::date_format_order(true));

	$value = '';
	if ( $property > 1 ) {
		$month = date('n', $property);
		$day = date('j', $property);
		$year = date('Y', $property);
	}

	$lastfield = false;
	foreach ( $dateorder as $fieldtype ):

		if ( 's' == $fieldtype[0] ):
			echo 's' == $lastfield ? '' : '/';
 		elseif ( 'month' == $fieldtype ):
			$value = $month;
			?><input type="text" name="<?php echo esc_attr($name); ?>[month]" id="<?php echo esc_attr($name); ?>-month" title="<?php Shopp::_e('Month'); ?>" size="3" maxlength="2" value="<?php echo esc_attr($value); ?>" class="selectall" /><?php
		elseif ( 'day' == $fieldtype ):
			$value = $day;
			?><input type="text" name="<?php echo esc_attr($name); ?>[date]" id="<?php echo esc_attr($name); ?>-date" title="<?php Shopp::_e('Day'); ?>" size="3" maxlength="2" value="<?php echo esc_attr($value); ?>" class="selectall" /><?php
		elseif ( 'year' == $fieldtype ):
			$value = $year;
			?><input type="text" name="<?php echo esc_attr($name); ?>[year]" id="<?php echo esc_attr($name); ?>-year" title="<?php Shopp::_e('Year'); ?>" size="5" maxlength="4" value="<?php echo esc_attr($value); ?>" class="selectall" /><?php
		endif;
		$lastfield = $fieldtype[0];

	endforeach;
}
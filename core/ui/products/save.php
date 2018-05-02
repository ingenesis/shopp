<?php

	$workflows = array(
		"continue" => __('Continue Editing','Shopp'),
		"close" => __('Products Manager','Shopp'),
		"new" => __('New Product','Shopp'),
		"next" => __('Edit Next','Shopp'),
		"previous" => __('Edit Previous','Shopp')
		);


	$date_format = get_option('date_format');
	$time_format = get_option('time_format');

?>
<div id="misc-publishing-actions">
	<input type="hidden" name="id" value="<?php echo $Product->id; ?>" />

	<div class="misc-pub-section misc-pub-section-last">
		<input type="hidden" name="status" value="draft" /><input type="checkbox" name="status" value="publish" id="published" tabindex="11" <?php if ($Product->status == "publish") echo ' checked="checked"'?> /><label for="published"><strong> <?php if ($Product->published() && !empty($Product->id)) _e('Published','Shopp'); else _e('Publish','Shopp'); ?></strong> <span id="publish-status"><?php if ($Product->publish>1) printf(__('on: %s', 'Shopp'),"</span><br />".date($date_format.' @ '.$time_format,$Product->publish)); else echo "</span>"; ?></label> <span id="schedule-toggling"><button type="button" name="schedule-toggle" id="schedule-toggle" class="button-secondary"><?php if ($Product->publish>1) _e('Edit','Shopp'); else _e('Schedule','Shopp'); ?></button></span>

		<div id="scheduling">
			<div id="schedule-calendar" class="calendar-wrap">
				<?php
					$previous = false;
					$dateorder = Shopp::date_format_order(true);
					foreach ($dateorder as $type => $format):
						if ($previous == "s" && $type[0] == "s") continue;
						if ("month" == $type): ?><input type="text" name="publish[month]" id="publish-month" title="<?php _e('Month','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("n",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ("day" == $type): ?><input type="text" name="publish[date]" id="publish-date" title="<?php _e('Day','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("j",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ("year" == $type): ?><input type="text" name="publish[year]" id="publish-year" title="<?php _e('Year','Shopp'); ?>" size="4" maxlength="4" value="<?php echo ($Product->publish>1)?date("Y",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0]; ?><?php endforeach; ?>
				 <br />
				<input type="text" name="publish[hour]" id="publish-hour" title="<?php _e('Hour','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("g",$Product->publish):date('g'); ?>" class="publishdate selectall" />:<input type="text" name="publish[minute]" id="publish-minute" title="<?php _e('Minute','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("i",$Product->publish):date('i'); ?>" class="publishdate selectall" />
				<select name="publish[meridiem]" class="publishdate">
				<?php echo Shopp::menuoptions(array('AM' => __('AM','Shopp'),'PM' => __('PM','Shopp')),date('A',$Product->publish),true); ?>
				</select>
			</div>
		</div>

	</div>

</div>
<div id="major-publishing-actions">
	<select name="settings[workflow]" id="workflow">
	<?php echo Shopp::menuoptions($workflows,shopp_setting('workflow'),true); ?>
	</select>
<input type="submit" class="button-primary" name="save" value="<?php _e('Save Product','Shopp'); ?>" />
</div>
<form action="<?php echo $this->url(); ?>" method="post">
<?php if ( ! empty($Notes->meta) ): ?>
<table>
	<?php foreach ($Notes->meta as $Note): $User = get_userdata($Note->value->author); ?>
	<tr>
		<th class="column-author column-username"><?php echo get_avatar($User->ID,32); ?>
			<?php echo esc_html($User->display_name); ?><br />
			<span><?php echo Shopp::_d(get_option('date_format'), $Note->created); ?></span>
			<span><?php echo Shopp::_d(get_option('time_format'), $Note->created); ?></span></th>
		<td>
			<div id="note-<?php echo $Note->id; ?>">
			<?php if($Note->value->sent == 1): ?>
				<p class="notesent"><?php _e('Sent to the Customer:','Shopp'); ?> </p>
			<?php endif; ?>
			<?php echo apply_filters('shopp_order_note',$Note->value->message); ?>
			</div>
			<p class="notemeta">
				<span class="notectrls">
				<button type="submit" name="delete-note[<?php echo $Note->id; ?>]" value="delete" class="button-secondary deletenote"><small><?php Shopp::_e('Delete'); ?></small></button>
				<button type="button" name="edit-note[<?php echo $Note->id; ?>]" value="edit" class="button-secondary editnote"><small><?php Shopp::_e('Edit'); ?></small></button>
				<?php do_action('shopp_order_note_controls'); ?>
				</span>
			</p>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
<?php endif; ?>

<div id="notation">
	<p><label for="notes"><?php _e('New Note','Shopp'); ?>:</label><br />
		<textarea name="note" id="note" cols="50" rows="10"></textarea></p>
	<?php do_action('shopp_order_new_note_ui'); ?>
	<p class="alignright">
		<button type="button" name="cancel-note" value="cancel" id="cancel-note-button" class="button-secondary"><?php _e('Cancel','Shopp'); ?></button>
		<button type="submit" name="save-note" value="save" class="button-primary"><?php _e('Save Note','Shopp'); ?></button>
	</p>
	<div class="alignright options">
		<input type="checkbox" name="send-note" id="send-note" value="1">
		<label for="send-note"><?php _e('Send to customer','Shopp'); ?></label>
	</div>
</div>
<p class="alignright" id="add-note">
	<button type="button" name="add-note" value="add" id="add-note-button" class="button-secondary"><?php _e('Add Note','Shopp'); ?></button></p>
	<br class="clear" />
</form>

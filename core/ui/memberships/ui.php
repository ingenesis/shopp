<?php
function save_meta_box ($MemberPlan) {
?>
	<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" />
	</div>
<?php
}
ShoppUI::addmetabox('save-membership', __('Save','Shopp').$Admin->boxhelp('membership-editor-save'), 'save_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function settings_meta_box ($MemberPlan) {
?>
<p><input type="hidden" name="continuity" value="off" /><input type="checkbox" name="continuity" value="on" id="featured" tabindex="12" <?php if ($MemberPlan->continuity == "on") echo ' checked="checked"'?> /><label for="featured"> <?php _e('Continued access' ,'Shopp'); ?></label></p>
<?php $roles = get_editable_roles(); ?>
<p><select name="role" id="wp-roles">
<?php foreach ($roles as $value => $role): $selected = (strtolower($MemberPlan->role) == strtolower($value)?' selected="selected"':''); ?>
<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $role['name']; ?></option>
<?php endforeach; ?>
</select><label for="wp-roles"><?php _e('Default User Role','Shopp'); ?></p>

<?php
}
ShoppUI::addmetabox('membership-settings', __('Settings','Shopp').$Admin->boxhelp('membership-editor-settings'), 'settings_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function sources_meta_box ($MemberPlan) {
?>
<ul id="sources"></ul>
<p>Show list of content sources...</p>
<?php
}
ShoppUI::addmetabox('membership-sources', __('Content','Shopp').$Admin->boxhelp('membership-editor-sources'), 'sources_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function rules_meta_box ($MemberPlan) {
?>
<ul id="rules"></ul>
<input type="button" id="add-stage" name="add-stage" value="<?php _e('Add Step','Shopp'); ?>" class="button-secondary" />
<?php
}
ShoppUI::addmetabox('membership-rules', __('Access','Shopp').$Admin->boxhelp('membership-editor-rules'), 'rules_meta_box', 'shopp_page_shopp-memberships', 'normal', 'core');
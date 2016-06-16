<?php
function save_meta_box ($MemberPlan) {
?>
	<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php Shopp::_e('Save Changes'); ?>" />
	</div>
<?php
}
ShoppUI::addmetabox('save-membership', Shopp::__('Save').$Admin->boxhelp('membership-editor-save'), 'save_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function settings_meta_box ($MemberPlan) {
?>
<p><input type="hidden" name="continuity" value="off" /><input type="checkbox" name="continuity" value="on" id="featured" tabindex="12" <?php if ($MemberPlan->continuity == "on") echo ' checked="checked"'?> /><label for="featured"> <?php Shopp::_e('Continued access' ); ?></label></p>
<?php $roles = get_editable_roles(); ?>
<p><select name="role" id="wp-roles">
<?php foreach ($roles as $value => $role): $selected = (strtolower($MemberPlan->role) == strtolower($value)?' selected="selected"':''); ?>
<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $role['name']; ?></option>
<?php endforeach; ?>
</select><label for="wp-roles"><?php Shopp::_e('Default User Role'); ?></p>

<?php
}
ShoppUI::addmetabox('membership-settings', Shopp::__('Settings').$Admin->boxhelp('membership-editor-settings'), 'settings_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function sources_meta_box ($MemberPlan) {
?>
<ul id="sources"></ul>
<p>Show list of content sources...</p>
<?php
}
ShoppUI::addmetabox('membership-sources', Shopp::__('Content').$Admin->boxhelp('membership-editor-sources'), 'sources_meta_box', 'shopp_page_shopp-memberships', 'side', 'core');

function rules_meta_box ($MemberPlan) {
?>
<ul id="rules"></ul>
<input type="button" id="add-stage" name="add-stage" value="<?php Shopp::_e('Add Step'); ?>" class="button-secondary" />
<?php
}
ShoppUI::addmetabox('membership-rules', Shopp::__('Access').$Admin->boxhelp('membership-editor-rules'), 'rules_meta_box', 'shopp_page_shopp-memberships', 'normal', 'core');
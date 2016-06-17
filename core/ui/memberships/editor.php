	<div class="wrap shopp">

		<div class="icon32"></div>
		<h2><?php Shopp::_e('Membership Editor'); ?></h2>

		<?php do_action('shopp_admin_notice'); ?>

		<div id="ajax-response"></div>
		<form name="membership" id="membership" action="<?php echo add_query_arg('page',$this->Admin->pagename('memberships'),admin_url('admin.php')); ?>" method="post">
			<?php wp_nonce_field('shopp-save-membership'); ?>

			<div class="hidden"><input type="hidden" name="id" value="<?php echo $MemberPlan->id; ?>" /></div>

			<div id="poststuff" class="metabox-holder has-right-sidebar">

				<div id="side-info-column" class="inner-sidebar">
				<?php
				do_action('submitpage_box');
				$side_meta_boxes = do_meta_boxes('shopp_page_shopp-memberships', 'side', $MemberPlan);
				?>
				</div>

				<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content" class="has-sidebar-content">
					<div id="titlediv">
						<div id="titlewrap">
							<input name="name" id="title" type="text" value="<?php echo esc_attr($MemberPlan->name); ?>" size="30" tabindex="1" autocomplete="off" />
						</div>
					</div>

				<?php
				do_meta_boxes('shopp_page_shopp-memberships', 'normal', $MemberPlan);
				do_meta_boxes('shopp_page_shopp-memberships', 'advanced', $MemberPlan);
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>

				</div>
				</div>

			</div> <!-- #poststuff -->
		</form>
	</div>

<script id="panelUI" type="text/x-jquery-tmpl">
<li class="panel subpanel ${type}">
	<div class="label">
		<label>${label}</label>
		<php echo ShoppUI::button('delete', 'delete'); ?>
	</div>
	<div class="ui"></div>
</li>
</script>

<script id="stagePanelControls" type="text/x-jquery-tmpl">
<fieldset>
<input type="hidden" name="stages[${index}][id]" value="${id}" class="id" />
<label for="advance-${index}"><input type="hidden" name="stages[${index}][advance]" value="off" /><input type="checkbox" id="advance-${index}" name="stages[${index}][advance]" value="on" class="advance" />&nbsp;<?php Shopp::_e('Advance automatically'); ?></label>
<span class="schedule"><label>
<?php Shopp::_e('after'); ?></label>
 <select name="stages[${index}][interval]" class="interval">
<?php for ($i=1; $i < 31; $i++): ?><option><?php echo $i; ?></option><?php endfor; ?>
</select><select name="stages[${index}][period]" class="period"></select>
</span>

<div class="alignright actions">
<select name="access-type" class="content">
<option value="">+&nbsp;&nbsp;Add Access&hellip;</option>
<?php foreach ($rulegroups as $prefix => $group): ?>
	<optgroup label="<?php echo $group; ?>">
	<?php foreach ($ruletypes as $value => $label): if (strpos($value, $prefix) !== 0) continue; ?><option value="<?php echo $value; ?>"><?php echo $label; ?></option><?php endforeach; ?>
	</optgroup>
<?php endforeach; ?>
</select>
</div>
</fieldset>
</script>

<script id="billPeriodOptions" type="text/x-jquery-tmpl">
	<option value="${value}">${label}</option>
</script>

<script id="accessMenu" type="text/x-jquery-tmpl">
<div class="alignleft action">
<select name="${name}" class="access">
	<optgroup label="<?php Shopp::_e('Allow Access'); ?>">
	<option value="allow" class="allow"><?php Shopp::_e('Allow'); ?></option>
	<option value="allow-all" class="allow"><?php Shopp::_e('Allow All'); ?></option>
	</optgroup>
	<optgroup label="<?php Shopp::_e('Deny Access'); ?>">
	<option value="deny" class="deny"><?php Shopp::_e('Deny'); ?></option>
	<option value="deny-all" class="deny"><?php Shopp::_e('Deny All'); ?></option>
	</optgroup>
</select>
</div>
</script>

<script type="text/javascript">
/* <![CDATA[ */
var sugg_url = '<?php echo wp_nonce_url(admin_url("admin-ajax.php"), "wp_ajax_shopp_suggestions"); ?>',
	pluginuri = <?php echo json_encode(SHOPP_PLUGINURI); ?>,
	rule_groups = <?php echo json_encode($rulegroups); ?>,
	rule_types = <?php echo json_encode($ruletypes); ?>,
	bill_periods = <?php echo json_encode(ShoppPrice::periods()); ?>,
	rules = <?php echo json_encode($MemberPlan->stages); ?>,
	STAGE_LABEL = <?php Shopp::_jse('Content Access Rules'); ?>,
	STAGES_LABEL = <?php Shopp::_jse('Step'); ?>,
	DELETE_RULE_PROMPT = <?php Shopp::_jse('Are you sure you want to delete this rule?'); ?>,
	DELETE_GROUP_PROMPT = <?php Shopp::_jse('Are you sure you want to delete this rule?'); ?>;

/* ]]> */
</script>
<form action="<?php echo $this->url(); ?>" method="post">

<?php if ( $Purchase->shippable ): ?>
<script id="shipment-ui" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<li class="inline-fields">
	<span class="number">${num}.</span>
	<span><input type="text" name="shipment[${id}][tracking]" value="${tracking}" size="30" class="tracking" /><br />
	<label><?php _e('Tracking Number'); ?></label>
	</span>
	<span>
	<select name="shipment[${id}][carrier]">${carriermenu}</select><?php echo ShoppUI::button('delete','delete-shipment[${id}]'); ?><br />
	<label><?php _e('Carrier'); ?></label>
	</span>
</li>
<?php echo $shipmentui = ob_get_clean(); ?>
</script>

<script id="shipnotice-ui" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<div class="shipnotice misc-pub-section">
	<div class="shipment">
		<h4><big><?php _e('Shipments','Shopp'); ?></big></h4>
		<p><?php _e('An email will be sent to notify the customer.','Shopp'); ?></p>
		<input type="hidden" name="ship-notice" value="active" />
		<ol>
			${shipments}
			<li><span class="number">${shipmentnum}.</span> <input type="submit" id="addship-button" name="add-shipment" value="<?php _e('Add Shipment','Shopp'); ?>" class="button-secondary" /></li>
		</ol>

		<input type="submit" id="cancel-ship" name="cancel-shipments" value="<?php _e('Cancel','Shopp'); ?>" class="button-secondary" />
		<div class="submit">
			<input type="submit" name="submit-shipments" value="<?php _e('Send Shipping Notice','Shopp'); ?>" class="button-primary" />
		</div>
	</div>
</div>
<?php echo $shipnotice_ui = ob_get_clean(); ?>
</script>
<?php endif; ?>

<?php if ( ! $Purchase->isvoid() ): ?>
<script id="refund-ui" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<div class="refund misc-pub-section">
	<div class="refunding">
		<h4><big>${title}</big></h4>
		An email will be sent to notify the customer.
		<input type="hidden" name="order-action" value="${action}" />

		<div><label for="message"><?php _e('Message to the customer','Shopp'); ?></label>
			<textarea name="message" id="message" cols="50" rows="7" ></textarea></div>

		<div class="alignright">
			<div class="inline-fields">
				<span>
				<select name="reason">
					<option>&mdash; Select &mdash;</option>
					<?php echo Shopp::menuoptions(stripslashes_deep(shopp_setting('cancel_reasons')), false, true); ?>
				</select><br />
				<label>${reason}</label>
				</span>

				<span><input type="text" name="amount" value="<?php echo Shopp::money($Purchase->total); ?>" ${disable_amount} /><br />
				<label><?php _e('Amount','Shopp'); ?></label></span>
			</div>
		</div>
		<div class="clear"></div>
		<div class="submit">
			<input type="submit" id="cancel-refund" name="cancel-refund" value="${cancel}" class="button-secondary" />
			<div class="alignright">
			<span class="mark-status">
				<input type="hidden" name="send" value="off" />
				<label title="<?php Shopp::__('Enable to process through the payment gateway (%s) and set the Shopp payment status. Disable to update only the Shopp payment status.', $gateway_name); ?>"><input type="checkbox" name="send" value="on" <?php if ($gateway_refunds) echo ' checked="checked"'; ?>/>&nbsp;${send}</label>
			</span>

			<input type="submit" name="process-refund" value="${process}" class="button-primary" />
			</div>
		</div>
	</div>
</div>
<?php echo $refundui = ob_get_clean(); ?>
</script>
<?php endif; ?>

<div class="minor-publishing">

	<div class="minor-publishing-actions headline">
	<div class="misc-pub-section controls">
	<?php
		$printurl = wp_nonce_url(admin_url('admin-ajax.php').'?action=shopp_order_receipt&amp;id='.$Purchase->id,'wp_ajax_shopp_order_receipt');
		$controls = '<div class="alignright"><a id="print-button" href="'.esc_url($printurl).'" class="button hide-if-no-js" target="_blank">'.__('Print Order','Shopp').'</a></div>';
		echo apply_filters('shopp_order_management_controls',$controls,$Purchase);
	?>
	</div>
		<div class="misc-pub-section">
			<div class="status">
			<?php
			if (isset($Purchase->txnevent)) {
				$UI = OrderEventRenderer::renderer($Purchase->txnevent);
				$event = array('<strong>'.$UI->name().'</strong>');
				if ('' != $UI->details()) $event[] = $UI->details();
				if ('' != $UI->date()) $event[] = $UI->date();
				echo '<p>'.join(' &mdash; ',$event).'</p>';
			} else { ?>
				<?php if ( ! empty($Purchase->txnid) ): ?>
				<p><strong><?php _e('Processed by','Shopp'); ?> </strong><?php echo $Purchase->gateway; ?><?php echo (!empty($Purchase->txnid)?" ($Purchase->txnid)":""); ?></p>
				<?php endif; ?>
				<?php
					$output = '';
					if (!empty($Purchase->card) && !empty($Purchase->cardtype))
						$output = '<p><strong>'.$Purchase->txnstatus.'</strong> '.
							__('to','Shopp').' '.
							(!empty($Purchase->cardtype)?$Purchase->cardtype:'').
							(!empty($Purchase->card)?sprintf(" (&hellip;%d)",$Purchase->card):'').'</p>';

					echo apply_filters('shopp_orderui_payment_card',$output, $Purchase);
			}

			if (isset($Purchase->shipevent)): $UI = OrderEventRenderer::renderer($Purchase->shipevent);
				echo '<p><strong>'.$UI->name().'</strong> '.$UI->details().' &mdash; '.$UI->date().'</p>';
			endif;
			?>
			</div>
		</div>
		<div class="manager-ui">
		<?php
			$action = false;
			if (isset($_POST['ship-notice']) && 'active' != $_POST['ship-notice']) $action = 'ship-notice';
			elseif (isset($_POST['edit-billing-address']) || isset($_POST['edit-shipping-address'])) $action = 'edit-address';
			elseif (isset($_POST['cancel-order']) || isset($_POST['refund-order'])) $action = 'refund-order';

			if (isset($_POST['cancel-shipments']) && 'ship-notice' == $action) $action = false;
			if (isset($_POST['cancel-refund']) && 'refund-order' == $action) $action = false;



			if ( 'ship-notice' == $action ) {

				unset($_POST['cancel-order'],$_POST['refund-order']); // ???

				$default = array('tracking' => '', 'carrier' => '');
				$shipment = isset($_POST['shipment']) ? $_POST['shipment'] : array($default);

				$shipments = (int)$_POST['shipments'];

				if ( isset($_POST['delete-shipment']) ) {
					$queue = array_keys($_POST['delete-shipment']);
					foreach ($queue as $index) array_splice($shipment,$index,1);
				}
				if ( isset($_POST['add-shipment']) )
					$shipment[] = $default;

				// Build the shipment entry UIs
				foreach ( $shipment as $id => $package ) {
					extract($package);
					$menu = Shopp::menuoptions($carriers_menu,$carrier,true);
					$shipmentuis = ShoppUI::template($shipmentui, array('${id}' => $id,'${num}' => ($id+1),'${tracking}'=>$tracking,'${carriermenu}'=>$menu ));
				}


				echo ShoppUI::template($shipnotice_ui, array('${shipments}'=>$shipmentuis, '${shipmentnum}'=>count($shipment)+1));
			}

			if ('refund-order' == $action) {
				$data = array(
					'${action}' => 'refund',
					'${title}' => __('Refund Order','Shopp'),
					'${reason}' => __('Reason for refund','Shopp'),
					'${send}' => __('Send to gateway','Shopp'),
					'${cancel}' => __('Cancel Refund','Shopp'),
					'${process}' => __('Process Refund','Shopp')
				);

				if (isset($_POST['cancel-order'])) {
					$data = array(
						'${action}' => 'cancel',
						'${disable_amount}' =>  ' disabled="disabled"',
						'${title}' => __('Cancel Order','Shopp'),
						'${reason}' => __('Reason for cancellation','Shopp'),
						'${send}' => __('Send to gateway','Shopp'),
						'${cancel}' => __('Do Not Cancel','Shopp'),
						'${process}' => __('Cancel Order','Shopp')
					);
				}

				echo ShoppUI::template($refundui, $data);
			}


		?>
		</div>
	</div>
</div>
<?php if ( ! ( $Purchase->isvoid() || $Purchase->refunded ) ): ?>
	<div id="major-publishing-actions">
		<?php if ( ! $Purchase->isvoid() ): ?>
		<div class="alignleft">
			<?php if ( current_user_can('shopp_void') && ! $Purchase->captured ): ?>
				<input type="submit" id="cancel-order" name="cancel-order" value="<?php _e('Cancel Order','Shopp'); ?>" class="button-secondary cancel" />
			<?php endif; ?>
			<?php
			if ( current_user_can('shopp_refund') && ( ('CHARGED' == $Purchase->txnstatus) || ($Purchase->authorized && $Purchase->captured && $Purchase->refunded < $Purchase->total) ) ): ?>
				<input type="submit" id="refund-button" name="refund-order" value="<?php _e('Refund','Shopp'); ?>" class="button-secondary refund" />
			<?php endif; ?>
		</div>
		<?php endif; ?>
		&nbsp;
		<?php if ( $Purchase->authorized || 0 == $Purchase->balance ): ?>
			<?php if ( $Purchase->shippable && 'ship-notice' != $action && is_array(shopp_setting('shipping_carriers')) ): ?>
			<input type="submit" id="shipnote-button" name="ship-notice" value="<?php _e('Send Shipment Notice','Shopp'); ?>" class="button-primary" />
			<?php endif; ?>
			<?php if ( current_user_can('shopp_capture') && ! $Purchase->captured && $gateway_captures ): ?>
			<input type="submit" name="charge" value="<?php _e('Charge Order','Shopp'); ?>" class="button-primary" />
			<?php endif; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
</form>
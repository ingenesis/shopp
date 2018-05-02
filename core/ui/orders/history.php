<table class="widefat history">
	<tfoot>
		<tr class="balance">
			<td colspan="3"><?php Shopp::_e('Order Balance'); ?></td>
			<td><?php echo Shopp::money($Purchase->balance); ?></td>
		</tr>
	</tfoot>
	<tbody>
	<?php foreach ( $Purchase->events as $id => $Event )
		echo apply_filters('shopp_order_manager_event', $Event);
	?>
	</tbody>
</table>
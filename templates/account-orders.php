<?php
/**
 ** WARNING! DO NOT EDIT!
 **
 ** These templates are part of the core Shopp files
 ** and will be overwritten when upgrading Shopp.
 **
 ** For editable templates, setup Shopp theme templates:
 ** http://shopplugin.com/docs/the-catalog/theme-templates/
 **
 **/
?>

<p>
	<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a>
</p>

<?php if ( shopp( 'purchase.get-id' ) ) : ?>
	<?php shopp( 'purchase.receipt' ); ?>
	<?php return; ?>
<?php endif; ?>

<form action="<?php shopp( 'customer.action' ); ?>" method="post" class="shopp validate" autocomplete="off">
	<?php if ( shopp( 'customer.has-purchases' ) ) : ?>
		<table cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Date', 'Shopp' ); ?></th>
					<th scope="col"><?php _e( 'Order', 'Shopp' ); ?></th>
					<th scope="col"><?php _e( 'Status', 'Shopp' ); ?></th>
					<th scope="col"><?php _e( 'Total', 'Shopp' ); ?></th>
				</tr>
			</thead>
			<?php while( shopp( 'customer.purchases' ) ) : ?>
				<tr>
					<td><?php shopp( 'purchase.date' ); ?></td>
					<td><?php shopp( 'purchase.id' ); ?></td>
					<td><?php shopp( 'purchase.status' ); ?></td>
					<td><?php shopp( 'purchase.total' ); ?></td>
					<td><a href="<?php shopp( 'customer.order' ); ?>"><?php _e( 'View Order', 'Shopp' ); ?></a></td>
				</tr>
			<?php endwhile; ?>
		</table>
	<?php else: ?>
		<p><?php _e( 'You have no orders, yet.', 'Shopp' ); ?></p>
	<?php endif; // end 'has-purchases' ?>
</form>

<p>
	<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a>
</p>

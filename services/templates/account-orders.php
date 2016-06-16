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
	<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php Shopp::_e( 'Return to Account Management' ); ?></a>
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
					<th scope="col"><?php Shopp::_e( 'Date' ); ?></th>
					<th scope="col"><?php Shopp::_e( 'Order' ); ?></th>
					<th scope="col"><?php Shopp::_e( 'Status' ); ?></th>
					<th scope="col"><?php Shopp::_e( 'Total' ); ?></th>
				</tr>
			</thead>
			<?php while( shopp( 'customer.purchases' ) ) : ?>
				<tr>
					<td><?php shopp( 'purchase.date' ); ?></td>
					<td><?php shopp( 'purchase.id' ); ?></td>
					<td><?php shopp( 'purchase.status' ); ?></td>
					<td><?php shopp( 'purchase.total' ); ?></td>
					<td><a href="<?php shopp( 'customer.order' ); ?>"><?php Shopp::_e( 'View Order' ); ?></a></td>
				</tr>
			<?php endwhile; ?>
		</table>
	<?php else: ?>
		<p><?php Shopp::_e( 'You have no orders, yet.' ); ?></p>
	<?php endif; // end 'has-purchases' ?>
</form>

<p>
	<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php Shopp::_e( 'Return to Account Management' ); ?></a>
</p>

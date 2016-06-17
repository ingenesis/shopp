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

<h3><?php _e( 'Downloads', 'Shopp' ); ?></h3>

<p>
	<a href="<?php shopp('customer.url'); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a>
</p>

<?php if ( shopp( 'customer.has-downloads' ) ) : ?>
	<table cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th scope="col"><?php _e( 'Product', 'Shopp' ); ?></th>
				<th scope="col"><?php _e( 'Order', 'Shopp' ); ?></th>
				<th scope="col"><?php _e( 'Amount', 'Shopp' ); ?></th>
			</tr>
		</thead>
		<?php while( shopp( 'customer.downloads' ) ) : ?>
			<tr>
				<td>
					<?php shopp( 'customer.download', 'name' ); ?> <?php shopp( 'customer.download', 'variation' ); ?><br />
					<small>
						<a href="<?php shopp( 'customer.download', 'url' ); ?>"><?php _e( 'Download File', 'Shopp' ); ?></a> (<?php shopp( 'customer.download', 'size' ); ?>)
					</small>
				</td>
				<td>
					<?php shopp( 'customer.download', 'purchase' ); ?><br />
					<small><?php shopp( 'customer.download', 'date' ); ?></small>
				</td>
				<td>
					<?php shopp( 'customer.download', 'total' ); ?><br />
					<small><?php shopp( 'customer.download', 'downloads' ); ?> <?php _e( 'Downloads', 'Shopp' ); ?></small>
				</td>
			</tr>
		<?php endwhile; ?>
	</table>
<?php else : ?>
	<p>
		<?php _e( 'You have no digital product downloads available.', 'Shopp' ); ?>
	</p>
<?php endif; // end 'has-downloads' ?>
<p>
	<a href="<?php shopp( 'customer.url' ); ?>">&laquo; <?php _e( 'Return to Account Management', 'Shopp' ); ?></a>
</p>
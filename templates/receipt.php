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

<div id="receipt" class="shopp">
	<table class="transaction">
		<tr>
			<th><?php Shopp::_e( 'Order Num:' ); ?></th>
			<td><?php shopp( 'purchase.id' ); ?></td>
		</tr>
		<tr>
			<th><?php Shopp::_e( 'Order Date:' ); ?></th>
			<td><?php shopp( 'purchase.date' ); ?></td>
		</tr>
		<tr>
			<th><?php Shopp::_e( 'Billed To:' ); ?></th>
			<td><?php shopp( 'purchase.card' ); ?> (<?php shopp( 'purchase.cardtype' ); ?>)</td>
		</tr>
		<tr>
			<th><?php Shopp::_e( 'Transaction:' ); ?></th>
			<td><?php shopp( 'purchase.transactionid' ); ?> (<strong><?php shopp( 'purchase.payment' ); ?></strong>)</td>
		</tr>
	</table>

	<table class="labels">
		<tr>
			<td>
				<fieldset class="billing">
					<legend><?php Shopp::_e( 'Billed to' ); ?></legend>
					<address>
						<?php shopp( 'purchase.firstname' ); ?> <?php shopp( 'purchase.lastname' ); ?><br />
						<?php shopp( 'purchase.company' ); ?><br />
						<?php shopp( 'purchase.address' ); ?><br />
						<?php shopp( 'purchase.xaddress' ); ?>
						<?php shopp( 'purchase.city' ); ?>, <?php shopp( 'purchase.state' ); ?> <?php shopp( 'purchase.postcode' ); ?><br />
						<?php shopp( 'purchase.country' ); ?>
					</address>
				</fieldset>
			</td>
			<?php if ( shopp( 'purchase.hasfreight' ) ) : ?>
				<td>
					<fieldset class="shipping">
						<legend><?php Shopp::_e( 'Ship to' ); ?></legend>
						<address>
							<?php shopp( 'purchase.shipname' ); ?><br /><br />
							<?php shopp( 'purchase.shipaddress' ); ?><br />
							<?php shopp( 'purchase.shipxaddress' ); ?>
							<?php shopp( 'purchase.shipcity' ); ?>, <?php shopp( 'purchase.shipstate' ); ?> <?php shopp( 'purchase.shippostcode' ); ?><br />
							<?php shopp( 'purchase.shipcountry' ); ?>
						</address>
						<p><?php Shopp::_e( 'Shipping:' ); ?> <?php shopp( 'purchase.shipmethod' ); ?></p>
					</fieldset>
				</td>
			<?php endif; ?>
		</tr>
	</table>

	<?php if ( shopp( 'purchase.hasitems' ) ) : ?>
		<table class="order widefat">
			<thead>
				<tr>
					<th scope="col" class="item"><?php Shopp::_e( 'Items Ordered' ); ?></th>
					<th scope="col"><?php Shopp::_e( 'Quantity' ); ?></th>
					<th scope="col" class="money"><?php Shopp::_e( 'Item Price' ); ?></th>
					<th scope="col" class="money"><?php Shopp::_e( 'Item Total' ); ?></th>
				</tr>
			</thead>

			<?php while ( shopp( 'purchase.items' ) ) : ?>
				<tr>
					<td>
						<?php shopp( 'purchase.item-name' ); ?><?php shopp( 'purchase.item-options', 'before= – ' ); ?><br />
						<?php shopp( 'purchase.item-sku' ); ?><br />
						<?php shopp( 'purchase.item-download' ); ?>
						<?php shopp( 'purchase.item-addons-list' ); ?>
						<?php shopp( 'purchase.item-inputs-list' ); ?>
					</td>
					<td><?php shopp( 'purchase.item-quantity' ); ?></td>
					<td class="money"><?php shopp( 'purchase.item-unitprice' ); ?></td>
					<td class="money"><?php shopp( 'purchase.item-total' ); ?></td>
				</tr>
			<?php endwhile; ?>

			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php Shopp::_e( 'Subtotal' ); ?></th>
				<td class="money"><?php shopp( 'purchase.subtotal' ); ?></td>
			</tr>

			<?php if ( shopp( 'purchase.hasdiscount' ) ) : ?>
				<tr class="totals">
					<th scope="row" colspan="3" class="total"><?php Shopp::_e( 'Discount' ); ?></th>
					<td class="money">-<?php shopp( 'purchase.discount' ); ?></td>
				</tr>
			<?php endif; ?>

			<?php if ( shopp( 'purchase.hasfreight' ) ) : ?>
				<tr class="totals">
					<th scope="row" colspan="3" class="total"><?php Shopp::_e( 'Shipping' ); ?></th>
					<td class="money"><?php shopp( 'purchase.shipping' ); ?></td>
				</tr>
			<?php endif; ?>

			<?php if ( shopp( 'purchase.hastax' ) ) : ?>
				<tr class="totals">
					<th scope="row" colspan="3" class="total"><?php Shopp::_e( 'Tax' ); ?></th>
					<td class="money"><?php shopp( 'purchase.tax' ); ?></td>
				</tr>
			<?php endif; ?>

			<tr class="totals">
				<th scope="row" colspan="3" class="total"><?php Shopp::_e( 'Total' ); ?></th>
				<td class="money"><?php shopp( 'purchase.total' ); ?></td>
			</tr>
		</table>

		<?php if ( shopp( 'purchase.has-data' ) ) : ?>
			<ul>
				<?php while ( shopp( 'purchase.orderdata' ) ) : ?>
					<?php if ( shopp( 'purchase.get-data' ) == '' ) continue; ?>
					<li><strong><?php shopp( 'purchase.data', 'name' ); ?>:</strong> <?php shopp( 'purchase.data' ); ?></li>
				<?php endwhile; ?>
			</ul>
		<?php endif; ?>

	<?php else : ?>
		<p class="notice"><?php Shopp::_e( 'There were no items found for this purchase.' ); ?></p>
	<?php endif; ?>
</div>

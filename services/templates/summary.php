<?php
/**
 ** WARNING! DO NOT EDIT!
 **
 ** These templates are part of the core Shopp files
 ** and will be overwritten when upgrading Shopp.
 **
 ** For editable templates, setup Shopp theme templates:
 ** http://docs.shopplugin.net/Setting_Up_Theme_Templates
 **
 **/
?>

<?php if ( shopp( 'cart.hasitems' ) ) : ?>
	<div id="cart" class="shopp">
		<table>
			<tr>
				<th scope="col" class="item"><?php Shopp::_e( 'Cart Items' ); ?></th>
				<th scope="col"><?php Shopp::_e( 'Quantity' ); ?></th>
				<th scope="col" class="money"><?php Shopp::_e( 'Item Price' ); ?></th>
				<th scope="col" class="money"><?php Shopp::_e( 'Item Total' ); ?></th>
			</tr>

			<?php while ( shopp( 'cart.items' ) ) : ?>
				<tr>
					<td>
						<a href="<?php shopp( 'cartitem.url' ); ?>"><?php shopp( 'cartitem.name' ); ?></a>
						<?php shopp( 'cartitem.options', 'show=selected&before= (&after=)' ); ?>
						<?php shopp( 'cartitem.inputs-list' ); ?>
						<?php shopp( 'cartitem.addons-list' ); ?>
					</td>
					<td><?php shopp( 'cartitem.quantity' ); ?></td>
					<td class="money"><?php shopp( 'cartitem.unitprice' ); ?></td>
					<td class="money"><?php shopp( 'cartitem.total' ); ?></td>
				</tr>
			<?php endwhile; ?>

			<?php while ( shopp( 'cart.promos' ) ) : ?>
				<tr>
					<td colspan="4" class="money"><?php shopp( 'cart.promo-name' ); ?><strong><?php shopp( 'cart.promo-discount', array( 'before' => '&nbsp;&mdash;&nbsp;' ) ); ?></strong></td>
				</tr>
			<?php endwhile; ?>

			<tr class="totals">
				<td colspan="2" rowspan="5">
					<?php if ( ( shopp( 'cart.has-shipping-methods' ) ) ) : ?>
						<small><?php Shopp::_e( 'Select a shipping method:' ); ?></small>
						<form action="<?php shopp( 'shipping.url' ); ?>" method="post">
							<ul id="shipping-methods">
								<?php while ( shopp( 'shipping.methods' ) ) : ?>
									<li>
										<label>
											<?php shopp( 'shipping.method-selector' ); ?>
											<?php shopp( 'shipping.method-name' ); ?> &mdash;
											<strong><?php shopp( 'shipping.method-cost' ); ?></strong><br />
											<small><?php shopp( 'shipping.method-delivery' ); ?></small>
										</label>
									</li>
								<?php endwhile; ?>
							</ul>
							<?php shopp( 'shipping.update-button' ); ?>
						</form>
					<?php endif; ?>
				</td>
				<th scope="row"><?php Shopp::_e( 'Subtotal' ); ?></th>
				<td class="money"><?php shopp( 'cart.subtotal' ); ?></td>
			</tr>

			<?php if ( shopp( 'cart.hasdiscount' ) ) : ?>
				<tr class="totals">
					<th scope="row"><?php Shopp::_e( 'Discount' ); ?></th>
					<td class="money">-<?php shopp( 'cart.discount' ); ?></td>
				</tr>
			<?php endif; ?>

			<?php if ( shopp( 'cart.needs-shipped' ) ) : ?>
				<tr class="totals">
					<th scope="row"><?php shopp( 'cart.shipping', 'label=' . Shopp::__( 'Shipping' ) ); ?></th>
					<td class="money"><?php shopp( 'cart.shipping' ); ?></td>
				</tr>
			<?php endif; ?>

			<tr class="totals">
				<th scope="row"><?php shopp( 'cart.tax', 'label=' . Shopp::__( 'Taxes' ) ); ?></th>
				<td class="money"><?php shopp('cart','tax' ); ?></td>
			</tr>
			<tr class="totals total">
				<th scope="row"><?php Shopp::_e( 'Total' ); ?></th>
				<td class="money"><?php shopp( 'cart.total' ); ?></td>
			</tr>
		</table>

		<?php if ( shopp( 'checkout.hasdata' ) ) : ?>
			<ul>
				<?php while ( shopp( 'checkout.orderdata' ) ) : ?>
					<li><strong><?php shopp( 'checkout.data', 'name' ); ?>:</strong> <?php shopp( 'checkout.data' ); ?></li>
				<?php endwhile; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php else : ?>
	<p class="notice"><?php Shopp::_e( 'There are currently no items in your shopping cart.' ); ?></p>
	<p><a href="<?php shopp( 'storefront.url' ); ?>">&laquo; <?php Shopp::_e( 'Continue Shopping' ); ?></a></p>
<?php endif; ?>

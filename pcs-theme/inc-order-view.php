<?php
/** Order tracker, details and invoice. Used by the order received page and My account view order. */
function pcs_order_view( $order, $mode = 'thanks' ) {
	$status = $order->get_status();
	$steps  = array( 'Order placed', 'Packed', 'Out for delivery', 'Delivered' );
	$at     = 0;
	if ( 'pcs-packed' === $status ) { $at = 1; }
	if ( 'pcs-out' === $status ) { $at = 2; }
	if ( 'completed' === $status ) { $at = 3; }
	$cancelled = in_array( $status, array( 'cancelled', 'refunded' ), true );
	$slot  = function_exists( 'pcs_order_slot_text' ) ? pcs_order_slot_text( $order ) : '';
	$name  = $order->get_billing_first_name();
	$addr  = array_filter( array( $order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_city(), $order->get_billing_postcode() ) );
	$items = $order->get_items();
	$count = 0;
	foreach ( $items as $it ) { $count += $it->get_quantity(); }
	$fee   = 0;
	foreach ( $order->get_fees() as $f ) { $fee += (float) $f->get_total(); }
	$wa    = preg_replace( '/\D/', '', pcs_opt( 'whatsapp' ) );
	?>
	<?php if ( 'view' === $mode ) : ?>
	<div class="ty-hero ty-view">
		<h1>Order #<?php echo esc_html( $order->get_order_number() ); ?></h1>
		<p>Placed on <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd M Y, h:i A' ) ); ?></p>
		<span class="ty-pill ty-s-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span>
	</div>
	<?php else : ?>
	<div class="ty-hero">
		<span class="ty-tick"><?php echo pcs_icon( 'check' ); // phpcs:ignore ?></span>
		<h1><?php echo $cancelled ? 'Order cancelled' : esc_html( 'Thank you' . ( $name ? ', ' . $name : '' ) . '!' ); ?></h1>
		<p><?php echo $cancelled ? 'This order has been cancelled.' : 'Your order is placed. We will start cutting and packing it fresh for you.'; ?></p>
		<div class="ty-no">Order <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></div>
	</div>

	<?php endif; ?>

	<?php if ( ! $cancelled ) : ?>
	<ol class="ty-track">
		<?php foreach ( $steps as $i => $st ) : ?><li class="<?php echo $i < $at ? 'done' : ( $i === $at ? 'now' : '' ); ?>"><i><?php echo $i < $at ? '✓' : (int) $i + 1; ?></i><span><?php echo esc_html( $st ); ?></span></li><?php endforeach; ?>
	</ol>
	<?php endif; ?>

	<div class="ty-meta">
		<div><span>Date</span><strong><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd M Y, h:i A' ) ); ?></strong></div>
		<?php if ( $slot ) : ?><div><span>Delivery slot</span><strong><?php echo esc_html( $slot ); ?></strong></div><?php endif; ?>
		<div><span>Payment</span><strong><?php echo wp_kses_post( $order->get_payment_method_title() ?: 'Cash on delivery' ); ?></strong></div>
		<div><span>Total</span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></div>
	</div>

	<section class="ty-inv" id="invoice">
		<header class="ty-inv-h">
			<div class="ty-brand"><?php if ( pcs_opt( 'logo' ) ) : ?><img src="<?php echo esc_url( pcs_opt( 'logo' ) ); ?>" alt="<?php echo esc_attr( pcs_opt( 'store_name' ) ); ?>" height="48"><?php else : ?><strong><?php echo esc_html( pcs_opt( 'store_name' ) ); ?></strong><?php endif; ?></div>
			<div class="ty-inv-t"><h2>Invoice</h2><span>#<?php echo esc_html( $order->get_order_number() ); ?> · <?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd M Y' ) ); ?></span></div>
		</header>
		<div class="ty-items">
			<?php foreach ( $items as $item ) :
				$p   = $item->get_product();
				$img = $p ? get_the_post_thumbnail_url( $p->get_id(), 'thumbnail' ) : '';
				?>
			<div class="ty-item">
				<span class="ty-ph"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" width="72" height="72" loading="lazy"><?php endif; ?></span>
				<div class="ty-it">
					<strong><?php echo esc_html( $item->get_name() ); ?></strong>
					<?php $meta = array(); foreach ( $item->get_formatted_meta_data() as $m ) { $meta[] = wp_strip_all_tags( $m->display_key . ': ' . $m->display_value ); } ?>
					<?php if ( $meta ) : ?><small><?php echo esc_html( implode( ' · ', $meta ) ); ?></small><?php endif; ?>
					<small>Qty <?php echo (int) $item->get_quantity(); ?></small>
				</div>
				<b class="ty-amt"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></b>
			</div>
			<?php endforeach; ?>
		</div>
		<dl class="ty-sum">
			<div><dt>Items (<?php echo (int) $count; ?>)</dt><dd><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div>
			<?php if ( $order->get_total_discount() > 0 ) : ?><div><dt>Discount</dt><dd>−<?php echo wp_kses_post( wc_price( $order->get_total_discount() ) ); ?></dd></div><?php endif; ?>
			<div><dt>Delivery</dt><dd><?php echo $fee > 0 ? wp_kses_post( wc_price( $fee ) ) : 'Free'; ?></dd></div>
			<div class="ty-grand"><dt>Total</dt><dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd></div>
		</dl>
		<div class="ty-cols">
			<div><h3>Delivering to</h3><p><?php echo esc_html( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ); ?><br><?php echo esc_html( implode( ', ', $addr ) ); ?><br><?php echo esc_html( $order->get_billing_phone() ); ?></p></div>
			<div><h3>Payment</h3><p><?php echo wp_kses_post( $order->get_payment_method_title() ?: 'Cash on delivery' ); ?><?php echo 'cod' === $order->get_payment_method() ? '<br>Please keep the exact amount ready.' : ''; ?></p><?php if ( $order->get_customer_note() ) : ?><h3>Note</h3><p><?php echo esc_html( $order->get_customer_note() ); ?></p><?php endif; ?></div>
		</div>
	</section>

	<div class="ty-actions">
		<button type="button" class="btn" onclick="window.print()">Download / print invoice</button>
		<?php if ( is_user_logged_in() ) : ?><a class="btn btn-line" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">My orders</a><?php endif; ?>
		<a class="btn btn-line" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Continue shopping</a>
		<?php if ( $wa ) : ?><a class="btn btn-line" target="_blank" rel="noopener" href="https://wa.me/<?php echo esc_attr( $wa ); ?>?text=<?php echo rawurlencode( 'Hi, about my order #' . $order->get_order_number() ); ?>">Need help? WhatsApp</a><?php endif; ?>
	</div>
<?php
}

<?php
/**
 * Order received page: confirmation, tracker and invoice with product photos.
 *
 * @var WC_Order $order
 * @version 11.2.0
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order ty">
<?php if ( ! $order ) : ?>
	<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
<?php elseif ( $order->has_status( 'failed' ) ) : ?>
	<div class="ty-hero ty-bad">
		<h1>Payment did not go through</h1>
		<p>Your order could not be processed. Please try again.</p>
		<p><a class="btn" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">Pay again</a></p>
	</div>
<?php else :
	do_action( 'woocommerce_before_thankyou', $order->get_id() );
	pcs_order_view( $order, 'thanks' );
	if ( 'cod' !== $order->get_payment_method() ) {
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
	}
endif; ?>
</div>

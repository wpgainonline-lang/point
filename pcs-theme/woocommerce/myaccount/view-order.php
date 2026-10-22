<?php
/**
 * My account → view order: same tracker and invoice as the order received page.
 *
 * @version 10.1.0
 */
defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );
if ( ! $order ) {
	return;
}
$notes = $order->get_customer_order_notes();
?>
<div class="woocommerce-order ty">
	<?php pcs_order_view( $order, 'view' ); ?>
	<?php if ( $notes ) : ?>
	<section class="ty-notes">
		<h2>Order updates</h2>
		<?php foreach ( $notes as $note ) : ?>
		<div class="ty-note"><small><?php echo esc_html( date_i18n( 'd M Y, h:i A', strtotime( $note->comment_date ) ) ); ?></small><?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?></div>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>
</div>

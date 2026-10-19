<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Two extra statuses for the delivery flow: Packed and Out for delivery. */
add_action( 'init', function () {
	register_post_status( 'wc-pcs-packed', array( 'label' => 'Packed', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Packed <span class="count">(%s)</span>', 'Packed <span class="count">(%s)</span>' ) ) );
	register_post_status( 'wc-pcs-out', array( 'label' => 'Out for delivery', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Out for delivery <span class="count">(%s)</span>', 'Out for delivery <span class="count">(%s)</span>' ) ) );
} );
add_filter( 'wc_order_statuses', function ( $s ) {
	$new = array();
	foreach ( $s as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'wc-processing' === $k ) {
			$new['wc-pcs-packed'] = 'Packed';
			$new['wc-pcs-out']    = 'Out for delivery';
		}
	}
	return $new;
} );

/* Slot details on the admin order screen, emails and thank-you page. */
function pcs_order_slot_text( $order ) {
	$d = $order->get_meta( '_pcs_slot_date' );
	$s = $order->get_meta( '_pcs_slot' );
	return $s ? trim( ( $d ? wp_date( 'D, j M', strtotime( $d ) ) : '' ) . ', ' . $s, ', ' ) : '';
}

add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$t = pcs_order_slot_text( $order );
	if ( $t ) {
		echo '<p><strong>Delivery slot:</strong> ' . esc_html( $t ) . '</p>';
	}
} );
add_filter( 'woocommerce_email_order_meta_fields', function ( $f, $sent, $order ) {
	$t = pcs_order_slot_text( $order );
	if ( $t ) {
		$f['pcs_slot'] = array( 'label' => 'Delivery slot', 'value' => $t );
	}
	return $f;
}, 10, 3 );
add_action( 'woocommerce_thankyou', function ( $id ) {
	$o = wc_get_order( $id );
	if ( $o && pcs_order_slot_text( $o ) ) {
		echo '<p><strong>Delivery slot:</strong> ' . esc_html( pcs_order_slot_text( $o ) ) . '</p>';
	}
}, 5 );

/* Orders list: show the slot next to each order. */
add_filter( 'manage_woocommerce_page_wc-orders_columns', function ( $c ) {
	$c['pcs_slot'] = 'Delivery slot';
	return $c;
} );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( $col, $order ) {
	if ( 'pcs_slot' === $col ) {
		echo esc_html( pcs_order_slot_text( $order ) );
	}
}, 10, 2 );
add_filter( 'manage_edit-shop_order_columns', function ( $c ) {
	$c['pcs_slot'] = 'Delivery slot';
	return $c;
} );
add_action( 'manage_shop_order_posts_custom_column', function ( $col, $id ) {
	if ( 'pcs_slot' === $col ) {
		echo esc_html( pcs_order_slot_text( wc_get_order( $id ) ) );
	}
}, 10, 2 );

// The shop's own email address gets new-order mails.
add_filter( 'woocommerce_email_recipient_new_order', function ( $r ) {
	return pcs_opt( 'email' ) ? pcs_opt( 'email' ) : $r;
} );

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------- cart items carry weight + cut ---------------- */

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	if ( isset( $_REQUEST['pcs_g'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$g = (int) $_REQUEST['pcs_g']; // phpcs:ignore
		if ( in_array( $g, pcs_weights( $product_id ), true ) ) {
			$data['pcs_g'] = $g;
			$cut           = isset( $_REQUEST['pcs_cut'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pcs_cut'] ) ) : ''; // phpcs:ignore
			$cuts          = pcs_cuts( $product_id );
			$data['pcs_cut'] = in_array( $cut, $cuts, true ) ? $cut : ( $cuts ? $cuts[0] : '' );
		}
	}
	return $data;
}, 10, 2 );

add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id ) {
	if ( ! isset( $_REQUEST['pcs_g'] ) || ! in_array( (int) $_REQUEST['pcs_g'], pcs_weights( $product_id ), true ) ) { // phpcs:ignore
		wc_add_notice( 'Please choose a weight.', 'error' );
		return false;
	}
	return $ok;
}, 10, 2 );

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $item ) {
		if ( ! empty( $item['pcs_g'] ) ) {
			$kg = (float) wc_get_product( $item['product_id'] )->get_price();
			$item['data']->set_price( round( $kg * $item['pcs_g'] / 1000 ) );
		}
	}
}, 20 );

add_filter( 'woocommerce_get_item_data', function ( $out, $item ) {
	if ( ! empty( $item['pcs_g'] ) ) {
		$out[] = array( 'key' => 'Weight', 'value' => pcs_weight_label( $item['pcs_g'] ) );
	}
	if ( ! empty( $item['pcs_cut'] ) ) {
		$out[] = array( 'key' => 'Cut', 'value' => $item['pcs_cut'] );
	}
	return $out;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( ! empty( $values['pcs_g'] ) ) {
		$line->add_meta_data( 'Weight', pcs_weight_label( $values['pcs_g'] ) );
	}
	if ( ! empty( $values['pcs_cut'] ) ) {
		$line->add_meta_data( 'Cut', $values['pcs_cut'] );
	}
}, 10, 3 );

// Quantity is "number of packs"; allow a sensible maximum.
add_filter( 'woocommerce_quantity_input_args', function ( $a ) {
	$a['max_value'] = 20;
	return $a;
} );
add_filter( 'woocommerce_is_sold_individually', '__return_false' );

/* ---------------- delivery charge, minimum order ---------------- */

add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$after = (float) $cart->get_subtotal() - (float) $cart->get_discount_total();
	$free  = (float) pcs_opt( 'free_above', 0 );
	$fee   = (float) pcs_opt( 'delivery_fee', 0 );
	if ( $cart->get_subtotal() > 0 && $fee > 0 && ! ( $free > 0 && $after >= $free ) ) {
		$cart->add_fee( 'Delivery charge', $fee, false );
	}
} );

add_action( 'woocommerce_check_cart_items', function () {
	$min = (float) pcs_opt( 'min_order', 0 );
	$sub = (float) WC()->cart->get_subtotal();
	if ( $min > 0 && $sub > 0 && $sub < $min ) {
		wc_add_notice( 'Minimum order is ' . pcs_money( $min ) . '. Please add ' . pcs_money( $min - $sub ) . ' more.', 'error' );
	}
} );

/* ---------------- ajax: add / quantity / drawer ---------------- */

function pcs_cart_payload() {
	$cart = WC()->cart;
	$cart->calculate_totals();
	$lines = '';
	foreach ( $cart->get_cart() as $key => $it ) {
		$p    = $it['data'];
		$img  = get_the_post_thumbnail_url( $it['product_id'], 'thumbnail' );
		$meta = array();
		if ( ! empty( $it['pcs_g'] ) ) {
			$meta[] = pcs_weight_label( $it['pcs_g'] );
		}
		if ( ! empty( $it['pcs_cut'] ) ) {
			$meta[] = $it['pcs_cut'];
		}
		$lines .= '<div class="line">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="" width="56" height="56">' : '' ) .
			'<div class="grow"><strong>' . esc_html( $p->get_name() ) . '</strong><div class="muted">' . esc_html( implode( ' · ', $meta ) ) . '</div></div>' .
			'<div class="step"><button type="button" data-key="' . esc_attr( $key ) . '" data-q="' . ( (int) $it['quantity'] - 1 ) . '" aria-label="Less">−</button><span>' . (int) $it['quantity'] . '</span><button type="button" data-key="' . esc_attr( $key ) . '" data-q="' . ( (int) $it['quantity'] + 1 ) . '" aria-label="More">+</button></div>' .
			'<div class="amt">' . esc_html( pcs_money( $it['line_total'] ) ) . '</div></div>';
	}
	$sub  = (float) $cart->get_subtotal();
	$disc = (float) $cart->get_discount_total();
	$fee  = (float) $cart->get_fee_total();
	$min  = (float) pcs_opt( 'min_order', 0 );
	$free = (float) pcs_opt( 'free_above', 0 );
	$hint = '';
	if ( $min && $sub && $sub < $min ) {
		$hint = '<p class="msg bad">Minimum order ' . esc_html( pcs_money( $min ) ) . '. Add ' . esc_html( pcs_money( $min - $sub ) ) . ' more.</p>';
	} elseif ( $free && $sub && ( $sub - $disc ) < $free ) {
		$hint = '<p class="muted">Add ' . esc_html( pcs_money( $free - ( $sub - $disc ) ) ) . ' more for free delivery</p>';
	}
	return array(
		'count' => (int) $cart->get_cart_contents_count(),
		'empty' => $cart->is_empty(),
		'lines' => $lines,
		'foot'  => '<dl><div><dt>Items</dt><dd>' . esc_html( pcs_money( $sub ) ) . '</dd></div>' .
			( $disc ? '<div><dt>Discount</dt><dd>−' . esc_html( pcs_money( $disc ) ) . '</dd></div>' : '' ) .
			'<div><dt>Delivery</dt><dd>' . ( $fee ? esc_html( pcs_money( $fee ) ) : 'Free' ) . '</dd></div>' .
			'<div class="tot"><dt>Total</dt><dd>' . esc_html( pcs_money( $cart->get_total( 'edit' ) ) ) . '</dd></div></dl>' . $hint,
		'below' => (bool) ( $min && $sub && $sub < $min ),
	);
}

function pcs_ajax_start() {
	if ( ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
	wc_clear_notices();
}

add_action( 'wc_ajax_pcs_cart', function () {
	pcs_ajax_start();
	wp_send_json( pcs_cart_payload() );
} );

add_action( 'wc_ajax_pcs_add', function () {
	pcs_ajax_start();
	$id  = (int) ( $_POST['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$qty = max( 1, min( 20, (int) ( $_POST['q'] ?? 1 ) ) ); // phpcs:ignore
	$_REQUEST['pcs_g']   = $_POST['g'] ?? 0; // phpcs:ignore
	$_REQUEST['pcs_cut'] = $_POST['cut'] ?? ''; // phpcs:ignore
	$p = wc_get_product( $id );
	if ( ! $p || ! pcs_in_stock( $id ) || ! WC()->cart->add_to_cart( $id, $qty ) ) {
		$err = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_send_json( array( 'error' => $err ? wp_strip_all_tags( $err[0]['notice'] ) : 'Could not add this item.' ), 400 );
	}
	wc_clear_notices();
	wp_send_json( pcs_cart_payload() );
} );

add_action( 'wc_ajax_pcs_qty', function () {
	pcs_ajax_start();
	$key = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$q   = max( 0, min( 20, (int) ( $_POST['q'] ?? 0 ) ) ); // phpcs:ignore
	if ( WC()->cart->get_cart_item( $key ) ) {
		WC()->cart->set_quantity( $key, $q );
	}
	wp_send_json( pcs_cart_payload() );
} );

// Pages that hold a personal cart must never be served from a page cache.
add_action( 'template_redirect', function () {
	if ( is_cart() || is_checkout() || is_account_page() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'pcs personal page' );
	}
} );
// The ajax endpoints answer per visitor.
add_action( 'init', function () {
	if ( isset( $_GET['wc-ajax'] ) && 0 === strpos( (string) $_GET['wc-ajax'], 'pcs_' ) ) { // phpcs:ignore
		do_action( 'litespeed_control_set_nocache', 'pcs ajax' );
	}
} );

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( ! get_role( 'pcs_customer' ) ) {
		add_role( 'pcs_customer', 'Customer', array( 'read' => true ) );
	}
} );

add_action( 'rest_api_init', function () {
	$pub = array( 'permission_callback' => '__return_true' );
	register_rest_route( 'pcs/v1', '/quote', array( 'methods' => 'POST', 'callback' => 'pcs_rest_quote' ) + $pub );
	register_rest_route( 'pcs/v1', '/pincode', array( 'methods' => 'GET', 'callback' => 'pcs_rest_pincode' ) + $pub );
	register_rest_route( 'pcs/v1', '/contact', array( 'methods' => 'POST', 'callback' => 'pcs_rest_contact' ) + $pub );
	register_rest_route( 'pcs/v1', '/checkout', array( 'methods' => 'POST', 'callback' => 'pcs_rest_checkout', 'permission_callback' => 'is_user_logged_in' ) );
} );

/* ---------- delivery area & slots ---------- */

function pcs_pincodes() {
	$out = array();
	foreach ( pcs_lines( 'pincodes' ) as $l ) {
		$out[ $l[0] ] = $l[1] ?? '';
	}
	return $out;
}

function pcs_slot_start_minutes( $label ) {
	if ( preg_match( '/(\d{1,2}):?(\d{2})?\s*(AM|PM)/i', $label, $m ) ) {
		$h = (int) $m[1] % 12 + ( 'PM' === strtoupper( $m[3] ) ? 12 : 0 );
		return $h * 60 + (int) ( $m[2] ?? 0 );
	}
	return 0;
}

/** Slots that can still be booked, per day (today + tomorrow). */
function pcs_slots_for_days() {
	$now   = current_datetime();
	$lead  = (float) pcs_opt( 'lead_hours', 2 );
	$slots = array_column( pcs_lines( 'slots' ), 0 );
	$days  = array();
	foreach ( array( 0, 1 ) as $d ) {
		$date = $now->modify( "+$d day" );
		$list = array();
		foreach ( $slots as $s ) {
			if ( 0 === $d ) {
				$cut = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ) + $lead * 60;
				if ( pcs_slot_start_minutes( $s ) < $cut ) {
					continue;
				}
			}
			$list[] = $s;
		}
		$days[] = array( 'date' => $date->format( 'Y-m-d' ), 'label' => 0 === $d ? 'Today' : 'Tomorrow', 'slots' => $list );
	}
	return $days;
}

function pcs_rest_pincode( WP_REST_Request $r ) {
	$pin = preg_replace( '/\D/', '', (string) $r['pin'] );
	$all = pcs_pincodes();
	return isset( $all[ $pin ] )
		? array( 'ok' => true, 'area' => $all[ $pin ], 'message' => 'We deliver to ' . ( $all[ $pin ] ?: $pin ) . '.' )
		: array( 'ok' => false, 'message' => 'Sorry, we do not deliver to this pincode yet. We are expanding soon.' );
}

/* ---------- pricing ---------- */

function pcs_find_coupon( $code ) {
	$code = strtoupper( trim( (string) $code ) );
	foreach ( pcs_lines( 'coupons' ) as $c ) {
		if ( strtoupper( $c[0] ) === $code && count( $c ) >= 3 ) {
			return array( 'code' => $code, 'type' => 'percent' === strtolower( $c[1] ) ? 'percent' : 'flat', 'value' => (float) $c[2], 'min' => (float) ( $c[3] ?? 0 ) );
		}
	}
	return null;
}

/** Server-authoritative price calculation. */
function pcs_quote( array $in ) {
	$lines = array();
	$sub   = 0;
	foreach ( (array) ( $in['items'] ?? array() ) as $it ) {
		$id = (int) ( $it['id'] ?? 0 );
		$g  = (int) ( $it['g'] ?? 0 );
		$q  = max( 1, min( 20, (int) ( $it['q'] ?? 1 ) ) );
		$p  = get_post( $id );
		if ( ! $p || 'pcs_product' !== $p->post_type || 'publish' !== $p->post_status || ! in_array( $g, pcs_weights( $id ), true ) ) {
			continue;
		}
		$cuts = pcs_cuts( $id );
		$cut  = isset( $it['cut'] ) && in_array( $it['cut'], $cuts, true ) ? $it['cut'] : ( $cuts ? $cuts[0] : '' );
		$unit = pcs_price_for( $id, $g );
		$ok   = pcs_in_stock( $id );
		$lines[] = array( 'id' => $id, 'name' => $p->post_title, 'g' => $g, 'label' => pcs_weight_label( $g ), 'cut' => $cut, 'q' => $q, 'unit' => $unit, 'total' => $unit * $q, 'ok' => $ok, 'img' => get_the_post_thumbnail_url( $id, 'thumbnail' ) ?: '' );
		if ( $ok ) {
			$sub += $unit * $q;
		}
	}
	$disc   = 0;
	$coupon = null;
	$cmsg   = '';
	if ( ! empty( $in['coupon'] ) ) {
		$coupon = pcs_find_coupon( $in['coupon'] );
		if ( ! $coupon ) {
			$cmsg = 'Invalid coupon code.';
		} elseif ( $sub < $coupon['min'] ) {
			$cmsg   = 'Add items worth ' . pcs_money( $coupon['min'] ) . ' to use this coupon.';
			$coupon = null;
		} else {
			$disc = 'percent' === $coupon['type'] ? (int) round( $sub * $coupon['value'] / 100 ) : (int) $coupon['value'];
			$disc = min( $disc, $sub );
		}
	}
	$after = $sub - $disc;
	$free  = (float) pcs_opt( 'free_above', 0 );
	$fee   = ( $sub > 0 && ! ( $free > 0 && $after >= $free ) ) ? (int) pcs_opt( 'delivery_fee', 0 ) : 0;
	return array(
		'lines' => $lines, 'subtotal' => $sub, 'discount' => $disc, 'coupon' => $coupon ? $coupon['code'] : '', 'coupon_msg' => $cmsg,
		'delivery' => $fee, 'total' => $after + $fee, 'min_order' => (int) pcs_opt( 'min_order', 0 ), 'free_above' => (int) $free,
	);
}

function pcs_rest_quote( WP_REST_Request $r ) {
	return pcs_quote( (array) $r->get_json_params() );
}

/* ---------- placing the order ---------- */

function pcs_payment_methods() {
	$m = array();
	if ( pcs_opt( 'cod_enabled', 1 ) ) {
		$m['cod'] = 'Cash on Delivery';
	}
	// Online gateways (Razorpay etc.) can register themselves here later.
	return apply_filters( 'pcs_payment_methods', $m );
}

function pcs_rest_checkout( WP_REST_Request $r ) {
	$in = (array) $r->get_json_params();
	$q  = pcs_quote( $in );
	$ok = array_filter( $q['lines'], function ( $l ) { return $l['ok']; } );
	if ( ! $ok ) {
		return new WP_Error( 'empty', 'Your cart is empty or items are unavailable.', array( 'status' => 400 ) );
	}
	if ( count( $ok ) !== count( $q['lines'] ) ) {
		return new WP_Error( 'stock', 'Some items are unavailable. Please remove them and try again.', array( 'status' => 400 ) );
	}
	if ( $q['subtotal'] < $q['min_order'] ) {
		return new WP_Error( 'min', 'Minimum order is ' . pcs_money( $q['min_order'] ) . '.', array( 'status' => 400 ) );
	}
	$a   = (array) ( $in['address'] ?? array() );
	$pin = preg_replace( '/\D/', '', (string) ( $a['pincode'] ?? '' ) );
	$pcs = pcs_pincodes();
	if ( ! isset( $pcs[ $pin ] ) ) {
		return new WP_Error( 'pin', 'We do not deliver to this pincode yet.', array( 'status' => 400 ) );
	}
	$name = sanitize_text_field( $a['name'] ?? '' );
	$line = sanitize_textarea_field( $a['line'] ?? '' );
	if ( strlen( $name ) < 2 || strlen( $line ) < 6 ) {
		return new WP_Error( 'addr', 'Please enter your name and full address.', array( 'status' => 400 ) );
	}
	$day  = sanitize_text_field( $in['slot']['date'] ?? '' );
	$slot = sanitize_text_field( $in['slot']['label'] ?? '' );
	$valid = false;
	foreach ( pcs_slots_for_days() as $d ) {
		if ( $d['date'] === $day && in_array( $slot, $d['slots'], true ) ) {
			$valid = true;
		}
	}
	if ( ! $valid ) {
		return new WP_Error( 'slot', 'Please choose an available delivery slot.', array( 'status' => 400 ) );
	}
	$pay = sanitize_key( $in['payment'] ?? 'cod' );
	if ( ! isset( pcs_payment_methods()[ $pay ] ) ) {
		return new WP_Error( 'pay', 'Please choose a payment method.', array( 'status' => 400 ) );
	}
	$user  = wp_get_current_user();
	$phone = get_user_meta( $user->ID, 'pcs_phone', true );
	if ( ! pcs_throttle( 'order' . $user->ID, 10, HOUR_IN_SECONDS ) ) {
		return new WP_Error( 'rate', 'Too many orders. Please call us.', array( 'status' => 429 ) );
	}

	$id = wp_insert_post( array( 'post_type' => 'pcs_order', 'post_status' => 'publish', 'post_title' => 'Order', 'post_author' => $user->ID ), true );
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'save', 'Could not place the order. Please try again.', array( 'status' => 500 ) );
	}
	wp_update_post( array( 'ID' => $id, 'post_title' => 'Order #' . $id ) );
	$meta = array(
		'pcs_status' => 'new', 'pcs_pay' => $pay, 'pcs_pay_status' => 'pending', 'pcs_name' => $name, 'pcs_phone' => $phone,
		'pcs_address' => $line, 'pcs_landmark' => sanitize_text_field( $a['landmark'] ?? '' ), 'pcs_pincode' => $pin, 'pcs_area' => $pcs[ $pin ],
		'pcs_slot_date' => $day, 'pcs_slot' => $slot, 'pcs_note' => sanitize_textarea_field( $in['note'] ?? '' ), 'pcs_quote' => $q,
		'pcs_total' => $q['total'],
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	pcs_notify_new_order( $id );
	return array( 'ok' => true, 'order' => $id );
}

function pcs_rest_contact( WP_REST_Request $r ) {
	if ( ! pcs_throttle( 'contact' . pcs_ip(), 5, HOUR_IN_SECONDS ) ) {
		return new WP_Error( 'rate', 'Too many messages. Please call us.', array( 'status' => 429 ) );
	}
	$name  = sanitize_text_field( $r['name'] );
	$phone = sanitize_text_field( $r['phone'] );
	$msg   = sanitize_textarea_field( $r['message'] );
	if ( strlen( $name ) < 2 || ( strlen( $phone ) < 8 && ! is_email( (string) $r['email'] ) ) ) {
		return new WP_Error( 'bad', 'Please enter your name and a phone number or email.', array( 'status' => 400 ) );
	}
	wp_mail( pcs_opt( 'email' ), 'Website enquiry from ' . $name, "Name: $name\nPhone: $phone\nEmail: " . sanitize_email( (string) $r['email'] ) . "\n\n$msg" );
	return array( 'ok' => true );
}

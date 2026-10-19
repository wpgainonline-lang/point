<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------- delivery area & slots ---------------- */

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
			if ( 0 === $d && pcs_slot_start_minutes( $s ) < (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ) + $lead * 60 ) {
				continue;
			}
			$list[] = $s;
		}
		$days[] = array( 'date' => $date->format( 'Y-m-d' ), 'label' => 0 === $d ? 'Today' : 'Tomorrow', 'slots' => $list );
	}
	return $days;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'pcs/v1', '/pincode', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ( WP_REST_Request $r ) {
		$pin = preg_replace( '/\D/', '', (string) $r['pin'] );
		$all = pcs_pincodes();
		return isset( $all[ $pin ] )
			? array( 'ok' => true, 'area' => $all[ $pin ], 'message' => 'We deliver to ' . ( $all[ $pin ] ?: $pin ) . '.' )
			: array( 'ok' => false, 'message' => 'Sorry, we do not deliver to this pincode yet. We are expanding soon.' );
	} ) );
	register_rest_route( 'pcs/v1', '/contact', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'pcs_rest_contact' ) );
} );

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

/* ---------------- checkout form ---------------- */

add_filter( 'woocommerce_checkout_fields', function ( $f ) {
	$user  = wp_get_current_user();
	$phone = $user->ID ? get_user_meta( $user->ID, 'billing_phone', true ) : '';

	foreach ( array( 'billing_last_name', 'billing_company', 'billing_country', 'billing_state', 'billing_address_2', 'billing_city' ) as $k ) {
		unset( $f['billing'][ $k ] );
	}
	unset( $f['shipping'], $f['account'] );

	$f['billing']['billing_first_name'] = array( 'label' => 'Name', 'required' => true, 'class' => array( 'form-row-wide' ), 'priority' => 10, 'autocomplete' => 'name' );
	$f['billing']['billing_phone']      = array( 'label' => 'Mobile number', 'required' => true, 'type' => 'tel', 'class' => array( 'form-row-wide' ), 'priority' => 20, 'default' => $phone, 'custom_attributes' => $phone ? array( 'readonly' => 'readonly' ) : array() );
	$f['billing']['billing_address_1']  = array( 'label' => 'Delivery address', 'placeholder' => 'House no, street, area', 'required' => true, 'class' => array( 'form-row-wide' ), 'priority' => 30, 'autocomplete' => 'street-address' );
	$f['billing']['billing_postcode']   = array( 'label' => 'Pincode', 'required' => true, 'type' => 'text', 'class' => array( 'form-row-wide' ), 'priority' => 40, 'custom_attributes' => array( 'maxlength' => 6, 'inputmode' => 'numeric' ), 'autocomplete' => 'postal-code' );
	$f['billing']['billing_email']['required'] = false;
	$f['billing']['billing_email']['label']    = 'Email (for order updates)';
	$f['billing']['billing_email']['priority'] = 50;

	$f['pcs'] = array( 'pcs_slot' => array( 'type' => 'select', 'label' => 'Delivery slot', 'required' => true, 'class' => array( 'form-row-wide' ), 'options' => pcs_slot_options(), 'priority' => 10 ) );

	$f['order']['order_comments']['label']       = 'Note for the butcher';
	$f['order']['order_comments']['placeholder'] = 'e.g. small pieces, skin off';
	return $f;
} );

function pcs_slot_options() {
	$o = array( '' => 'Choose a slot' );
	foreach ( pcs_slots_for_days() as $d ) {
		foreach ( $d['slots'] as $s ) {
			$o[ $d['date'] . '|' . $s ] = $d['label'] . ', ' . $s;
		}
	}
	return $o;
}

// Render the slot block after the billing fields.
add_action( 'woocommerce_after_checkout_billing_form', function () {
	$fields = WC()->checkout()->get_checkout_fields( 'pcs' );
	echo '<div class="pcs-slot">';
	foreach ( $fields as $k => $f ) {
		woocommerce_form_field( $k, $f, WC()->checkout()->get_value( $k ) );
	}
	echo '</div>';
} );

add_filter( 'woocommerce_checkout_posted_data', function ( $d ) {
	$d['billing_country'] = 'IN';
	$d['billing_state']   = 'TN';
	$d['billing_city']    = pcs_opt( 'city' );
	return $d;
} );

add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	$pin = preg_replace( '/\D/', '', (string) ( $data['billing_postcode'] ?? '' ) );
	$all = pcs_pincodes();
	if ( ! isset( $all[ $pin ] ) ) {
		$errors->add( 'pcs_pin', 'We do not deliver to this pincode yet.' );
	}
	$slot  = isset( $_POST['pcs_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['pcs_slot'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' !== $slot && ! isset( pcs_slot_options()[ $slot ] ) ) {
		$errors->add( 'pcs_slot', 'Please choose an available delivery slot.' );
	}
	if ( ! pcs_throttle( 'order' . get_current_user_id(), 10, HOUR_IN_SECONDS ) ) {
		$errors->add( 'pcs_rate', 'Too many orders in a short time. Please call us.' );
	}
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	$slot = isset( $_POST['pcs_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['pcs_slot'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $slot && false !== strpos( $slot, '|' ) ) {
		list( $date, $label ) = explode( '|', $slot, 2 );
		$order->update_meta_data( '_pcs_slot_date', $date );
		$order->update_meta_data( '_pcs_slot', $label );
	}
	$pin  = preg_replace( '/\D/', '', (string) $order->get_billing_postcode() );
	$area = pcs_pincodes()[ $pin ] ?? '';
	$order->update_meta_data( '_pcs_area', $area );
	$order->set_billing_city( $area ? $area : pcs_opt( 'city' ) );
} );

add_action( 'woocommerce_checkout_update_user_meta', function ( $user_id, $data ) {
	if ( $user_id && ! empty( $data['billing_first_name'] ) ) {
		wp_update_user( array( 'ID' => $user_id, 'display_name' => $data['billing_first_name'] ) );
	}
}, 10, 2 );

/* ---------------- sign-in is by OTP only ---------------- */

add_filter( 'woocommerce_checkout_must_be_logged_in_message', '__return_empty_string' );
add_action( 'woocommerce_before_checkout_form', function () {
	if ( ! is_user_logged_in() ) {
		echo '<div class="panel narrow">' . pcs_login_box() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}, 5 );
add_filter( 'woocommerce_account_menu_items', function ( $items ) {
	unset( $items['downloads'], $items['edit-address'], $items['edit-account'] );
	return $items;
} );

add_filter( 'gettext', function ( $t, $text ) {
	if ( 'Billing details' === $text ) {
		return 'Delivery details';
	}
	if ( 'Additional information' === $text ) {
		return 'Notes';
	}
	return $t;
}, 10, 2 );

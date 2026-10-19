<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'pcs/v1', '/otp/send', array( 'methods' => 'POST', 'callback' => 'pcs_otp_send', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'pcs/v1', '/otp/verify', array( 'methods' => 'POST', 'callback' => 'pcs_otp_verify', 'permission_callback' => '__return_true' ) );
	register_rest_route( 'pcs/v1', '/logout', array( 'methods' => 'POST', 'callback' => function () { wp_logout(); return array( 'ok' => true ); }, 'permission_callback' => '__return_true' ) );
} );

function pcs_clean_phone( $p ) {
	$p = preg_replace( '/\D/', '', (string) $p );
	$p = substr( $p, -10 );
	return preg_match( '/^[6-9]\d{9}$/', $p ) ? $p : '';
}

function pcs_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '0';
}

function pcs_throttle( $key, $max, $window ) {
	$k = 'pcs_t_' . md5( $key );
	$n = (int) get_transient( $k );
	if ( $n >= $max ) {
		return false;
	}
	set_transient( $k, $n + 1, $window );
	return true;
}

function pcs_otp_send( WP_REST_Request $r ) {
	$phone = pcs_clean_phone( $r['phone'] );
	if ( ! $phone ) {
		return new WP_Error( 'phone', 'Enter a valid 10-digit mobile number.', array( 'status' => 400 ) );
	}
	$provider = pcs_opt( 'otp_provider', 'email' );
	$email    = '';
	if ( 'email' === $provider ) {
		$email = sanitize_email( (string) $r['email'] );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'email', 'Enter a valid email to receive the code.', array( 'status' => 400 ) );
		}
	}
	if ( ! pcs_throttle( 'ph' . $phone, 5, HOUR_IN_SECONDS ) || ! pcs_throttle( 'ip' . pcs_ip(), 20, HOUR_IN_SECONDS ) ) {
		return new WP_Error( 'rate', 'Too many requests. Please try again later.', array( 'status' => 429 ) );
	}
	if ( get_transient( 'pcs_otp_gap_' . $phone ) ) {
		return new WP_Error( 'wait', 'Please wait 30 seconds before asking for a new code.', array( 'status' => 429 ) );
	}
	$code = (string) random_int( 100000, 999999 );
	set_transient( 'pcs_otp_' . $phone, array( 'h' => wp_hash_password( $code ), 'tries' => 0, 'name' => sanitize_text_field( (string) $r['name'] ), 'email' => $email ), 10 * MINUTE_IN_SECONDS );
	set_transient( 'pcs_otp_gap_' . $phone, 1, 30 );

	$sent = pcs_otp_deliver( $provider, $phone, $code, $email );
	if ( is_wp_error( $sent ) ) {
		delete_transient( 'pcs_otp_' . $phone );
		return $sent;
	}
	return array( 'ok' => true );
}

function pcs_otp_deliver( $provider, $phone, $code, $email ) {
	$store = pcs_opt( 'store_name' );
	$key   = pcs_opt( 'otp_key', '' );
	$tpl   = pcs_opt( 'otp_template', '' );
	switch ( $provider ) {
		case 'email':
			return wp_mail( $email, "$store login code", "Your $store login code is $code. It is valid for 10 minutes." ) ? true : new WP_Error( 'mail', 'Could not send email. Please try again.', array( 'status' => 502 ) );
		case '2factor':
			$url  = 'https://2factor.in/API/V1/' . rawurlencode( $key ) . '/SMS/' . $phone . '/' . $code . ( $tpl ? '/' . rawurlencode( $tpl ) : '' );
			$res  = wp_remote_get( $url, array( 'timeout' => 15 ) );
			$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
			return ( $body && 'Success' === ( $body['Status'] ?? '' ) ) ? true : new WP_Error( 'sms', 'Could not send SMS. Please try again.', array( 'status' => 502 ) );
		case 'msg91':
			$url = add_query_arg( array( 'template_id' => $tpl, 'mobile' => '91' . $phone, 'authkey' => $key, 'otp' => $code ), 'https://control.msg91.com/api/v5/otp' );
			$res = wp_remote_post( $url, array( 'timeout' => 15 ) );
			$b   = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
			return ( $b && 'success' === ( $b['type'] ?? '' ) ) ? true : new WP_Error( 'sms', 'Could not send SMS. Please try again.', array( 'status' => 502 ) );
		case 'test':
			update_option( 'pcs_test_otp', $phone . ' : ' . $code, false );
			return true;
	}
	return new WP_Error( 'cfg', 'OTP is not configured.', array( 'status' => 500 ) );
}

function pcs_otp_verify( WP_REST_Request $r ) {
	$phone = pcs_clean_phone( $r['phone'] );
	$code  = preg_replace( '/\D/', '', (string) $r['code'] );
	$rec   = $phone ? get_transient( 'pcs_otp_' . $phone ) : false;
	if ( ! $rec ) {
		return new WP_Error( 'expired', 'Code expired. Please request a new one.', array( 'status' => 400 ) );
	}
	if ( $rec['tries'] >= 5 ) {
		delete_transient( 'pcs_otp_' . $phone );
		return new WP_Error( 'tries', 'Too many wrong attempts. Request a new code.', array( 'status' => 429 ) );
	}
	if ( ! wp_check_password( $code, $rec['h'] ) ) {
		$rec['tries']++;
		set_transient( 'pcs_otp_' . $phone, $rec, 10 * MINUTE_IN_SECONDS );
		return new WP_Error( 'wrong', 'Wrong code. Please check and try again.', array( 'status' => 400 ) );
	}
	delete_transient( 'pcs_otp_' . $phone );

	$login = 'pcs_' . $phone;
	$user  = get_user_by( 'login', $login );
	if ( ! $user ) {
		$name = $rec['name'] ? $rec['name'] : 'Customer';
		$uid  = wp_insert_user( array( 'user_login' => $login, 'user_pass' => wp_generate_password( 32 ), 'display_name' => $name, 'first_name' => $name, 'role' => 'customer', 'user_email' => $rec['email'] ) );
		if ( is_wp_error( $uid ) ) {
			return new WP_Error( 'user', 'Could not create your account.', array( 'status' => 500 ) );
		}
		update_user_meta( $uid, 'pcs_phone', $phone );
		update_user_meta( $uid, 'billing_phone', $phone );
		update_user_meta( $uid, 'billing_first_name', $name );
		$user = get_user_by( 'id', $uid );
	}
	if ( user_can( $user, 'edit_posts' ) ) {
		return new WP_Error( 'staff', 'This number cannot be used to log in.', array( 'status' => 403 ) );
	}
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	return array( 'ok' => true, 'name' => $user->display_name );
}

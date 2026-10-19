<?php
/**
 * Plugin Name: PCS Meat Shop
 * Description: Fresh-meat layer for WooCommerce: weight & cut options, delivery slots, pincode check, OTP login, ajax cart drawer and shop settings.
 * Version: 0.2.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PCS_VERSION', '0.2.0' );
define( 'PCS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCS_URL', plugin_dir_url( __FILE__ ) );

require PCS_DIR . 'inc/settings.php';
require PCS_DIR . 'inc/otp.php';
require PCS_DIR . 'inc/seed.php';
require PCS_DIR . 'inc/legacy.php';

add_action( 'admin_notices', function () {
	if ( ! class_exists( 'WooCommerce' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-error"><p><strong>PCS Shop</strong> needs WooCommerce to be installed and active.</p></div>';
	}
} );

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		require PCS_DIR . 'inc/products.php';
		require PCS_DIR . 'inc/cart.php';
		require PCS_DIR . 'inc/checkout.php';
		require PCS_DIR . 'inc/orders.php';
		require PCS_DIR . 'inc/shortcodes.php';
	}
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	wp_enqueue_script( 'pcs-shop', PCS_URL . 'assets/shop.js', array(), (string) filemtime( PCS_DIR . 'assets/shop.js' ), true );
	wp_localize_script( 'pcs-shop', 'PCS', array(
		'ajax'     => esc_url_raw( home_url( '/?wc-ajax=' ) ),
		'api'      => esc_url_raw( rest_url( 'pcs/v1/' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'loggedIn' => is_user_logged_in(),
		'otpEmail' => 'email' === pcs_opt( 'otp_provider', 'email' ),
		'checkout' => esc_url( wc_get_checkout_url() ),
		'shop'     => esc_url( wc_get_page_permalink( 'shop' ) ),
		'onCheckout' => function_exists( 'is_checkout' ) && is_checkout(),
		'slots'    => pcs_slots_for_days(),
		'pins'     => pcs_pincodes(),
		'currency' => '₹',
	) );
} );

// Old URLs from the first version of the shop.
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( preg_match( '#^shop/(chicken|mutton)$#', $path, $m ) ) {
		$t = get_term_by( 'slug', $m[1], 'product_cat' );
		if ( $t ) {
			wp_safe_redirect( get_term_link( $t ), 301 );
			exit;
		}
	}
	if ( 'products' === $path && function_exists( 'wc_get_page_permalink' ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'shop' ), 301 );
		exit;
	}
	if ( 'account' === $path && function_exists( 'wc_get_page_permalink' ) ) {
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ), 301 );
		exit;
	}
} );

// Keep staff logged-in screens out of reach of customers.
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || ! is_user_logged_in() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/' ) );
	exit;
} );
add_filter( 'show_admin_bar', function ( $s ) {
	return current_user_can( 'edit_posts' ) ? $s : false;
} );

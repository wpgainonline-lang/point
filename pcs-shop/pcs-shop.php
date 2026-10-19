<?php
/**
 * Plugin Name: PCS Shop
 * Description: Lightweight fresh-meat store: products, cart, checkout (COD), OTP login, delivery slots, orders and settings. No page builder, no WooCommerce.
 * Version: 0.1.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PCS_VERSION', '0.1.0' );
define( 'PCS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCS_URL', plugin_dir_url( __FILE__ ) );

require PCS_DIR . 'inc/settings.php';
require PCS_DIR . 'inc/data.php';
require PCS_DIR . 'inc/otp.php';
require PCS_DIR . 'inc/checkout.php';
require PCS_DIR . 'inc/orders.php';
require PCS_DIR . 'inc/shortcodes.php';
require PCS_DIR . 'inc/seed.php';

register_activation_hook( __FILE__, function () {
	pcs_register_types();
	add_role( 'pcs_customer', 'Customer', array( 'read' => true ) );
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'pcs-shop', PCS_URL . 'assets/shop.js', array(), PCS_VERSION, true );
	wp_localize_script( 'pcs-shop', 'PCS', array(
		'api'      => esc_url_raw( rest_url( 'pcs/v1/' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'loggedIn' => is_user_logged_in(),
		'otpEmail' => 'email' === pcs_opt( 'otp_provider', 'email' ),
		'checkout' => esc_url( pcs_page_url( 'checkout' ) ),
		'account'  => esc_url( pcs_page_url( 'account' ) ),
		'slots'    => pcs_slots_for_days(),
		'currency' => '₹',
	) );
} );

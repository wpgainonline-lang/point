<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	register_nav_menus( array( 'main' => 'Main menu' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'pcs-fresh', get_stylesheet_uri(), array(), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
} );

// Keep the front end light: no emoji, embeds or block-library CSS.
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
} );
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );

// The theme styles WooCommerce itself; keep its stock CSS and block styles off.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'wc-blocks-vendors-style' );
}, 100 );

function pcs_theme_nav() {
	$items = array(
		array( 'Home', home_url( '/' ), 'home' ),
		array( 'Chicken', get_term_link( 'chicken', 'product_cat' ), 'chicken' ),
		array( 'Mutton', get_term_link( 'mutton', 'product_cat' ), 'mutton' ),
		array( 'About', pcs_page_url( 'about-us' ), 'info' ),
		array( 'Quality', pcs_page_url( 'quality-hygiene' ), 'shield' ),
		array( 'Contact', pcs_page_url( 'contact-us' ), 'phone' ),
	);
	$cur = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . strtok( $_SERVER['REQUEST_URI'] ?? '/', '?' );
	foreach ( $items as $i ) {
		if ( is_wp_error( $i[1] ) ) {
			continue;
		}
		echo '<a href="' . esc_url( $i[1] ) . '"' . ( untrailingslashit( $i[1] ) === untrailingslashit( $cur ) ? ' class="on"' : '' ) . '>' . pcs_icon( $i[2] ) . '<span>' . esc_html( $i[0] ) . '</span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/** Tiny inline line icons (24x24, stroke only) so no icon font or image requests are needed. */
function pcs_icon( $name ) {
	$p = array(
		'snow'  => '<path d="M12 2v20M4 7l16 10M20 7L4 17M9 3l3 2 3-2M9 21l3-2 3 2"/>',
		'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
		'knife' => '<path d="M4 20L16 8c2-2 4-3 5-3 0 1-1 3-3 5L9 19z"/><path d="M4 20l3-1"/>',
		'bike'  => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M6 17l4-8h5l3 8M10 9H7M13 9l-2 8"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'cash'  => '<rect x="3" y="6" width="18" height="12"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
		'pin'   => '<path d="M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
		'mail'  => '<rect x="3" y="5" width="18" height="14"/><path d="M3 7l9 7 9-7"/>',
		'home'  => '<path d="M4 11l8-7 8 7v9h-5v-6H9v6H4z"/>',
		'grid'  => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
		'cart'  => '<path d="M3 4h3l2.5 11h9L20 7H7"/><circle cx="10" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
		'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
		'chicken' => '<g transform="rotate(45 12 12)"><ellipse cx="12" cy="8" rx="4.6" ry="6"/><path d="M12 14v5.5"/><circle cx="10.5" cy="20.8" r="1.3"/><circle cx="13.5" cy="20.8" r="1.3"/></g>',
		'mutton' => '<path d="M4.5 12C4.5 7 8.5 4 14 4c4 0 6.5 2 6.5 5 0 5-4.500 11-9.500 11-4 0-6.500-3-6.500-8z"/><circle cx="14" cy="10" r="2.300"/>',
		'info'  => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
		'menu'  => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'search' => '<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/>',
		'up'    => '<path d="M6 14l6-6 6 6"/>',
	);
	return '<svg class="ic" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="square" aria-hidden="true">' . ( $p[ $name ] ?? '' ) . '</svg>';
}

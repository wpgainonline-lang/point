<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
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

function pcs_theme_nav() {
	$items = array(
		array( 'Home', home_url( '/' ) ),
		array( 'Chicken', get_term_link( 'chicken', 'pcs_cat' ) ),
		array( 'Mutton', get_term_link( 'mutton', 'pcs_cat' ) ),
		array( 'About', pcs_page_url( 'about-us' ) ),
		array( 'Quality & Hygiene', pcs_page_url( 'quality-hygiene' ) ),
		array( 'Contact', pcs_page_url( 'contact-us' ) ),
	);
	$cur = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . strtok( $_SERVER['REQUEST_URI'] ?? '/', '?' );
	foreach ( $items as $i ) {
		if ( is_wp_error( $i[1] ) ) {
			continue;
		}
		echo '<a href="' . esc_url( $i[1] ) . '"' . ( untrailingslashit( $i[1] ) === untrailingslashit( $cur ) ? ' class="on"' : '' ) . '>' . esc_html( $i[0] ) . '</a>';
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
		'up'    => '<path d="M6 14l6-6 6 6"/>',
	);
	return '<svg class="ic" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="square" aria-hidden="true">' . ( $p[ $name ] ?? '' ) . '</svg>';
}

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
	wp_enqueue_style( 'pcs-fresh', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
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

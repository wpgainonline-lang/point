<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Hidden registration of the first version's product type, only so its data can be read during migration.
add_action( 'init', function () {
	if ( ! post_type_exists( 'pcs_product' ) ) {
		register_post_type( 'pcs_product', array( 'public' => false, 'show_ui' => false, 'rewrite' => false, 'supports' => array( 'title', 'editor', 'thumbnail' ) ) );
	}
	if ( ! taxonomy_exists( 'pcs_cat' ) ) {
		register_taxonomy( 'pcs_cat', 'pcs_product', array( 'public' => false, 'show_ui' => false, 'rewrite' => false ) );
	}
}, 5 );

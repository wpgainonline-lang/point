<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * About page content, editable at Meat Shop → About page.
 * ------------------------------------------------------------------ */

function pcs_about_defaults() {
	return array(
		'hero_kicker' => 'About us',
		'hero_title'  => 'Fresh meat. Proper cuts. Honest service.',
		'hero_text'   => 'House of Cutts is a trusted meat shop offering fresh mutton and chicken, prepared with care, cleanliness and professional cutting.',
		'hero_image'  => '',
		'story_title' => 'Every good meal begins with quality ingredients',
		'story'       => "That is why we focus on freshness, hygienic handling and dependable service.\n\nOur goal is simple: to provide fresh meat, proper cuts and honest service that customers can trust every day.",
		'story_image' => '',
		'stats'       => "7|Days a week open\n{products}|Fresh cuts to choose from\n2|Chicken and mutton\nChilled|Kept cold till it reaches you",
		'vision'      => 'To become India’s most trusted chilled meat brand, changing the way families buy meat by making freshness, hygiene, quality and transparency a standard, not a compromise.',
		'mission'     => 'To make quality meat a simple and worry-free choice for every family. Fresh meat. Honest service. Quality you can trust.',
		'values_title' => 'Why choose House of Cutts',
		'values'      => "Fresh chilled meat|Our meat is carefully handled and maintained under chilled conditions to preserve its natural freshness, texture and flavour.\nHygiene you can trust|From handling and cutting to packing, we follow strict hygiene practices because clean meat starts with a clean process.\nCuts made your way|Whether it’s curry cut, biryani cut, boneless or a special requirement, our experienced meat professionals prepare every cut to suit the way you cook.\nQuality you can count on|We believe good meat should be consistent every time you order, from the quality of the meat to the way it is cut and packed.",
		'steps_title' => 'How your meat is handled',
		'steps'       => "Carefully sourced|We source quality chicken from trusted suppliers and check it before processing.\nHygienically processed|The chicken is cleaned and processed in a controlled, hygienic environment.\nFreshly chilled|The processed chicken is rapidly chilled to help maintain its freshness, texture and quality.\nCarefully cut|Our trained meat professionals prepare the cuts you need, from curry cut and biryani cut to boneless, breast and drumstick.\nWeighed and packed|Every portion is accurately weighed and hygienically packed, ready for your kitchen.\nKept chilled until you get it|The meat remains under controlled chilled conditions through storage and delivery, helping maintain the cold chain.",
		'visit_title' => 'Come and see the counter yourself',
		'visit_text'  => 'You are welcome to look at how the meat is stored, cut and packed before you order. Walk in any day, or call ahead and we will keep your cut ready.',
		'cta_title'   => 'Fresh chicken and mutton, delivered in Chennai',
		'cta_text'    => 'Pick your cuts, choose a delivery slot and pay on delivery.',
	);
}

function pcs_about( $key ) {
	return pcs_pc_get( 'pcs_about', pcs_about_defaults(), $key );
}

function pcs_about_lines( $key ) {
	return pcs_pc_lines( 'pcs_about', pcs_about_defaults(), $key );
}

function pcs_about_fields() {
	return array(
		'Top banner'            => array(
			'hero_kicker' => array( 'Small heading', 'text' ),
			'hero_title'  => array( 'Main heading', 'text' ),
			'hero_text'   => array( 'Intro text', 'textarea' ),
			'hero_image'  => array( 'Banner image', 'image', 'Leave empty to use your product photos.' ),
		),
		'Our story'             => array(
			'story_title' => array( 'Heading', 'text' ),
			'story'       => array( 'Story text', 'textarea', 'Leave a blank line between paragraphs.' ),
			'story_image' => array( 'Story image', 'image', 'Leave empty to use a product photo.' ),
		),
		'Numbers strip'         => array(
			'stats' => array( 'Numbers', 'textarea', 'One per line: Big text|Label. {products} becomes the number of products in your shop.' ),
		),
		'Vision & mission'      => array(
			'vision'  => array( 'Vision', 'textarea' ),
			'mission' => array( 'Mission', 'textarea' ),
		),
		'Why choose us'         => array(
			'values_title' => array( 'Heading', 'text' ),
			'values'       => array( 'Points', 'textarea', 'One per line: Title|Text' ),
		),
		'How the meat is handled' => array(
			'steps_title' => array( 'Heading', 'text' ),
			'steps'       => array( 'Steps', 'textarea', 'One per line: Title|Text (shown as numbered steps)' ),
		),
		'Visit the shop'        => array(
			'visit_title' => array( 'Heading', 'text' ),
			'visit_text'  => array( 'Text', 'textarea', 'Address, phone and timings come from Meat Shop → Settings.' ),
		),
		'Bottom banner'         => array(
			'cta_title' => array( 'Heading', 'text' ),
			'cta_text'  => array( 'Text', 'text' ),
		),
	);
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'pcs-settings', 'Settings', 'Settings', 'manage_options', 'pcs-settings', 'pcs_settings_page' );
	add_submenu_page( 'pcs-settings', 'About page', 'About page', 'manage_options', 'pcs-about', function () {
		pcs_pc_admin( 'pcs_about', pcs_about_fields(), pcs_about_defaults(), 'About page', 'Edit the words and photos of the About Us page.', 'about-us' );
	} );
} );

/** Image for a section: the chosen one, else a product photo. */
function pcs_about_image( $key, $fallback_index = 0 ) {
	$u = pcs_about( $key );
	if ( $u ) {
		return $u;
	}
	$ids = get_posts( array( 'post_type' => 'product', 'numberposts' => 12, 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC', 'meta_key' => '_thumbnail_id' ) );
	$id  = $ids[ $fallback_index ] ?? ( $ids[0] ?? 0 );
	return $id ? (string) get_the_post_thumbnail_url( $id, 'large' ) : '';
}

function pcs_about_stat_text( $v ) {
	$n = (int) wp_count_posts( 'product' )->publish;
	return str_replace( '{products}', (string) $n, $v );
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Quality & Hygiene page content, editable at Meat Shop → Quality page. */

function pcs_quality_defaults() {
	return array(
		'hero_kicker'  => 'Our commitment',
		'hero_title'   => 'Quality & Hygiene',
		'hero_text'    => 'Meat is only as good as the way it is handled. At House of Cutts, cleanliness is not a one-time effort. It is the routine we follow before the shutter opens and after the last order goes out.',
		'hero_image'   => '',
		'pillars'      => "Before the shutter opens|Cleanliness is a routine, not a one-time effort. Every day starts with a clean counter.\nAt every stage|From selection and storage to cutting and packing, every stage has a clear standard.\nAfter the last order|The same care continues after the last order goes out, so the next day starts clean.",
		'steps_kicker' => 'Step by step',
		'steps_title'  => 'Our chicken processing',
		'steps_text'   => 'What actually happens between selection and the bag you carry home.',
		'steps'        => "Carefully sourced|We source quality chicken from trusted suppliers and check it before processing.\nHygienically processed|The chicken is cleaned and processed in a controlled, hygienic environment.\nFreshly chilled|The processed chicken is rapidly chilled to help maintain its freshness, texture and quality.\nCarefully cut|Our trained meat professionals prepare the chicken into the cuts you need, from curry cut and biryani cut to boneless, breast, drumstick and more.\nWeighed and packed|Every portion is accurately weighed and hygienically packed, ready for your kitchen.\nKept chilled until you get it|The chicken remains under controlled chilled conditions through storage and delivery, helping maintain the cold chain until it reaches you.",
		'steps2_title' => '',
		'steps2_text'  => '',
		'steps2'       => '',
		'cuts_title'   => 'Cuts you can order',
		'visit_title'  => 'Come and see the counter yourself',
		'visit_text'   => 'You are welcome to look at how the meat is stored, cut and packed before you order. Walk in any day, or call ahead and we will keep your cut ready.',
	);
}

function pcs_quality( $key ) {
	return pcs_pc_get( 'pcs_quality', pcs_quality_defaults(), $key );
}

function pcs_quality_lines( $key ) {
	return pcs_pc_lines( 'pcs_quality', pcs_quality_defaults(), $key );
}

function pcs_quality_fields() {
	return array(
		'Top banner'      => array(
			'hero_kicker' => array( 'Small heading', 'text' ),
			'hero_title'  => array( 'Main heading', 'text' ),
			'hero_text'   => array( 'Intro text', 'textarea' ),
			'hero_image'  => array( 'Banner image', 'image', 'Leave empty to use a product photo.' ),
		),
		'Three promises'  => array(
			'pillars' => array( 'Cards', 'textarea', 'One per line: Title|Text' ),
		),
		'Process 1'       => array(
			'steps_kicker' => array( 'Small heading', 'text' ),
			'steps_title'  => array( 'Heading', 'text' ),
			'steps_text'   => array( 'Intro', 'text' ),
			'steps'        => array( 'Steps', 'textarea', 'One per line: Title|Text. Shown as numbered steps.' ),
		),
		'Process 2 (optional, e.g. mutton)' => array(
			'steps2_title' => array( 'Heading', 'text', 'Leave this and the steps empty to hide the second process.' ),
			'steps2_text'  => array( 'Intro', 'text' ),
			'steps2'       => array( 'Steps', 'textarea', 'One per line: Title|Text' ),
		),
		'Cuts'            => array(
			'cuts_title' => array( 'Heading', 'text', 'Shows your products with photos. Add or remove products under Products.' ),
		),
		'Visit the shop'  => array(
			'visit_title' => array( 'Heading', 'text' ),
			'visit_text'  => array( 'Text', 'textarea' ),
		),
	);
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'pcs-settings', 'Quality page', 'Quality page', 'manage_options', 'pcs-quality', function () {
		pcs_pc_admin( 'pcs_quality', pcs_quality_fields(), pcs_quality_defaults(), 'Quality & Hygiene page', 'Edit the words and photos of the Quality & Hygiene page.', 'quality-hygiene' );
	} );
} );

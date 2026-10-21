<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Contact page content, editable at Meat Shop → Contact page. Address, phone, email and timings come from Settings. */

function pcs_contact_defaults() {
	return array(
		'hero_kicker' => 'Contact',
		'hero_title'  => 'Get in touch',
		'hero_text'   => 'Call us, message us or simply walk in. We are happy to tell you what is fresh today before you decide.',
		'visit_note'  => 'Walk in any day and see the counter.',
		'call_note'   => 'Call ahead to check availability of a specific cut or quantity before you visit.',
		'email_note'  => 'For bulk orders, function requirements and general enquiries.',
		'hours_note'  => 'Sunday mornings are our busiest hours. Call ahead if you need a large quantity.',
		'form_kicker' => 'Write to us',
		'form_title'  => 'Tell us what you need',
		'form_text'   => 'Send us a message and we will get back to you.',
		'show_map'    => 'yes',
		'bulk_title'  => 'Bulk orders and functions',
		'bulk_text'   => 'Planning a function or need a large quantity? Call or email us with your requirement and we will help you with the cuts and quantity.',
		'cta_title'   => 'Fresh chicken and mutton, delivered in Chennai',
		'cta_text'    => 'Pick your cuts, choose a delivery slot and pay on delivery.',
	);
}

function pcs_contact_p( $key ) {
	return pcs_pc_get( 'pcs_contact', pcs_contact_defaults(), $key );
}

function pcs_contact_fields() {
	return array(
		'Top banner'     => array(
			'hero_kicker' => array( 'Small heading', 'text' ),
			'hero_title'  => array( 'Main heading', 'text' ),
			'hero_text'   => array( 'Intro text', 'textarea' ),
		),
		'Contact cards'  => array(
			'visit_note' => array( 'Note under the address', 'text', 'The address itself is in Meat Shop → Settings.' ),
			'call_note'  => array( 'Note under the phone number', 'text' ),
			'email_note' => array( 'Note under the email', 'text' ),
			'hours_note' => array( 'Note under the timings', 'text' ),
		),
		'Message form'   => array(
			'form_kicker' => array( 'Small heading', 'text' ),
			'form_title'  => array( 'Heading', 'text' ),
			'form_text'   => array( 'Text', 'text' ),
			'show_map'    => array( 'Show map (yes / no)', 'text', 'Type no to hide the map.' ),
		),
		'Bulk orders'    => array(
			'bulk_title' => array( 'Heading', 'text' ),
			'bulk_text'  => array( 'Text', 'textarea' ),
		),
		'Bottom banner'  => array(
			'cta_title' => array( 'Heading', 'text' ),
			'cta_text'  => array( 'Text', 'text' ),
		),
	);
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'pcs-settings', 'Contact page', 'Contact page', 'manage_options', 'pcs-contact', function () {
		pcs_pc_admin( 'pcs_contact', pcs_contact_fields(), pcs_contact_defaults(), 'Contact page', 'Edit the words of the Contact page. Address, phone, email and timings are in Meat Shop → Settings.', 'contact-us' );
	} );
} );

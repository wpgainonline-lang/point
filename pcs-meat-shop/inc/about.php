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
	static $all = null;
	if ( null === $all ) {
		$all = wp_parse_args( array_filter( (array) get_option( 'pcs_about', array() ), function ( $v ) { return '' !== $v; } ), pcs_about_defaults() );
	}
	return $all[ $key ] ?? '';
}

function pcs_about_lines( $key ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) pcs_about( $key ) ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l ) {
			$out[] = array_map( 'trim', explode( '|', $l, 2 ) );
		}
	}
	return $out;
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
	add_submenu_page( 'pcs-settings', 'About page', 'About page', 'manage_options', 'pcs-about', 'pcs_about_admin' );
} );

function pcs_about_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_media();
	if ( isset( $_POST['pcs_about_nonce'] ) && wp_verify_nonce( $_POST['pcs_about_nonce'], 'pcs_about' ) ) {
		$new = array();
		foreach ( pcs_about_fields() as $group ) {
			foreach ( $group as $k => $f ) {
				$v         = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
				$new[ $k ] = 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : ( 'image' === $f[1] ? esc_url_raw( $v ) : sanitize_text_field( $v ) );
			}
		}
		update_option( 'pcs_about', $new );
		if ( function_exists( 'do_action' ) ) {
			do_action( 'litespeed_purge_all' );
		}
		echo '<div class="notice notice-success"><p>Saved. <a href="' . esc_url( get_permalink( get_page_by_path( 'about-us' ) ) ) . '" target="_blank" rel="noopener">View the About page</a></p></div>';
		echo '<script>setTimeout(function(){location.href=location.href},600)</script>';
	}
	$cur = wp_parse_args( array_filter( (array) get_option( 'pcs_about', array() ), function ( $v ) { return '' !== $v; } ), pcs_about_defaults() );
	echo '<div class="wrap"><h1>About page</h1><p>Edit the words and photos of the About Us page. Clear a box and save to bring back the original text.</p><form method="post">';
	wp_nonce_field( 'pcs_about', 'pcs_about_nonce' );
	foreach ( pcs_about_fields() as $group => $fields ) {
		echo '<h2>' . esc_html( $group ) . '</h2><table class="form-table">';
		foreach ( $fields as $k => $f ) {
			$v = $cur[ $k ] ?? '';
			echo '<tr><th><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
			if ( 'textarea' === $f[1] ) {
				echo '<textarea class="large-text" rows="' . ( in_array( $k, array( 'values', 'steps', 'stats' ), true ) ? 7 : 4 ) . '" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '">' . esc_textarea( $v ) . '</textarea>';
			} elseif ( 'image' === $f[1] ) {
				echo '<input class="regular-text pcs-img" type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '"> <button type="button" class="button pcs-pick" data-target="' . esc_attr( $k ) . '">Choose image</button>';
				echo '<div style="margin-top:8px">' . ( $v ? '<img src="' . esc_url( $v ) . '" style="max-width:240px;height:auto">' : '' ) . '</div>';
			} else {
				echo '<input class="large-text" type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
			}
			if ( ! empty( $f[2] ) ) {
				echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	echo '<p><button class="button button-primary">Save About page</button></p></form></div>';
	?>
	<script>
	jQuery(function($){
		$('.pcs-pick').on('click',function(e){
			e.preventDefault();var t=$('#'+$(this).data('target'));
			var f=wp.media({title:'Choose image',multiple:false,library:{type:'image'}});
			f.on('select',function(){t.val(f.state().get('selection').first().toJSON().url);});
			f.open();
		});
	});
	</script>
	<?php
}

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

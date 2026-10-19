<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', function () {
	if ( isset( $_POST['pcs_seed'], $_POST['pcs_seed_nonce'] ) && wp_verify_nonce( $_POST['pcs_seed_nonce'], 'pcs_seed' ) && current_user_can( 'manage_options' ) ) {
		$r = pcs_seed();
		add_action( 'admin_notices', function () use ( $r ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $r ) . '</p></div>';
		} );
	}
} );

// Lets the MCP plugin trigger the import.
add_filter( 'pcmcp_allowed_actions', function ( $a ) {
	$a['pcs_seed'] = 'pcs_seed';
	return $a;
} );

function pcs_seed_data() {
	$b = 'https://houseofcutts.com/wp-content/uploads/2026/';
	$c = array( '500,1000', 'Chicken' );
	return array(
		'Chicken' => array(
			array( 'Chicken Curry Cut', '08/chicken_curry_cut_large.jpeg', '500,1000,2000' ),
			array( 'Chicken Biryani Cut', '08/Untitled-design-compressed.jpg', '500,1000,2000' ),
			array( 'Chicken Breast Boneless', '08/fb37cd82-0e7a-476f-87da-52416d04ffb9-compressed.jpg', '250,500,1000' ),
			array( 'Chicken Boneless Cubes', '09/ChatGPT-Image-Sep-12-2026-10_09_08-PM-compressed.jpg', '250,500,1000' ),
			array( 'Chicken Leg and Thigh', '08/house_of_cutts_chicken_leg_1465x1073-compressed.jpg', '500,1000,2000' ),
			array( 'Chicken Drumstick', '08/24ba762d-8680-47d6-a1b5-03733220c977-compressed.jpg', '500,1000' ),
			array( 'Chicken Wings', '08/a42cd1b8-6336-4950-8e4d-0fd414ba1e02-compressed.jpg', '500,1000' ),
			array( 'Chicken Lollipop', '08/ChatGPT-Image-Aug-12-2026-12_14_17-AM-compressed.jpg', '250,500,1000' ),
			array( 'Chicken 65 Cut', '08/house_of_cutts_chicken_1465x1073-compressed-1.jpg', '250,500,1000' ),
			array( 'Whole Chicken (Tandoori)', '09/ChatGPT-Image-Sep-12-2026-09_57_44-PM-compressed.jpg', '1000,1500,2000' ),
		),
		'Mutton'  => array(
			array( 'Mutton Curry Cut', '08/ChatGPT-Image-Aug-12-2026-12_26_41-AM-compressed.jpg', '500,1000' ),
			array( 'Mutton Biryani Cut', '08/ChatGPT-Image-Aug-12-2026-12_50_33-AM-compressed.jpg', '500,1000' ),
			array( 'Mutton Boneless', '08/ChatGPT-Image-Aug-22-2026-01_53_12-PM-compressed.jpg', '250,500,1000' ),
			array( 'Mutton Mince (Kheema)', '08/ChatGPT-Image-Aug-12-2026-12_41_14-AM-compressed.jpg', '250,500,1000' ),
			array( 'Mutton Suvarotti (Spleen)', '08/house_of_cutts_mutton_spleen_black_plate_1000x681-compressed.jpg', '250,500' ),
			array( 'Mutton Leg (Paya)', '08/ChatGPT-Image-Aug-12-2026-12_47_04-AM-compressed.jpg', '500,1000' ),
			array( 'Mutton Brain', '08/1695289091600.jpg', '250,500' ),
			array( 'Mutton Liver', '08/ChatGPT-Image-Aug-12-2026-12_56_12-AM-compressed.jpg', '250,500' ),
		),
	);
}

function pcs_sideload( $url, $post_id ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_sideload_image( $url, $post_id, null, 'id' );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

function pcs_seed() {
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 );
	}
	$base    = 'https://houseofcutts.com/wp-content/uploads/2026/';
	$made    = 0;
	$skipped = 0;
	$desc    = array( 'Chicken' => 'Fresh chilled chicken, cleaned and cut by our trained meat professionals and packed hygienically.', 'Mutton' => 'Fresh, tender mutton, carefully cut and hygienically packed.' );
	$order   = 0;
	foreach ( pcs_seed_data() as $cat => $items ) {
		$term = term_exists( $cat, 'pcs_cat' ) ?: wp_insert_term( $cat, 'pcs_cat' );
		$tid  = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		foreach ( $items as $it ) {
			$order++;
			if ( get_posts( array( 'post_type' => 'pcs_product', 'title' => $it[0], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) ) ) {
				$skipped++;
				continue;
			}
			$pid = wp_insert_post( array( 'post_type' => 'pcs_product', 'post_status' => 'publish', 'post_title' => $it[0], 'post_content' => $desc[ $cat ] ) );
			if ( is_wp_error( $pid ) ) {
				continue;
			}
			wp_set_object_terms( $pid, array( $tid ), 'pcs_cat' );
			update_post_meta( $pid, 'pcs_weights', $it[2] );
			update_post_meta( $pid, 'pcs_price', 0 );
			update_post_meta( $pid, 'pcs_stock', 'in' );
			update_post_meta( $pid, 'pcs_order', $order );
			update_post_meta( $pid, 'pcs_featured', $order <= 4 || ( $order > 10 && $order <= 12 ) ? '1' : '' );
			$img = pcs_sideload( $base . $it[1], $pid );
			if ( $img ) {
				set_post_thumbnail( $pid, $img );
			}
			$made++;
		}
	}

	// Home banners
	if ( ! get_option( 'pcs_settings' ) || '' === pcs_opt( 'banners', '' ) ) {
		$lines = array();
		foreach ( array( '09/336b3402-0b53-4004-bff1-188db26b9cae-compressed.webp|Fresh Chicken|Cleaned, cut your way|/shop/chicken/', '09/ChatGPT-Image-Sep-12-2026-09_04_17-PM-compressed.webp|Fresh Mutton|Tender, carefully cut|/shop/mutton/' ) as $b ) {
			list( $file, $t, $s, $l ) = explode( '|', $b );
			$aid = pcs_sideload( $base . $file, 0 );
			if ( $aid ) {
				$lines[] = wp_get_attachment_url( $aid ) . "|$t|$s|$l";
			}
		}
		$cur            = wp_parse_args( get_option( 'pcs_settings', array() ), pcs_defaults() );
		$cur['banners'] = implode( "\n", $lines );
		update_option( 'pcs_settings', $cur );
	}

	// Pages
	$pages = array(
		'about-us'        => array( 'About Us', "<h2>Our Vision</h2><p>To become India’s most trusted chilled meat brand, changing the way families buy meat by making freshness, hygiene, quality and transparency a standard, not a compromise.</p><h2>Our Mission</h2><p>At House of Cutts, our mission is to make quality meat a simple and worry-free choice for every family. Fresh meat. Honest service. Quality you can trust.</p>" ),
		'quality-hygiene' => array( 'Quality & Hygiene', "<p>Meat is only as good as the way it is handled. At House of Cutts, cleanliness is not a one-time effort. It is the routine we follow before the shutter opens and after the last order goes out.</p><h2>Our chicken processing</h2><ol><li><strong>Carefully sourced.</strong> We source quality chicken from trusted suppliers and check it before processing.</li><li><strong>Hygienically processed.</strong> The chicken is cleaned and processed in a controlled, hygienic environment.</li><li><strong>Freshly chilled.</strong> The processed chicken is rapidly chilled to help maintain its freshness, texture and quality.</li><li><strong>Carefully cut.</strong> Our trained meat professionals prepare the cuts you need.</li><li><strong>Weighed and packed.</strong> Every portion is accurately weighed and hygienically packed.</li><li><strong>Kept chilled until you get it.</strong> The chicken remains under controlled chilled conditions through storage and delivery.</li></ol><h2>Come and see the counter yourself</h2><p>You are welcome to look at how the meat is stored, cut and packed before you order. Walk in any day, or call ahead and we will keep your cut ready.</p>" ),
		'contact-us'      => array( 'Contact', '[pcs_contact]' ),
		'checkout'        => array( 'Cart & Checkout', '[pcs_checkout]' ),
		'account'         => array( 'My Account', '[pcs_account]' ),
	);
	foreach ( $pages as $slug => $p ) {
		$ex = get_page_by_path( $slug );
		if ( $ex ) {
			wp_update_post( array( 'ID' => $ex->ID, 'post_status' => 'publish', 'post_content' => $p[1] ) );
		} else {
			wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug, 'post_content' => $p[1] ) );
		}
	}
	flush_rewrite_rules();
	return "Imported $made products ($skipped already existed) and set up pages.";
}

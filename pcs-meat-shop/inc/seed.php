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

// Lets the MCP plugin trigger the setup.
add_filter( 'pcmcp_allowed_actions', function ( $a ) {
	$a['pcs_seed'] = 'pcs_seed';
	return $a;
} );

function pcs_seed_data() {
	return array(
		'Chicken' => array(
			array( 'Chicken Curry Cut', '08/chicken_curry_cut_large.jpeg', '500,1000,2000', 240 ),
			array( 'Chicken Biryani Cut', '08/Untitled-design-compressed.jpg', '500,1000,2000', 250 ),
			array( 'Chicken Breast Boneless', '08/fb37cd82-0e7a-476f-87da-52416d04ffb9-compressed.jpg', '250,500,1000', 380 ),
			array( 'Chicken Boneless Cubes', '09/ChatGPT-Image-Sep-12-2026-10_09_08-PM-compressed.jpg', '250,500,1000', 400 ),
			array( 'Chicken Leg and Thigh', '08/house_of_cutts_chicken_leg_1465x1073-compressed.jpg', '500,1000,2000', 280 ),
			array( 'Chicken Drumstick', '08/24ba762d-8680-47d6-a1b5-03733220c977-compressed.jpg', '500,1000', 290 ),
			array( 'Chicken Wings', '08/a42cd1b8-6336-4950-8e4d-0fd414ba1e02-compressed.jpg', '500,1000', 220 ),
			array( 'Chicken Lollipop', '08/ChatGPT-Image-Aug-12-2026-12_14_17-AM-compressed.jpg', '250,500,1000', 320 ),
			array( 'Chicken 65 Cut', '08/house_of_cutts_chicken_1465x1073-compressed-1.jpg', '250,500,1000', 330 ),
			array( 'Whole Chicken (Tandoori)', '09/ChatGPT-Image-Sep-12-2026-09_57_44-PM-compressed.jpg', '1000,1500,2000', 230 ),
		),
		'Mutton'  => array(
			array( 'Mutton Curry Cut', '08/ChatGPT-Image-Aug-12-2026-12_26_41-AM-compressed.jpg', '500,1000', 820 ),
			array( 'Mutton Biryani Cut', '08/ChatGPT-Image-Aug-12-2026-12_50_33-AM-compressed.jpg', '500,1000', 840 ),
			array( 'Mutton Boneless', '08/ChatGPT-Image-Aug-22-2026-01_53_12-PM-compressed.jpg', '250,500,1000', 980 ),
			array( 'Mutton Mince (Kheema)', '08/ChatGPT-Image-Aug-12-2026-12_41_14-AM-compressed.jpg', '250,500,1000', 880 ),
			array( 'Mutton Suvarotti (Spleen)', '08/house_of_cutts_mutton_spleen_black_plate_1000x681-compressed.jpg', '250,500', 600 ),
			array( 'Mutton Leg (Paya)', '08/ChatGPT-Image-Aug-12-2026-12_47_04-AM-compressed.jpg', '500,1000', 450 ),
			array( 'Mutton Brain', '08/1695289091600.jpg', '250,500', 700 ),
			array( 'Mutton Liver', '08/ChatGPT-Image-Aug-12-2026-12_56_12-AM-compressed.jpg', '250,500', 560 ),
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

function pcs_find_product( $title ) {
	$ids = get_posts( array( 'post_type' => 'product', 'title' => $title, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	return $ids ? (int) $ids[0] : 0;
}

function pcs_term( $name ) {
	$t = term_exists( $name, 'product_cat' );
	if ( ! $t ) {
		$t = wp_insert_term( $name, 'product_cat' );
	}
	return is_array( $t ) ? (int) $t['term_id'] : (int) $t;
}

function pcs_make_product( $title, $desc, $cat, $weights, $price, $thumb_id, $order, $cuts = '', $note = '', $in_stock = true ) {
	$p = new WC_Product_Simple();
	$p->set_name( $title );
	$p->set_description( $desc );
	$p->set_status( 'publish' );
	$p->set_regular_price( (string) $price );
	$p->set_catalog_visibility( 'visible' );
	$p->set_menu_order( $order );
	$p->set_manage_stock( false );
	$p->set_stock_status( $in_stock ? 'instock' : 'outofstock' );
	$p->set_sold_individually( false );
	$p->set_category_ids( array( pcs_term( $cat ) ) );
	if ( $thumb_id ) {
		$p->set_image_id( $thumb_id );
	}
	$p->update_meta_data( '_pcs_weights', $weights );
	$p->update_meta_data( '_pcs_cuts', $cuts );
	$p->update_meta_data( '_pcs_note', $note );
	return $p->save();
}

function pcs_seed() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 'WooCommerce is not active.';
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 );
	}

	/* 1. WooCommerce options for a weight-based, cash-on-delivery meat shop */
	foreach ( array(
		'woocommerce_currency' => 'INR', 'woocommerce_currency_pos' => 'left', 'woocommerce_price_num_decimals' => '0',
		'woocommerce_default_country' => 'IN:TN', 'woocommerce_ship_to_countries' => 'disabled', 'woocommerce_calc_taxes' => 'no',
		'woocommerce_enable_guest_checkout' => 'no', 'woocommerce_enable_signup_and_login_from_checkout' => 'no',
		'woocommerce_enable_myaccount_registration' => 'no', 'woocommerce_enable_checkout_login_reminder' => 'no',
		'woocommerce_cart_redirect_after_add' => 'no', 'woocommerce_manage_stock' => 'no', 'woocommerce_coming_soon' => 'no',
		'woocommerce_enable_reviews' => 'no', 'woocommerce_enable_coupons' => 'yes',
	) as $k => $v ) {
		update_option( $k, $v );
	}
	$cod            = (array) get_option( 'woocommerce_cod_settings', array() );
	$cod['enabled'] = 'yes';
	$cod['title']   = 'Cash on Delivery';
	update_option( 'woocommerce_cod_settings', $cod );

	/* 2. Pages */
	$content = array(
		'about-us'        => array( 'About Us', "<h2>Our Vision</h2><p>To become India’s most trusted chilled meat brand, changing the way families buy meat by making freshness, hygiene, quality and transparency a standard, not a compromise.</p><h2>Our Mission</h2><p>At House of Cutts, our mission is to make quality meat a simple and worry-free choice for every family. Fresh meat. Honest service. Quality you can trust.</p>" ),
		'quality-hygiene' => array( 'Quality & Hygiene', "<p>Meat is only as good as the way it is handled. At House of Cutts, cleanliness is not a one-time effort. It is the routine we follow before the shutter opens and after the last order goes out.</p><h2>Our chicken processing</h2><ol><li><strong>Carefully sourced.</strong> We source quality chicken from trusted suppliers and check it before processing.</li><li><strong>Hygienically processed.</strong> The chicken is cleaned and processed in a controlled, hygienic environment.</li><li><strong>Freshly chilled.</strong> The processed chicken is rapidly chilled to help maintain its freshness, texture and quality.</li><li><strong>Carefully cut.</strong> Our trained meat professionals prepare the cuts you need.</li><li><strong>Weighed and packed.</strong> Every portion is accurately weighed and hygienically packed.</li><li><strong>Kept chilled until you get it.</strong> The chicken remains under controlled chilled conditions through storage and delivery.</li></ol><h2>Come and see the counter yourself</h2><p>You are welcome to look at how the meat is stored, cut and packed before you order. Walk in any day, or call ahead and we will keep your cut ready.</p>" ),
		'contact-us'      => array( 'Contact', '[pcs_contact]' ),
		'cart'            => array( 'Cart', '[woocommerce_cart]' ),
		'checkout'        => array( 'Checkout', '[woocommerce_checkout]' ),
		'my-account'      => array( 'My Account', '[woocommerce_my_account]' ),
	);
	$ids = array();
	foreach ( $content as $slug => $p ) {
		$ex = get_page_by_path( $slug );
		if ( $ex ) {
			wp_update_post( array( 'ID' => $ex->ID, 'post_status' => 'publish', 'post_title' => $p[0], 'post_content' => $p[1] ) );
			$ids[ $slug ] = $ex->ID;
		} else {
			$ids[ $slug ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug, 'post_content' => $p[1] ) );
		}
	}
	foreach ( array( 'cart' => 'cart', 'checkout' => 'checkout', 'my-account' => 'myaccount' ) as $slug => $opt ) {
		update_option( 'woocommerce_' . $opt . '_page_id', $ids[ $slug ] );
	}
	// Remove duplicates created by WooCommerce itself and the old "account" page.
	foreach ( array( 'checkout-2', 'cart-2', 'my-account-2', 'account' ) as $dup ) {
		$d = get_page_by_path( $dup );
		if ( $d && ! in_array( $d->ID, $ids, true ) && $d->ID !== (int) get_option( 'woocommerce_shop_page_id' ) ) {
			wp_trash_post( $d->ID );
		}
	}

	/* 3. Products: bring over the first version's products, else create fresh ones */
	$made = 0;
	$skip = 0;
	$legacy = get_posts( array( 'post_type' => 'pcs_product', 'post_status' => 'any', 'numberposts' => 100, 'orderby' => 'ID', 'order' => 'ASC' ) );
	if ( $legacy ) {
		foreach ( $legacy as $o ) {
			if ( pcs_find_product( $o->post_title ) ) {
				$skip++;
				continue;
			}
			$t   = wp_get_object_terms( $o->ID, 'pcs_cat' );
			$cat = $t && ! is_wp_error( $t ) ? $t[0]->name : 'Chicken';
			pcs_make_product(
				$o->post_title, $o->post_content, $cat, get_post_meta( $o->ID, 'pcs_weights', true ) ?: '500,1000', get_post_meta( $o->ID, 'pcs_price', true ),
				(int) get_post_thumbnail_id( $o->ID ), (int) get_post_meta( $o->ID, 'pcs_order', true ), (string) get_post_meta( $o->ID, 'pcs_cuts', true ),
				(string) get_post_meta( $o->ID, 'pcs_note', true ), 'out' !== get_post_meta( $o->ID, 'pcs_stock', true )
			);
			$made++;
		}
	} else {
		$base  = 'https://houseofcutts.com/wp-content/uploads/2026/';
		$order = 0;
		$desc  = array( 'Chicken' => 'Fresh chilled chicken, cleaned and cut by our trained meat professionals and packed hygienically.', 'Mutton' => 'Fresh, tender mutton, carefully cut and hygienically packed.' );
		foreach ( pcs_seed_data() as $cat => $items ) {
			foreach ( $items as $it ) {
				$order++;
				if ( pcs_find_product( $it[0] ) ) {
					$skip++;
					continue;
				}
				pcs_make_product( $it[0], $desc[ $cat ], $cat, $it[2], $it[3], pcs_sideload( $base . $it[1], 0 ), $order );
				$made++;
			}
		}
	}

	/* 4. Coupon + home page settings */
	if ( ! wc_get_coupon_id_by_code( 'FRESH50' ) ) {
		$c = new WC_Coupon();
		$c->set_code( 'FRESH50' );
		$c->set_discount_type( 'fixed_cart' );
		$c->set_amount( 50 );
		$c->set_minimum_amount( 300 );
		$c->save();
	}
	$cur = wp_parse_args( get_option( 'pcs_settings', array() ), pcs_defaults() );
	if ( empty( $cur['offer'] ) ) {
		$cur['offer'] = 'Use code FRESH50 and get ₹50 off on orders above ₹300.';
	}
	if ( ! empty( $cur['banners'] ) ) {
		$cur['banners'] = str_replace( '/shop/', '/product-category/', $cur['banners'] );
	}
	unset( $cur['coupons'], $cur['cod_enabled'] );
	update_option( 'pcs_settings', $cur );

	update_option( 'woocommerce_permalinks', array( 'product_base' => '/product', 'category_base' => 'product-category', 'tag_base' => '', 'attribute_base' => '', 'use_verbose_page_rules' => false ) );
	flush_rewrite_rules();
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
	return "Setup done. Created $made products ($skip already existed).";
}

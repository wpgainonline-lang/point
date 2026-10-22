<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pcs_defaults() {
	return array(
		'store_name'    => 'House of Cutts',
		'logo'          => content_url( '/uploads/2026/07/Gordonson.png' ),
		'phone'         => '+91 877 814 3927',
		'whatsapp'      => '918778143927',
		'email'         => 'info@houseofcutts.com',
		'address'       => "No 17, Old Mahabalipuram Road, Padur, Kelambakkam\nChennai - 603103",
		'timings'       => "Monday - Saturday: 7:00 AM - 9:00 PM\nSunday: 6:30 AM - 9:00 PM",
		'announce'      => 'Fresh chilled chicken & mutton, delivered in Chennai',
		'city'          => 'Chennai',
		'delivery_fee'  => 40,
		'free_above'    => 500,
		'min_order'     => 0,
		'driver_max_active' => 1,
		'pincodes'      => "603103|Kelambakkam\n603202|Siruseri\n600130|Navalur\n600119|Sholinganallur\n600097|Thoraipakkam",
		'slots'         => "7:00 AM - 9:00 AM\n9:00 AM - 12:00 PM\n12:00 PM - 3:00 PM\n3:00 PM - 6:00 PM\n6:00 PM - 8:30 PM",
		'lead_hours'    => 2,
		'otp_provider'  => 'email',
		'otp_key'       => '',
		'otp_template'  => '',
		'banners'       => '',
		'usps'          => "Fresh & chilled|Never frozen, kept cold from cut to doorstep\nHygienic cuts|Cleaned, cut and packed in a hygienic counter\nCut your way|Curry, biryani, boneless and more\nChennai delivery|Pick a slot that suits you",
		'offer'         => '',
		'pdp_badges'    => "https://primecutss.com/wp-content/uploads/2026/10/pdp-fresh.webp|Daily Fresh Processing\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-hygiene.webp|Hygienic Processing\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-guarantee.webp|Freshness Guaranteed\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-sameday.webp|Same Day Delivery",
		'pdp_policies'  => "https://primecutss.com/wp-content/uploads/2026/10/pdp-nonreturn.png|Non-Returnable Product\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-freeship.png|Free Delivery on Bigger Orders\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-shipped.png|Same Day Delivery*\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-packed.png|Hygienically Packed\nhttps://primecutss.com/wp-content/uploads/2026/10/pdp-trusted.png|Sourced from Trusted Suppliers",
		'pdp_tip_icon'  => 'https://primecutss.com/wp-content/uploads/2026/10/pdp-tip.webp',
		'pdp_tips'      => "Storage Tips|Keep the pack in the fridge (0-4°C) as soon as it arrives and use it within 24 hours. For longer storage, freeze it at -18°C.\nThawing Tips|Thaw in the fridge overnight or in a sealed pack under cold water. Do not thaw at room temperature and do not refreeze thawed meat.\nPreparation Tips|Rinse lightly, pat dry and marinate for at least 30 minutes. Use separate boards and knives for raw meat and vegetables.\nCooking Tips|Cook chicken until the juices run clear and mutton until tender. Slow cooking on a low flame gives the best flavour for curry cuts.\nHygiene Tips|Wash your hands and tools after handling raw meat and never keep cooked and raw meat together.",
		'pdp_shelf'     => "Shelf life: 12-24 hours refrigerated at 0-4°C\nStorage: Keep refrigerated immediately in the sealed pack\nBest consumed within: 12 hours of delivery for best freshness\nFreezing: Freeze at -18°C for up to 2 months",
		'deal_title'    => 'Deal Of The Week',
		'deal_ids'      => '',
		'deal_end'      => '',
		'faqs'          => "Where do you deliver?|We deliver in {city}. Enter your pincode on the home page or at checkout to check your area. More cities are coming soon.\nHow do I pay?|Cash on delivery for now. Online payment will be added soon.\nWhat is the delivery charge?|Delivery is {delivery_fee}{free_line}.\nHow do I choose a delivery time?|At checkout, pick a day and a time slot that suits you. Slots that are too close to the current time are not shown.\nCan I choose how the meat is cut?|Yes. Pick the cut you want, such as curry cut, biryani cut or boneless. For special requests, add a note for the butcher at checkout.\nHow is the meat kept fresh?|Our meat is handled and kept under chilled conditions, from cutting and packing to delivery, to preserve its freshness, texture and flavour.\nWhere can I see my orders?|Log in and open My Orders from the menu to see your orders and their status.\nCan I visit the shop?|Yes, you are welcome. We are at {address}. Call {phone} if you need a large quantity.",
		'nav_icon_chicken' => '',
		'nav_icon_mutton'  => '',
		'promos'        => '',
		'qh_img_chicken' => '',
		'qh_img_mutton'  => '',
		'hero_img_chicken' => '',
		'hero_img_mutton'  => '',
		'testimonials'  => "Arun Kumar|Chennai|Really impressed with the quality from House of Cutts. The meat was fresh and juicy, and the cuts were exactly as requested. Packaging was neat and secure, which made the whole order feel very hygienic and premium. Definitely ordering again.\nSuresh Reddy|Chennai|Excellent meat quality. The cuts were fresh, tender and nicely prepared. I really liked the packaging because everything arrived clean and properly packed. The pieces were also cut to the right size for cooking. Very good experience.\nPriya Sharma|Chennai|Loved the chicken quality. The pieces were fresh, juicy and properly cleaned. The boneless cuts were evenly prepared, which made cooking much easier. Packaging was also very neat with no unnecessary mess. House of Cutts is now my go-to.\nMohammed Imran|Chennai|Very happy with the cutting quality. The curry pieces were perfectly sized and the meat was fresh and juicy. Everything was packed properly and looked hygienic. The staff also understood my cutting requirement perfectly. Great service overall.\nRahul Verma|Chennai|Fresh meat, clean cutting and really good packaging. The pieces looked fresh and had a great texture. I also liked that there was very little unnecessary waste. Everything was prepared with care. Highly recommended for anyone looking for quality cuts.\nKarthik Raj|Chennai|One of the better meat shopping experiences I have had in Chennai. The cuts were fresh, juicy and prepared exactly as requested. The quantity was properly packed and the packaging looked clean and professional. Will definitely order from House of Cutts again.",
	);
}

function pcs_opt( $key, $default = '' ) {
	static $all = null;
	if ( null === $all ) {
		$all = wp_parse_args( get_option( 'pcs_settings', array() ), pcs_defaults() );
	}
	return isset( $all[ $key ] ) && '' !== $all[ $key ] ? $all[ $key ] : $default;
}

function pcs_lines( $key ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) pcs_opt( $key, '' ) ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l ) {
			$out[] = array_map( 'trim', explode( '|', $l ) );
		}
	}
	return $out;
}

/** Fills {city}, {phone} and friends in FAQ text from the settings. */
function pcs_faq_text( $t ) {
	$free = (float) pcs_opt( 'free_above', 0 );
	$min  = (float) pcs_opt( 'min_order', 0 );
	return strtr( (string) $t, array(
		'{city}'         => pcs_opt( 'city' ),
		'{phone}'        => pcs_opt( 'phone' ),
		'{address}'      => str_replace( "\n", ', ', pcs_opt( 'address' ) ),
		'{delivery_fee}' => pcs_money( pcs_opt( 'delivery_fee', 0 ) ),
		'{free_line}'    => ( $free > 0 ? ', and free above ' . pcs_money( $free ) : '' ) . ( $min > 0 ? '. Minimum order is ' . pcs_money( $min ) : '' ),
		'{min_order}'    => pcs_money( $min ),
	) );
}

function pcs_money( $n ) {
	return '₹' . number_format_i18n( (float) $n, 0 );
}

function pcs_page_url( $slug ) {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		if ( 'checkout' === $slug ) {
			return wc_get_checkout_url();
		}
		if ( 'account' === $slug ) {
			return wc_get_page_permalink( 'myaccount' );
		}
	}
	$p = get_page_by_path( $slug );
	return $p ? get_permalink( $p ) : home_url( '/' . $slug . '/' );
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Meat Shop', 'Meat Shop', 'manage_options', 'pcs-settings', 'pcs_settings_page', 'dashicons-store', 26 );
} );

function pcs_fields() {
	// key => [label, type, help]
	return array(
		'Store'         => array(
			'store_name' => array( 'Store name', 'text' ),
			'logo'       => array( 'Logo image URL', 'text', 'Upload the logo in Media, then paste its URL here.' ),
			'phone'      => array( 'Phone', 'text' ),
			'whatsapp'   => array( 'WhatsApp number (with 91, no +)', 'text' ),
			'email'      => array( 'Order notification email', 'text' ),
			'address'    => array( 'Address', 'textarea' ),
			'timings'    => array( 'Timings', 'textarea' ),
			'announce'   => array( 'Top bar message', 'text' ),
			'city'       => array( 'City served', 'text', 'Shown to customers. Change when you add more cities.' ),
			'nav_icon_chicken' => array( 'Menu icon: Chicken', 'image', 'Small picture shown above Chicken in the top menu. Leave empty for the built-in icon.' ),
			'nav_icon_mutton'  => array( 'Menu icon: Mutton', 'image', 'Small picture shown above Mutton in the top menu.' ),
		),
		'Delivery'      => array(
			'delivery_fee' => array( 'Delivery charge (₹)', 'number' ),
			'free_above'   => array( 'Free delivery above (₹, 0 = never)', 'number' ),
			'min_order'    => array( 'Minimum order (₹)', 'number' ),
			'driver_max_active' => array( 'Orders at a time per delivery boy', 'number', 'A delivery boy who already has this many orders is skipped; the next free one gets the order. If everyone is busy the order waits and is assigned as soon as someone finishes.' ),
			'lead_hours'   => array( 'Hours before a slot starts that it closes', 'number' ),
			'pincodes'     => array( 'Serviceable pincodes', 'textarea', 'One per line: 600001|Area name. Add more pincodes/cities here any time.' ),
			'slots'        => array( 'Delivery slots', 'textarea', 'One per line, e.g. 7:00 AM - 9:00 AM' ),
		),
		'Payment & login' => array(
			'otp_provider' => array( 'OTP provider', 'select', array( 'email' => 'Email OTP (free)', '2factor' => '2Factor.in SMS', 'msg91' => 'MSG91 SMS', 'test' => 'Test mode (code shown below, never for live)' ) ),
			'otp_key'      => array( 'OTP API key / authkey', 'text', 'For 2Factor / MSG91 only.' ),
			'otp_template' => array( 'OTP template id', 'text', 'MSG91 template id, or 2Factor template name (optional).' ),
		),
		'Home page'     => array(
			'hero_img_chicken' => array( 'Hero photo: Chicken', 'image', 'Shown in the top section of the home page. Leave empty to use a product photo.' ),
			'hero_img_mutton'  => array( 'Hero photo: Mutton', 'image', 'Leave empty to use a product photo.' ),
			'qh_img_chicken' => array( 'Quality section photo: Chicken', 'image', 'Shown beside the Quality & hygiene text on the home page. Leave empty to use a product photo.' ),
			'qh_img_mutton'  => array( 'Quality section photo: Mutton', 'image' ),
			'promos' => array( 'Promo strip slider', 'textarea', 'Shown between the Chicken and Mutton sections. One per line: Image URL|Description|Link (e.g. /product-category/mutton/). Upload images in Media first.' ),
			'banners' => array( 'Banners', 'textarea', 'One per line: image URL|Title|Subtitle|Link' ),
			'usps'    => array( 'Highlights strip', 'textarea', 'One per line: Title|Text' ),
			'faqs' => array( 'FAQ (questions & answers)', 'textarea', 'One per line: Question|Answer. You can use {city} {delivery_fee} {free_line} {min_order} {address} {phone}.' ),
			'testimonials' => array( 'Customer reviews', 'textarea', 'One per line: Name|City|Review text. Shown as a carousel on the home page.' ),
		),
		'Product page' => array(
			'pdp_badges'   => array( 'Product page: trust icons', 'textarea', 'Shown under the price on every product. One per line: Icon image URL|Text.' ),
			'pdp_policies' => array( 'Product page: policy icons', 'textarea', 'Row of icons below the product details. One per line: Icon image URL|Text.' ),
			'pdp_tip_icon' => array( 'Product page: tips icon (image URL)', 'text' ),
			'pdp_tips'     => array( 'Product page: tips (accordion)', 'textarea', 'Same on every product. One per line: Title|Text.' ),
			'pdp_shelf'    => array( 'Product page: default shelf life', 'textarea', 'One point per line, Label: text. A product can override it on its own edit screen.' ),
		),
		'Deal of the week' => array(
			'deal_title' => array( 'Deal heading', 'text', 'Heading of the countdown deal section on the home page.' ),
			'deal_ids'   => array( 'Deal products', 'text', 'Product IDs separated by commas, e.g. 12,15,18,20 (4 look best). Leave empty to show the first 4 products. Put the sale price on each product so the % OFF tag shows.' ),
			'deal_end'   => array( 'Deal ends on', 'text', 'Date and time like 2026-11-01 23:59. Leave empty and the deal restarts every Monday and ends on Sunday night. The section hides when the date has passed.' ),
		),
		'Offers'        => array(
			'offer' => array( 'Offer bar text', 'text', 'Shown as a red bar on the home page. Create the coupon itself under Marketing → Coupons. Leave empty to hide.' ),
		),
	);
}

function pcs_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_media();
	if ( isset( $_POST['pcs_nonce'] ) && wp_verify_nonce( $_POST['pcs_nonce'], 'pcs_save' ) ) {
		$new = array();
		foreach ( pcs_fields() as $group ) {
			foreach ( $group as $k => $f ) {
				$v = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
				if ( 'checkbox' === $f[1] ) {
					$v = empty( $_POST[ $k ] ) ? 0 : 1;
				} elseif ( 'image' === $f[1] ) {
					$v = esc_url_raw( $v );
				} elseif ( 'number' === $f[1] ) {
					$v = max( 0, (float) $v );
				} elseif ( 'textarea' === $f[1] ) {
					$v = sanitize_textarea_field( $v );
				} else {
					$v = sanitize_text_field( $v );
				}
				$new[ $k ] = $v;
			}
		}
		update_option( 'pcs_settings', $new );
		echo '<div class="notice notice-success"><p>Saved.</p></div>';
		echo '<script>location.href=location.href</script>';
	}
	$cur = wp_parse_args( get_option( 'pcs_settings', array() ), pcs_defaults() );
	echo '<div class="wrap"><h1>Meat shop settings</h1><form method="post">';
	wp_nonce_field( 'pcs_save', 'pcs_nonce' );
	foreach ( pcs_fields() as $group => $fields ) {
		echo '<h2>' . esc_html( $group ) . '</h2><table class="form-table">';
		foreach ( $fields as $k => $f ) {
			$v = $cur[ $k ] ?? '';
			echo '<tr><th><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
			if ( 'textarea' === $f[1] ) {
				echo '<textarea class="large-text" rows="5" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '">' . esc_textarea( $v ) . '</textarea>';
			} elseif ( 'checkbox' === $f[1] ) {
				echo '<input type="checkbox" name="' . esc_attr( $k ) . '" value="1" ' . checked( $v, 1, false ) . '>';
			} elseif ( 'image' === $f[1] ) {
				echo '<input class="regular-text" type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '"> <button type="button" class="button pcs-pick" data-target="' . esc_attr( $k ) . '">Choose image</button>';
				echo '<div style="margin-top:8px">' . ( $v ? '<img src="' . esc_url( $v ) . '" style="max-width:160px;height:auto">' : '' ) . '</div>';
			} elseif ( 'select' === $f[1] ) {
				echo '<select name="' . esc_attr( $k ) . '">';
				foreach ( $f[2] as $ov => $ol ) {
					echo '<option value="' . esc_attr( $ov ) . '" ' . selected( $v, $ov, false ) . '>' . esc_html( $ol ) . '</option>';
				}
				echo '</select>';
			} else {
				echo '<input class="regular-text" type="' . ( 'number' === $f[1] ? 'number' : 'text' ) . '" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
			}
			if ( ! empty( $f[2] ) && is_string( $f[2] ) ) {
				echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	if ( 'test' === ( $cur['otp_provider'] ?? '' ) ) {
		echo '<p><strong>Last test OTP:</strong> <code>' . esc_html( get_option( 'pcs_test_otp', '-' ) ) . '</code></p>';
	}
	echo '<p><button class="button button-primary">Save settings</button></p></form>';
	echo '<script>jQuery(function($){$(".pcs-pick").on("click",function(e){e.preventDefault();var t=$("#"+$(this).data("target"));var f=wp.media({title:"Choose image",multiple:false,library:{type:"image"}});f.on("select",function(){t.val(f.state().get("selection").first().toJSON().url);});f.open();});});</script>';
	echo '<h2>Demo data</h2><p>Set up WooCommerce for the meat shop and bring over the products (safe to run again).</p>';
	echo '<form method="post">' . wp_nonce_field( 'pcs_seed', 'pcs_seed_nonce', true, false ) . '<button class="button" name="pcs_seed" value="1">Run setup &amp; import products</button></form></div>';
}

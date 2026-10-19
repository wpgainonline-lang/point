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
		'pincodes'      => "603103|Kelambakkam\n603202|Siruseri\n600130|Navalur\n600119|Sholinganallur\n600097|Thoraipakkam",
		'slots'         => "7:00 AM - 9:00 AM\n9:00 AM - 12:00 PM\n12:00 PM - 3:00 PM\n3:00 PM - 6:00 PM\n6:00 PM - 8:30 PM",
		'lead_hours'    => 2,
		'otp_provider'  => 'email',
		'otp_key'       => '',
		'otp_template'  => '',
		'banners'       => '',
		'usps'          => "Fresh & chilled|Never frozen, kept cold from cut to doorstep\nHygienic cuts|Cleaned, cut and packed in a hygienic counter\nCut your way|Curry, biryani, boneless and more\nChennai delivery|Pick a slot that suits you",
		'offer'         => '',
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
		),
		'Delivery'      => array(
			'delivery_fee' => array( 'Delivery charge (₹)', 'number' ),
			'free_above'   => array( 'Free delivery above (₹, 0 = never)', 'number' ),
			'min_order'    => array( 'Minimum order (₹)', 'number' ),
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
			'banners' => array( 'Banners', 'textarea', 'One per line: image URL|Title|Subtitle|Link' ),
			'usps'    => array( 'Highlights strip', 'textarea', 'One per line: Title|Text' ),
			'testimonials' => array( 'Customer reviews', 'textarea', 'One per line: Name|City|Review text. Shown as a carousel on the home page.' ),
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
	if ( isset( $_POST['pcs_nonce'] ) && wp_verify_nonce( $_POST['pcs_nonce'], 'pcs_save' ) ) {
		$new = array();
		foreach ( pcs_fields() as $group ) {
			foreach ( $group as $k => $f ) {
				$v = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
				if ( 'checkbox' === $f[1] ) {
					$v = empty( $_POST[ $k ] ) ? 0 : 1;
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
	echo '<h2>Demo data</h2><p>Set up WooCommerce for the meat shop and bring over the products (safe to run again).</p>';
	echo '<form method="post">' . wp_nonce_field( 'pcs_seed', 'pcs_seed_nonce', true, false ) . '<button class="button" name="pcs_seed" value="1">Run setup &amp; import products</button></form></div>';
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------- helpers (Woo price = ₹ per kg) ---------------- */

function pcs_weights( $id ) {
	$w = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_pcs_weights', true ) ) ) );
	return $w ? array_values( $w ) : array( 500, 1000 );
}

function pcs_cuts( $id ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, '_pcs_cuts', true ) ) ) ) );
}

function pcs_price_kg( $id ) {
	$p = wc_get_product( $id );
	return $p ? (float) $p->get_price() : 0;
}

function pcs_mrp_kg( $id ) {
	$p = wc_get_product( $id );
	return ( $p && $p->is_on_sale() ) ? (float) $p->get_regular_price() : 0;
}

function pcs_price_for( $id, $grams, $mrp = false ) {
	$kg = $mrp ? pcs_mrp_kg( $id ) : pcs_price_kg( $id );
	return (int) round( $kg * $grams / 1000 );
}

function pcs_in_stock( $id ) {
	$p = wc_get_product( $id );
	return $p && $p->is_purchasable() && $p->is_in_stock() && (float) $p->get_price() > 0;
}

function pcs_weight_label( $g ) {
	return $g >= 1000 && 0 === $g % 1000 ? ( $g / 1000 ) . ' kg' : ( $g > 1000 ? number_format( $g / 1000, 2 ) . ' kg' : $g . ' g' );
}

function pcs_card_data( $id ) {
	$w = pcs_weights( $id );
	return array(
		'id' => $id, 'name' => get_the_title( $id ), 'weights' => $w, 'cuts' => pcs_cuts( $id ),
		'prices' => array_map( function ( $g ) use ( $id ) { return pcs_price_for( $id, $g ); }, $w ),
		'inStock' => pcs_in_stock( $id ),
	);
}

/** Product card used on home, category and search pages. */
function pcs_card( $id ) {
	$d    = pcs_card_data( $id );
	$img  = get_the_post_thumbnail_url( $id, 'medium_large' );
	$note = get_post_meta( $id, '_pcs_note', true );
	$mrp  = pcs_mrp_kg( $id );
	ob_start();
	?>
	<article class="card" data-card='<?php echo esc_attr( wp_json_encode( $d ) ); ?>'>
		<a class="card-img" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $d['name'] ); ?>" loading="lazy" width="400" height="300"><?php endif; ?></a>
		<div class="card-body">
			<h3 class="card-title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( $d['name'] ); ?></a></h3>
			<?php if ( $note ) : ?><p class="muted"><?php echo esc_html( $note ); ?></p><?php endif; ?>
			<?php if ( $d['inStock'] ) : ?>
				<div class="row">
					<select class="sel-w" aria-label="Weight"><?php foreach ( $d['weights'] as $g ) : ?><option value="<?php echo (int) $g; ?>"><?php echo esc_html( pcs_weight_label( $g ) ); ?></option><?php endforeach; ?></select>
					<span class="price"><span class="p-now"><?php echo esc_html( pcs_money( $d['prices'][0] ) ); ?></span><?php if ( $mrp ) : ?> <s class="muted"><?php echo esc_html( pcs_money( pcs_price_for( $id, $d['weights'][0], true ) ) ); ?></s><?php endif; ?></span>
				</div>
				<button type="button" class="btn btn-block js-add">Add</button>
			<?php else : ?>
				<p class="muted"><?php echo pcs_price_kg( $id ) > 0 ? 'Out of stock' : 'Call to order: ' . esc_html( pcs_opt( 'phone' ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/* ---------------- product edit screen ---------------- */

add_action( 'woocommerce_product_options_pricing', function () {
	echo '<p class="form-field"><em>The price above is per kg. Customers pick a weight and pay price × weight.</em></p>';
	woocommerce_wp_text_input( array( 'id' => '_pcs_weights', 'label' => 'Weight options (grams)', 'description' => 'Comma separated, e.g. 250,500,1000', 'desc_tip' => false, 'placeholder' => '500,1000' ) );
	woocommerce_wp_text_input( array( 'id' => '_pcs_cuts', 'label' => 'Cut options', 'description' => 'Comma separated, e.g. Curry cut,Biryani cut. Leave empty if not needed.', 'desc_tip' => false ) );
	woocommerce_wp_text_input( array( 'id' => '_pcs_note', 'label' => 'Short note', 'description' => 'e.g. Serves 4-5', 'desc_tip' => false ) );
} );

add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	foreach ( array( '_pcs_weights', '_pcs_cuts', '_pcs_note' ) as $k ) {
		if ( isset( $_POST[ $k ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$product->update_meta_data( $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) ); // phpcs:ignore
		}
	}
} );

// Meat is sold by weight, so hide options that do not apply.
add_filter( 'woocommerce_product_data_tabs', function ( $tabs ) {
	unset( $tabs['shipping'], $tabs['linked_product'], $tabs['attribute'], $tabs['variations'] );
	return $tabs;
} );
add_filter( 'product_type_selector', function ( $t ) {
	return array( 'simple' => $t['simple'] ?? 'Simple product' );
} );

// Product ordering and page size on the storefront.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'product' ) || $q->is_tax( 'product_cat' ) || ( $q->is_search() && 'product' === $q->get( 'post_type' ) ) ) {
		$q->set( 'posts_per_page', 48 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
}, 99 );

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'pcs_register_types' );

function pcs_register_types() {
	register_post_type( 'pcs_product', array(
		'labels'       => array( 'name' => 'Products', 'singular_name' => 'Product', 'add_new_item' => 'Add product', 'edit_item' => 'Edit product' ),
		'public'       => true,
		'has_archive'  => 'products',
		'rewrite'      => array( 'slug' => 'product' ),
		'menu_icon'    => 'dashicons-carrot',
		'menu_position' => 27,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
		'show_in_rest' => false,
	) );
	register_taxonomy( 'pcs_cat', 'pcs_product', array(
		'labels'       => array( 'name' => 'Categories', 'singular_name' => 'Category' ),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => array( 'slug' => 'shop' ),
		'show_admin_column' => true,
	) );
	register_post_type( 'pcs_order', array(
		'labels'   => array( 'name' => 'Orders', 'singular_name' => 'Order', 'edit_item' => 'Order' ),
		'public'   => false,
		'show_ui'  => true,
		'menu_icon' => 'dashicons-clipboard',
		'menu_position' => 28,
		'supports' => array( '' ),
		'capability_type' => 'post',
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
}

/* ---------------- product helpers ---------------- */

function pcs_weights( $id ) {
	$w = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, 'pcs_weights', true ) ) ) );
	return $w ? array_values( $w ) : array( 500, 1000 );
}

function pcs_cuts( $id ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $id, 'pcs_cuts', true ) ) ) ) );
}

function pcs_price_kg( $id ) {
	return (float) get_post_meta( $id, 'pcs_price', true );
}

function pcs_mrp_kg( $id ) {
	$m = (float) get_post_meta( $id, 'pcs_mrp', true );
	return $m > pcs_price_kg( $id ) ? $m : 0;
}

function pcs_price_for( $id, $grams, $mrp = false ) {
	$kg = $mrp ? pcs_mrp_kg( $id ) : pcs_price_kg( $id );
	return (int) round( $kg * $grams / 1000 );
}

function pcs_in_stock( $id ) {
	return 'out' !== get_post_meta( $id, 'pcs_stock', true ) && pcs_price_kg( $id ) > 0;
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

/* ---------------- admin: product meta box ---------------- */

add_action( 'add_meta_boxes_pcs_product', function () {
	add_meta_box( 'pcs_prod', 'Price & options', 'pcs_product_box', 'pcs_product', 'normal', 'high' );
} );

function pcs_product_box( $post ) {
	wp_nonce_field( 'pcs_prod', 'pcs_prod_nonce' );
	$g = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, $k, true );
		return '' === $v ? $d : $v;
	};
	?>
	<table class="form-table">
		<tr><th>Price per kg (₹)</th><td><input type="number" step="1" min="0" name="pcs_price" value="<?php echo esc_attr( $g( 'pcs_price' ) ); ?>"> <span class="description">0 = not for sale yet</span></td></tr>
		<tr><th>Old price per kg (₹, optional)</th><td><input type="number" step="1" min="0" name="pcs_mrp" value="<?php echo esc_attr( $g( 'pcs_mrp' ) ); ?>"> <span class="description">Shown struck through when higher than price</span></td></tr>
		<tr><th>Weight options (grams)</th><td><input type="text" class="regular-text" name="pcs_weights" value="<?php echo esc_attr( $g( 'pcs_weights', '500,1000' ) ); ?>"> <span class="description">Comma separated, e.g. 250,500,1000</span></td></tr>
		<tr><th>Cut options</th><td><input type="text" class="regular-text" name="pcs_cuts" value="<?php echo esc_attr( $g( 'pcs_cuts' ) ); ?>"> <span class="description">Comma separated, e.g. Curry cut,Biryani cut. Leave empty if not applicable.</span></td></tr>
		<tr><th>Short note</th><td><input type="text" class="regular-text" name="pcs_note" value="<?php echo esc_attr( $g( 'pcs_note' ) ); ?>"> <span class="description">e.g. Serves 4-5, Gross weight</span></td></tr>
		<tr><th>Stock</th><td><select name="pcs_stock"><option value="in" <?php selected( $g( 'pcs_stock', 'in' ), 'in' ); ?>>In stock</option><option value="out" <?php selected( $g( 'pcs_stock' ), 'out' ); ?>>Out of stock</option></select></td></tr>
		<tr><th>Show on home page</th><td><label><input type="checkbox" name="pcs_featured" value="1" <?php checked( $g( 'pcs_featured' ), '1' ); ?>> Featured</label></td></tr>
		<tr><th>Sort order</th><td><input type="number" name="pcs_order" value="<?php echo esc_attr( $g( 'pcs_order', 0 ) ); ?>"></td></tr>
	</table>
	<?php
}

add_action( 'save_post_pcs_product', function ( $id ) {
	if ( ! isset( $_POST['pcs_prod_nonce'] ) || ! wp_verify_nonce( $_POST['pcs_prod_nonce'], 'pcs_prod' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	foreach ( array( 'pcs_price', 'pcs_mrp', 'pcs_order' ) as $k ) {
		update_post_meta( $id, $k, (float) ( $_POST[ $k ] ?? 0 ) );
	}
	foreach ( array( 'pcs_weights', 'pcs_cuts', 'pcs_note' ) as $k ) {
		update_post_meta( $id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
	}
	update_post_meta( $id, 'pcs_stock', 'out' === ( $_POST['pcs_stock'] ?? '' ) ? 'out' : 'in' );
	update_post_meta( $id, 'pcs_featured', empty( $_POST['pcs_featured'] ) ? '' : '1' );
} );

add_filter( 'manage_pcs_product_posts_columns', function ( $c ) {
	return array( 'cb' => $c['cb'], 'title' => 'Product', 'taxonomy-pcs_cat' => 'Category', 'pcs_price' => 'Price / kg', 'pcs_stock' => 'Stock', 'date' => $c['date'] );
} );
add_action( 'manage_pcs_product_posts_custom_column', function ( $col, $id ) {
	if ( 'pcs_price' === $col ) {
		$p = pcs_price_kg( $id );
		echo $p ? esc_html( pcs_money( $p ) ) : '<em>not set</em>';
	} elseif ( 'pcs_stock' === $col ) {
		echo 'out' === get_post_meta( $id, 'pcs_stock', true ) ? 'Out' : 'In';
	}
}, 10, 2 );

/* Quick edit for price & stock straight in the products list */
add_action( 'quick_edit_custom_box', function ( $col, $type ) {
	if ( 'pcs_product' !== $type || 'pcs_price' !== $col ) {
		return;
	}
	echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><label><span class="title">₹/kg</span><input type="number" name="pcs_price" value=""></label>';
	echo '<label><span class="title">Stock</span><select name="pcs_stock"><option value="in">In</option><option value="out">Out</option></select></label></div></fieldset>';
	wp_nonce_field( 'pcs_prod', 'pcs_prod_nonce' );
}, 10, 2 );

add_action( 'admin_footer-edit.php', function () {
	if ( 'pcs_product' !== get_current_screen()->post_type ) {
		return;
	}
	?>
	<script>
	jQuery(function($){var o=inlineEditPost.edit;inlineEditPost.edit=function(id){o.apply(this,arguments);if(typeof id==='object')id=this.getId(id);var r=$('#post-'+id),t=r.find('td.pcs_price').text().replace(/[^\d]/g,''),s=r.find('td.pcs_stock').text().trim()==='Out'?'out':'in',e=$('#edit-'+id);e.find('[name=pcs_price]').val(t);e.find('[name=pcs_stock]').val(s);};});
	</script>
	<?php
} );

/* Only the shop's own staff may see customers' products screen etc. Customers never reach wp-admin. */
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	if ( is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
} );
add_filter( 'show_admin_bar', function ( $s ) {
	return current_user_can( 'edit_posts' ) ? $s : false;
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'pcs_product' ) || $q->is_tax( 'pcs_cat' ) || ( $q->is_search() && 'pcs_product' === $q->get( 'post_type' ) ) ) {
		$q->set( 'posts_per_page', 48 );
		$q->set( 'meta_key', 'pcs_order' );
		$q->set( 'orderby', array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ) );
	}
} );

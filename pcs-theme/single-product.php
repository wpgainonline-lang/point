<?php get_header(); the_post(); $id = get_the_ID(); $d = pcs_card_data( $id ); $terms = get_the_terms( $id, 'product_cat' ); $mrp = pcs_mrp_kg( $id ); ?>
<?php
$gal = array();
if ( has_post_thumbnail() ) { $gal[] = get_post_thumbnail_id(); }
$wc_p = wc_get_product( $id );
if ( $wc_p ) { $gal = array_merge( $gal, $wc_p->get_gallery_image_ids() ); }
$dn = pcs_price_kg( $id );
$off = ( $mrp && $dn && $mrp > $dn ) ? (int) round( ( 1 - $dn / $mrp ) * 100 ) : 0;
$icon_row = function ( $key, $cls ) {
	$rows = pcs_lines( $key );
	if ( ! $rows ) { return; }
	echo '<ul class="' . esc_attr( $cls ) . '">';
	foreach ( $rows as $r ) {
		$ic = '';
		if ( ! empty( $r[0] ) ) {
			$ic = 0 === strpos( $r[0], 'http' ) ? '<img src="' . esc_url( $r[0] ) . '" alt="" width="56" height="56" loading="lazy">' : '<span class="pdp-ic">' . pcs_icon( $r[0] ) . '</span>'; // phpcs:ignore
		}
		echo '<li>' . $ic . '<span>' . esc_html( $r[1] ?? '' ) . '</span></li>';
	}
	echo '</ul>';
};
$shelf = trim( (string) get_post_meta( $id, '_pcs_shelf', true ) ) ?: (string) pcs_opt( 'pdp_shelf' );
$nutri = array();
foreach ( preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $id, '_pcs_nutri', true ) ) as $l ) {
	$l = array_map( 'trim', explode( '|', $l ) );
	if ( '' !== $l[0] && ! empty( $l[1] ) ) { $nutri[] = $l; }
}
$tel = preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) );
?>
<div class="wrap">
	<div class="page-head crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><?php if ( $terms && ! is_wp_error( $terms ) ) : ?> / <a href="<?php echo esc_url( get_term_link( $terms[0] ) ); ?>"><?php echo esc_html( $terms[0]->name ); ?></a><?php endif; ?> / <?php the_title(); ?></div>
	<div class="pdp" data-card='<?php echo esc_attr( wp_json_encode( $d ) ); ?>'>
		<div class="pdp-gal">
			<div class="pdp-img"><?php if ( $gal ) { echo wp_get_attachment_image( $gal[0], 'large', false, array( 'id' => 'pdp-main', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); } ?></div>
			<?php if ( count( $gal ) > 1 ) : ?><div class="pdp-thumbs"><?php foreach ( $gal as $i => $gid ) : ?><button type="button" class="<?php echo 0 === $i ? 'on' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $gid, 'large' ) ); ?>" aria-label="Photo <?php echo (int) $i + 1; ?>"><?php echo wp_get_attachment_image( $gid, 'thumbnail', false, array( 'loading' => 'lazy' ) ); ?></button><?php endforeach; ?></div><?php endif; ?>
		</div>
		<div class="pdp-info">
			<h1><?php the_title(); ?></h1>
			<?php if ( get_post_meta( $id, '_pcs_note', true ) ) : ?><p class="muted"><?php echo esc_html( get_post_meta( $id, '_pcs_note', true ) ); ?></p><?php endif; ?>
			<?php if ( $d['inStock'] ) : ?>
				<p class="pdp-price"><span class="big" id="pdp-price"><?php echo esc_html( pcs_money( $d['prices'][0] ) ); ?></span><?php if ( $mrp ) : ?> <s class="muted" id="pdp-mrp"><?php echo esc_html( pcs_money( pcs_price_for( $id, $d['weights'][0], true ) ) ); ?></s><?php endif; ?><?php if ( $off ) : ?> <span class="pdp-off"><?php echo (int) $off; ?>% OFF</span><?php endif; ?> <span class="muted"><?php echo esc_html( pcs_money( $dn ) ); ?> / kg</span></p>
			<?php endif; ?>
			<?php if ( $d['inStock'] ) : ?>
				<fieldset><legend>Weight</legend><?php foreach ( $d['weights'] as $i => $g ) : ?><label class="chip"><input type="radio" name="w" value="<?php echo (int) $g; ?>" <?php checked( 0, $i ); ?>><span><?php echo esc_html( pcs_weight_label( $g ) ); ?></span></label><?php endforeach; ?></fieldset>
				<?php if ( $d['cuts'] ) : ?><fieldset><legend>Cut</legend><?php foreach ( $d['cuts'] as $i => $c ) : ?><label class="chip"><input type="radio" name="cut" value="<?php echo esc_attr( $c ); ?>" <?php checked( 0, $i ); ?>><span><?php echo esc_html( $c ); ?></span></label><?php endforeach; ?></fieldset><?php endif; ?>
				<div class="pdp-buy"><div class="qty"><button type="button" class="qm" aria-label="Less">−</button><input type="number" id="pdp-q" min="1" max="20" value="1" aria-label="Quantity"><button type="button" class="qp" aria-label="More">+</button></div><button type="button" class="btn js-add pdp-add"><?php echo pcs_icon( 'cart' ); // phpcs:ignore ?> Add to cart</button></div>
			<?php else : ?>
				<p><strong><?php echo $dn > 0 ? 'Currently out of stock' : 'Call to order'; ?></strong></p>
			<?php endif; ?>
			<?php $icon_row( 'pdp_badges', 'pdp-badges' ); ?>
			<div class="pdp-assist"><strong>Any assistance?</strong><a class="as-call" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo pcs_icon( 'phone' ); // phpcs:ignore ?> Call</a><a class="as-wa" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', pcs_opt( 'whatsapp' ) ) ); ?>?text=<?php echo rawurlencode( 'Hi, I have a question about ' . get_the_title() ); ?>" target="_blank" rel="noopener">WhatsApp</a></div>
			<form class="pin-check"><input type="text" inputmode="numeric" maxlength="6" placeholder="Delivery pincode" aria-label="Pincode"><button class="btn btn-line" type="submit">Check delivery</button><span class="msg" role="status"></span></form>
			<?php $tips = pcs_lines( 'pdp_tips' ); if ( $tips ) : $tic = pcs_opt( 'pdp_tip_icon' ); ?>
			<div class="pdp-tips">
				<?php foreach ( $tips as $t ) : ?><details><summary><?php if ( $tic ) : ?><img src="<?php echo esc_url( $tic ); ?>" alt="" width="32" height="32" loading="lazy"><?php endif; ?><span><?php echo esc_html( $t[0] ); ?></span><i class="pm" aria-hidden="true"></i></summary><p><?php echo esc_html( $t[1] ?? '' ); ?></p></details><?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php $icon_row( 'pdp_policies', 'pdp-policy' ); ?>
	<div class="pdp-sections">
		<?php if ( trim( wp_strip_all_tags( get_the_content() ) ) ) : ?><section><h2>Description</h2><div class="content"><?php the_content(); ?></div></section><?php endif; ?>
		<?php if ( $shelf ) : ?><section><h2>Shelf life</h2><ul class="pdp-list"><?php foreach ( preg_split( '/\r\n|\r|\n/', $shelf ) as $l ) { if ( '' !== trim( $l ) ) { echo '<li>' . esc_html( trim( $l ) ) . '</li>'; } } ?></ul></section><?php endif; ?>
		<?php if ( $nutri ) : ?><section><h2>Nutrition <small>(per 100g)</small></h2><div class="pdp-nutri"><?php foreach ( $nutri as $n ) { echo '<div><b>' . esc_html( $n[1] ) . '</b><span>' . esc_html( $n[0] ) . '</span></div>'; } ?></div></section><?php endif; ?>
		<?php $faqs = pcs_lines( 'faqs' ); if ( $faqs ) : ?><section><h2>Frequently asked questions</h2><div class="faq-grid pdp-faq"><?php foreach ( $faqs as $f ) : ?><details class="faq-item"><summary><span><?php echo esc_html( pcs_faq_text( $f[0] ) ); ?></span><i class="pm" aria-hidden="true"></i></summary><p><?php echo esc_html( pcs_faq_text( $f[1] ?? '' ) ); ?></p></details><?php endforeach; ?></div></section><?php endif; ?>
	</div>
</div>
<?php
$rel_ids = array();
if ( $terms && ! is_wp_error( $terms ) ) {
	$rel_ids = get_posts( array( 'post_type' => 'product', 'numberposts' => 10, 'post__not_in' => array( $id ), 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => wp_list_pluck( $terms, 'term_id' ) ) ) ) );
}
if ( count( $rel_ids ) < 4 ) {
	$rel_ids = array_unique( array_merge( $rel_ids, get_posts( array( 'post_type' => 'product', 'numberposts' => 8, 'post__not_in' => array_merge( array( $id ), $rel_ids ), 'fields' => 'ids', 'orderby' => 'menu_order title', 'order' => 'ASC' ) ) ) );
}
if ( $rel_ids ) : ?>
<section class="section"><div class="wrap">
	<div class="section-head"><h2>More like this</h2><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">View all</a></div>
	<div class="car" data-car>
		<div class="car-track" tabindex="0"><?php foreach ( $rel_ids as $rid ) { echo pcs_card( $rid ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<button type="button" class="sl-btn sl-prev" aria-label="Previous">‹</button><button type="button" class="sl-btn sl-next" aria-label="Next">›</button>
	</div>
</div></section>
<?php endif; ?>
<?php get_footer(); ?>

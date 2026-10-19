<?php get_header(); the_post(); $id = get_the_ID(); $d = pcs_card_data( $id ); $terms = get_the_terms( $id, 'product_cat' ); $mrp = pcs_mrp_kg( $id ); ?>
<div class="wrap">
	<div class="page-head crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><?php if ( $terms && ! is_wp_error( $terms ) ) : ?> / <a href="<?php echo esc_url( get_term_link( $terms[0] ) ); ?>"><?php echo esc_html( $terms[0]->name ); ?></a><?php endif; ?> / <?php the_title(); ?></div>
	<div class="pdp" data-card='<?php echo esc_attr( wp_json_encode( $d ) ); ?>'>
		<div class="pdp-img"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); } ?></div>
		<div>
			<h1><?php the_title(); ?></h1>
			<?php if ( get_post_meta( $id, '_pcs_note', true ) ) : ?><p class="muted"><?php echo esc_html( get_post_meta( $id, '_pcs_note', true ) ); ?></p><?php endif; ?>
			<div class="content"><?php the_content(); ?></div>
			<?php if ( $d['inStock'] ) : ?>
				<p><span class="big" id="pdp-price"><?php echo esc_html( pcs_money( $d['prices'][0] ) ); ?></span><?php if ( $mrp ) : ?> <s class="muted"><?php echo esc_html( pcs_money( pcs_price_for( $id, $d['weights'][0], true ) ) ); ?></s><?php endif; ?> <span class="muted"><?php echo esc_html( pcs_money( pcs_price_kg( $id ) ) ); ?> / kg</span></p>
				<fieldset><legend>Weight</legend><?php foreach ( $d['weights'] as $i => $g ) : ?><label class="chip"><input type="radio" name="w" value="<?php echo (int) $g; ?>" <?php checked( 0, $i ); ?>><span><?php echo esc_html( pcs_weight_label( $g ) ); ?></span></label><?php endforeach; ?></fieldset>
				<?php if ( $d['cuts'] ) : ?><fieldset><legend>Cut</legend><?php foreach ( $d['cuts'] as $i => $c ) : ?><label class="chip"><input type="radio" name="cut" value="<?php echo esc_attr( $c ); ?>" <?php checked( 0, $i ); ?>><span><?php echo esc_html( $c ); ?></span></label><?php endforeach; ?></fieldset><?php endif; ?>
				<label style="max-width:100px">Quantity<input type="number" id="pdp-q" min="1" max="20" value="1"></label>
				<button type="button" class="btn js-add">Add to cart</button>
			<?php else : ?>
				<p><strong><?php echo pcs_price_kg( $id ) > 0 ? 'Currently out of stock' : 'Call to order'; ?></strong><br><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) ) ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a></p>
			<?php endif; ?>
			<form class="pin-check" style="margin-top:16px"><input type="text" inputmode="numeric" maxlength="6" placeholder="Delivery pincode" aria-label="Pincode"><button class="btn btn-line" type="submit">Check delivery</button><span class="msg" role="status"></span></form>
		</div>
	</div>
</div>
<?php get_footer(); ?>

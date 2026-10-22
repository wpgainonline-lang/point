<?php
get_header();
$banners = pcs_lines( 'banners' );
$cats    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
$all     = get_posts( array( 'post_type' => 'product', 'numberposts' => 40, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
$by_cat  = array();
foreach ( $all as $p ) {
	$t = get_the_terms( $p->ID, 'product_cat' );
	$by_cat[ $t && ! is_wp_error( $t ) ? $t[0]->slug : '_' ][] = $p;
}
$mosaic = array();
foreach ( array( 'chicken', 'mutton' ) as $slug ) {
	foreach ( array_slice( $by_cat[ $slug ] ?? array(), 0, 2 ) as $p ) {
		$mosaic[] = $p;
	}
}
$offer   = pcs_opt( 'offer' );
?>
<?php
$hero_img = array();
foreach ( array( 'chicken' => 'Chicken', 'mutton' => 'Mutton' ) as $slug => $label ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	$url  = pcs_opt( 'hero_img_' . $slug );
	if ( ! $url && ! empty( $by_cat[ $slug ][0] ) ) {
		$url = get_the_post_thumbnail_url( $by_cat[ $slug ][0], 'medium' );
	}
	$hero_img[ $slug ] = array( 'label' => $label, 'url' => $url, 'link' => $term ? get_term_link( $term ) : wc_get_page_permalink( 'shop' ) );
}
?>
<section class="hero3">
	<div class="wrap hero3-in">
		<div class="hero3-txt">
			<span class="kicker">Fresh · Chilled · Hygienic</span>
			<h1>Fresh chicken &amp; mutton, cut your way.</h1>
			<p>Proper cuts, honest weight and cold-chain delivery to your door in <?php echo esc_html( pcs_opt( 'city' ) ); ?>.</p>
			<div class="hero3-row">
				<form class="pin-check"><label class="pin-lbl" for="hero-pin">Check delivery in your area</label><div class="pin-in"><input id="hero-pin" type="text" inputmode="numeric" maxlength="6" placeholder="Enter pincode"><button class="btn btn-dark" type="submit">Check</button></div><span class="msg" role="status"></span></form>
			</div>
		</div>
		<div class="hero3-tiles">
			<?php foreach ( $hero_img as $h ) : ?>
			<div class="h-col">
				<a class="h-tile" href="<?php echo esc_url( $h['link'] ); ?>" aria-label="Shop <?php echo esc_attr( $h['label'] ); ?>"><?php if ( $h['url'] ) : ?><img src="<?php echo esc_url( $h['url'] ); ?>" alt="<?php echo esc_attr( $h['label'] ); ?>" width="170" height="170" fetchpriority="high"><?php endif; ?></a>
				<a class="btn btn-block" href="<?php echo esc_url( $h['link'] ); ?>">Shop <?php echo esc_html( $h['label'] ); ?></a>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="strip"><div class="wrap">
	<div><?php echo pcs_icon( 'snow' ); // phpcs:ignore ?><span><strong>Fresh &amp; chilled</strong>Never left out in the open</span></div>
	<div><?php echo pcs_icon( 'knife' ); // phpcs:ignore ?><span><strong>Cut your way</strong>Curry, biryani, boneless &amp; more</span></div>
	<div><?php echo pcs_icon( 'bike' ); // phpcs:ignore ?><span><strong>Slot delivery</strong>Today or tomorrow</span></div>
	<div><?php echo pcs_icon( 'cash' ); // phpcs:ignore ?><span><strong>Pay on delivery</strong>Cash when it arrives</span></div>
</div></section>

<?php if ( $offer ) : ?>
<section class="offer"><div class="wrap"><?php echo esc_html( $offer ); ?></div></section>
<?php endif; ?>

<?php $promos = pcs_lines( 'promos' ); $ci = 0; ?>
<?php foreach ( $cats as $t ) : $list = array_slice( $by_cat[ $t->slug ] ?? array(), 0, 12 ); if ( ! $list ) { continue; } ?>
<section class="section"><div class="wrap">
	<div class="section-head"><h2>Best of <?php echo esc_html( $t->name ); ?></h2><a href="<?php echo esc_url( get_term_link( $t ) ); ?>">See all <?php echo esc_html( $t->name ); ?></a></div>
	<div class="car" data-car>
		<div class="car-track" tabindex="0"><?php foreach ( $list as $p ) { echo pcs_card( $p->ID ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<button type="button" class="sl-btn sl-prev" aria-label="Previous">‹</button><button type="button" class="sl-btn sl-next" aria-label="Next">›</button>
	</div>
</div></section>
<?php if ( 0 === $ci && $promos && count( $cats ) > 1 ) : ?>
<section class="promo-strip"><div class="wrap">
	<div class="pf" data-fade="4500">
		<?php foreach ( $promos as $i => $pr ) : ?><a class="<?php echo 0 === $i ? 'on' : ''; ?>" href="<?php echo esc_url( 0 === strpos( $pr[2] ?? '', 'http' ) ? $pr[2] : home_url( $pr[2] ?? '/' ) ); ?>"<?php echo $i ? ' tabindex="-1" aria-hidden="true"' : ''; ?>><img src="<?php echo esc_url( $pr[0] ); ?>" alt="<?php echo esc_attr( $pr[1] ?? '' ); ?>" loading="lazy" width="1200" height="100"></a><?php endforeach; ?>
	</div>
</div></section>
<?php endif; $ci++; ?>
<?php endforeach; ?>

<?php if ( $banners ) : ?>
<section class="section" style="padding:0 0 32px"><div>
	<div class="slider slider-full" data-slider>
		<div class="hero" tabindex="0" aria-label="Offers">
			<?php foreach ( $banners as $b ) : ?><a class="slide" href="<?php echo esc_url( home_url( $b[3] ?? '/' ) ); ?>"><img src="<?php echo esc_url( $b[0] ); ?>" alt="<?php echo esc_attr( $b[1] ?? '' ); ?>" loading="lazy" width="1896" height="829"></a><?php endforeach; ?>
		</div>
		<?php if ( count( $banners ) > 1 ) : ?><button type="button" class="sl-btn sl-prev" aria-label="Previous">‹</button><button type="button" class="sl-btn sl-next" aria-label="Next">›</button><?php endif; ?>
	</div>
</div></section>
<?php endif; ?>

<section class="section soft"><div class="wrap">
	<div class="section-head"><h2>How it works</h2></div>
	<div class="steps">
		<div><strong>Pick your cuts</strong>Choose chicken or mutton and the weight you need.</div>
		<div><strong>Choose a slot</strong>Select a delivery day and time that suits you.</div>
		<div><strong>We cut &amp; pack</strong>Cut fresh, weighed and packed hygienically.</div>
		<div><strong>Delivered chilled</strong>Pay cash on delivery at your door.</div>
	</div>
</div></section>

<?php
$q_a = $all[0] ?? null;
$q_b = $all[12] ?? ( $all[1] ?? null );
$q_points = array(
	array( 'shield', 'Carefully sourced', 'Quality meat from trusted suppliers, checked before processing.' ),
	array( 'knife', 'Hygienic environment', 'Cleaned and processed in a controlled, hygienic space.' ),
	array( 'snow', 'Rapidly chilled', 'Chilled quickly to keep freshness, texture and quality.' ),
	array( 'star', 'Weighed &amp; packed', 'Every portion accurately weighed and hygienically packed.' ),
);
?>
<section class="section qh-sec"><div class="wrap qh">
	<div class="qh-txt">
		<span class="kicker">Quality &amp; hygiene</span>
		<h2>Clean meat starts with a clean process</h2>
		<p class="qh-lead">From selection and storage to cutting and packing, every stage has a clear standard. Walk in any day and see how the meat is stored, cut and packed before you order.</p>
		<div class="qh-pts">
			<?php foreach ( $q_points as $pt ) : ?>
			<div class="qh-pt"><span class="qh-ic"><?php echo pcs_icon( $pt[0] ); // phpcs:ignore ?></span><div><strong><?php echo wp_kses_post( $pt[1] ); ?></strong><span><?php echo esc_html( $pt[2] ); ?></span></div></div>
			<?php endforeach; ?>
		</div>
		<div class="qh-btns">
			<a class="btn" href="<?php echo esc_url( pcs_page_url( 'quality-hygiene' ) ); ?>">See our process &rarr;</a>
			<a class="btn btn-line" href="<?php echo esc_url( pcs_page_url( 'quality-hygiene' ) ); ?>#visit">Visit the counter</a>
		</div>
	</div>
	<div class="qh-pics">
		<?php if ( $q_a ) { echo get_the_post_thumbnail( $q_a, 'large', array( 'class' => 'qh-big', 'loading' => 'lazy' ) ); } ?>
		<?php if ( $q_b ) { echo get_the_post_thumbnail( $q_b, 'medium_large', array( 'class' => 'qh-small', 'loading' => 'lazy' ) ); } ?>
		<span class="qh-tag"><?php echo pcs_icon( 'snow' ); // phpcs:ignore ?> Fresh · Chilled · Hygienic</span>
	</div>
</div></section>

<?php $revs = pcs_lines( 'testimonials' ); if ( $revs ) : ?>
<section class="section rev-sec"><div class="wrap">
	<div class="center-head">
		<span class="kicker">Reviews</span>
		<h2>What our customers say</h2>
		<p class="stars-line"><span class="stars" aria-hidden="true">★★★★★</span> Verified customer reviews</p>
	</div>
	<div class="car car-rev" data-car data-auto="4500">
		<div class="car-track" tabindex="0">
			<?php foreach ( $revs as $r ) : ?>
			<figure class="rcard">
				<span class="qm" aria-hidden="true">&ldquo;</span>
				<div class="stars" role="img" aria-label="5 out of 5 stars">★★★★★</div>
				<blockquote><?php echo esc_html( $r[2] ?? '' ); ?></blockquote>
				<figcaption>
					<span class="av" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $r[0], 0, 1 ) ) ); ?></span>
					<span><strong><?php echo esc_html( $r[0] ); ?></strong><span class="muted"><?php echo esc_html( $r[1] ?? '' ); ?></span></span>
					<span class="ver"><?php echo pcs_icon( 'shield' ); // phpcs:ignore ?> Verified</span>
				</figcaption>
			</figure>
			<?php endforeach; ?>
		</div>
		<button type="button" class="sl-btn sl-prev" aria-label="Previous">‹</button><button type="button" class="sl-btn sl-next" aria-label="Next">›</button>
	</div>
	<div class="car-dots" aria-hidden="true"></div>
</div></section>
<?php endif; ?>

<?php $faqs = pcs_lines( 'faqs' ); if ( $faqs ) : ?>
<section class="section soft faq-sec"><div class="wrap">
	<div class="center-head">
		<span class="kicker">FAQ</span>
		<h2>Frequently asked questions</h2>
	</div>
	<div class="faq-grid">
		<?php foreach ( $faqs as $f ) : ?>
		<details class="faq-item"><summary><span><?php echo esc_html( pcs_faq_text( $f[0] ) ); ?></span><i class="pm" aria-hidden="true"></i></summary><p><?php echo esc_html( pcs_faq_text( $f[1] ?? '' ) ); ?></p></details>
		<?php endforeach; ?>
	</div>
</div></section>
<?php endif; ?>
<?php get_footer(); ?>

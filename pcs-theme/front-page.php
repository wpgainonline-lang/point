<?php
get_header();
$banners = pcs_lines( 'banners' );
$cats    = get_terms( array( 'taxonomy' => 'pcs_cat', 'hide_empty' => true ) );
$all     = get_posts( array( 'post_type' => 'pcs_product', 'numberposts' => 40, 'meta_key' => 'pcs_order', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) );
$by_cat  = array();
foreach ( $all as $p ) {
	$t = get_the_terms( $p->ID, 'pcs_cat' );
	$by_cat[ $t && ! is_wp_error( $t ) ? $t[0]->slug : '_' ][] = $p;
}
$mosaic = array();
foreach ( array( 'chicken', 'mutton' ) as $slug ) {
	foreach ( array_slice( $by_cat[ $slug ] ?? array(), 0, 2 ) as $p ) {
		$mosaic[] = $p;
	}
}
$coupons = pcs_lines( 'coupons' );
?>
<section class="hero2">
	<div class="wrap hero2-in">
		<div class="hero2-txt">
			<span class="kicker">Fresh · Chilled · Hygienic</span>
			<h1>Fresh chicken &amp; mutton,<br>cut your way.</h1>
			<p>Proper cuts, honest weight and cold-chain delivery to your door in <?php echo esc_html( pcs_opt( 'city' ) ); ?>.</p>
			<div class="hero2-cta">
				<?php foreach ( $cats as $t ) : ?><a class="btn" href="<?php echo esc_url( get_term_link( $t ) ); ?>">Shop <?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
			</div>
			<form class="pin-check"><input type="text" inputmode="numeric" maxlength="6" placeholder="Enter delivery pincode" aria-label="Pincode"><button class="btn btn-dark" type="submit">Check</button><span class="msg" role="status"></span></form>
			<ul class="chips"><li>Cash on delivery</li><li>Choose your time slot</li><li>Cut as you like</li></ul>
		</div>
		<div class="hero2-pics">
			<?php foreach ( array_slice( $mosaic, 0, 4 ) as $p ) : ?>
			<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'eager', 'alt' => esc_attr( $p->post_title ) ) ); ?><span><?php echo esc_html( $p->post_title ); ?></span></a>
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

<section class="section"><div class="wrap">
	<div class="section-head"><h2>Shop by cut</h2><a href="<?php echo esc_url( get_post_type_archive_link( 'pcs_product' ) ); ?>">View all</a></div>
	<div class="rail">
		<?php foreach ( $all as $p ) : ?>
		<a class="tile" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo get_the_post_thumbnail( $p, 'medium', array( 'loading' => 'lazy' ) ); ?><span><?php echo esc_html( $p->post_title ); ?></span></a>
		<?php endforeach; ?>
	</div>
</div></section>

<?php if ( $coupons ) : ?>
<section class="offer"><div class="wrap"><strong>Offer:</strong> use code <code><?php echo esc_html( $coupons[0][0] ); ?></code> and get <?php echo 'percent' === ( $coupons[0][1] ?? '' ) ? esc_html( $coupons[0][2] ) . '% off' : esc_html( pcs_money( $coupons[0][2] ?? 0 ) ) . ' off'; ?><?php echo ! empty( $coupons[0][3] ) ? ' on orders above ' . esc_html( pcs_money( $coupons[0][3] ) ) : ''; ?>.</div></section>
<?php endif; ?>

<?php foreach ( $cats as $t ) : $list = array_slice( $by_cat[ $t->slug ] ?? array(), 0, 4 ); if ( ! $list ) { continue; } ?>
<section class="section"><div class="wrap">
	<div class="section-head"><h2>Best of <?php echo esc_html( $t->name ); ?></h2><a href="<?php echo esc_url( get_term_link( $t ) ); ?>">See all <?php echo esc_html( $t->name ); ?></a></div>
	<div class="grid"><?php foreach ( $list as $p ) { echo pcs_card( $p->ID ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</div></section>
<?php endforeach; ?>

<?php if ( $banners ) : ?>
<section class="section"><div class="wrap">
	<div class="slider" data-slider>
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

<section class="section"><div class="wrap split">
	<div>
		<span class="kicker">Quality &amp; hygiene</span>
		<h2>Clean meat starts with a clean process</h2>
		<p>From selection and storage to cutting and packing, every stage has a clear standard. Walk in any day and see how the meat is stored, cut and packed before you order.</p>
		<ul class="ticks"><li>Carefully sourced and checked</li><li>Processed in a hygienic environment</li><li>Rapidly chilled to keep freshness</li><li>Weighed and packed for your kitchen</li></ul>
		<a class="btn btn-line" href="<?php echo esc_url( pcs_page_url( 'quality-hygiene' ) ); ?>">Our process</a>
	</div>
	<div class="split-pics">
		<?php foreach ( array_merge( array_slice( $all, 0, 1 ), array_slice( $all, 12, 1 ) ) as $p ) { echo get_the_post_thumbnail( $p, 'large', array( 'loading' => 'lazy' ) ); } ?>
	</div>
</div></section>

<section class="section soft"><div class="wrap split faq-wrap">
	<div><span class="kicker">Questions</span><h2>Good to know</h2><p>Still unsure? Call us on <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) ) ); ?>"><strong><?php echo esc_html( pcs_opt( 'phone' ) ); ?></strong></a>.</p></div>
	<div class="faq">
		<details><summary>Where do you deliver?</summary><p>We currently deliver in <?php echo esc_html( pcs_opt( 'city' ) ); ?>. Enter your pincode on this page to check. More cities are coming soon.</p></details>
		<details><summary>How do I pay?</summary><p>Cash on delivery for now. Online payment will be added soon.</p></details>
		<details><summary>What is the minimum order and delivery charge?</summary><p>Minimum order is <?php echo esc_html( pcs_money( pcs_opt( 'min_order', 0 ) ) ); ?>. Delivery is <?php echo esc_html( pcs_money( pcs_opt( 'delivery_fee', 0 ) ) ); ?><?php echo (float) pcs_opt( 'free_above', 0 ) > 0 ? ', and free above ' . esc_html( pcs_money( pcs_opt( 'free_above' ) ) ) : ''; ?>.</p></details>
		<details><summary>Can I choose how the meat is cut?</summary><p>Yes. Pick the cut you want, such as curry cut, biryani cut or boneless. For special requests, add a note at checkout.</p></details>
		<details><summary>Can I visit the shop?</summary><p>Yes, you are welcome. We are at <?php echo esc_html( str_replace( "\n", ', ', pcs_opt( 'address' ) ) ); ?>.</p></details>
	</div>
</div></section>
<?php get_footer(); ?>

<?php get_header(); ?>
<?php $banners = pcs_lines( 'banners' ); ?>
<?php if ( $banners ) : ?>
<section class="hero" aria-label="Offers">
	<?php foreach ( $banners as $b ) : ?>
	<a class="slide" href="<?php echo esc_url( home_url( $b[3] ?? '/' ) ); ?>"><img src="<?php echo esc_url( $b[0] ); ?>" alt="<?php echo esc_attr( $b[1] ?? '' ); ?>" width="1896" height="829"></a>
	<?php endforeach; ?>
</section>
<?php else : ?>
<section class="hero-plain"><div class="wrap"><h1>Fresh chicken &amp; mutton, delivered in <?php echo esc_html( pcs_opt( 'city' ) ); ?></h1></div></section>
<?php endif; ?>

<section class="pin-strip"><div class="wrap">
	<form class="pin-check"><strong>Delivering in <?php echo esc_html( pcs_opt( 'city' ) ); ?></strong>
		<input type="text" inputmode="numeric" maxlength="6" placeholder="Enter pincode" aria-label="Pincode"><button class="btn" type="submit">Check</button><span class="msg" role="status"></span></form>
</div></section>

<section class="section"><div class="wrap">
	<div class="section-head"><h2>Shop by category</h2></div>
	<div class="cats">
	<?php foreach ( get_terms( array( 'taxonomy' => 'pcs_cat', 'hide_empty' => true ) ) as $t ) :
		$first = get_posts( array( 'post_type' => 'pcs_product', 'numberposts' => 1, 'tax_query' => array( array( 'taxonomy' => 'pcs_cat', 'terms' => $t->term_id ) ), 'meta_key' => 'pcs_order', 'orderby' => 'meta_value_num', 'order' => 'ASC' ) );
		$img   = $first ? get_the_post_thumbnail_url( $first[0], 'medium' ) : ''; ?>
		<a class="cat" href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" width="300" height="225"><?php endif; ?><span><?php echo esc_html( $t->name ); ?></span></a>
	<?php endforeach; ?>
	</div>
</div></section>

<?php $feat = get_posts( array( 'post_type' => 'pcs_product', 'numberposts' => 8, 'meta_key' => 'pcs_featured', 'meta_value' => '1', 'fields' => 'ids' ) ); ?>
<?php if ( $feat ) : ?>
<section class="section"><div class="wrap">
	<div class="section-head"><h2>Popular cuts</h2><a href="<?php echo esc_url( get_post_type_archive_link( 'pcs_product' ) ); ?>">View all</a></div>
	<div class="grid"><?php foreach ( $feat as $id ) { echo pcs_card( $id ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</div></section>
<?php endif; ?>

<section class="section"><div class="wrap"><div class="usps">
	<?php foreach ( pcs_lines( 'usps' ) as $u ) : ?><div class="usp"><strong><?php echo esc_html( $u[0] ); ?></strong><?php echo esc_html( $u[1] ?? '' ); ?></div><?php endforeach; ?>
</div></div></section>

<section class="section"><div class="wrap">
	<div class="section-head"><h2>How it works</h2></div>
	<div class="steps">
		<div><strong>Pick your cuts</strong><br>Choose chicken or mutton and the weight you need.</div>
		<div><strong>Choose a slot</strong><br>Select a delivery day and time that suits you.</div>
		<div><strong>We cut &amp; pack</strong><br>Cut fresh, weighed and packed hygienically.</div>
		<div><strong>Delivered chilled</strong><br>Pay cash on delivery at your door.</div>
	</div>
</div></section>
<?php get_footer(); ?>

<?php
/**
 * Quality & Hygiene page. Words and photos are edited at Meat Shop → Quality page.
 */
get_header();
$pillars = pcs_quality_lines( 'pillars' );
$steps   = pcs_quality_lines( 'steps' );
$steps2  = pcs_quality_lines( 'steps2' );
$icons   = array( 'snow', 'shield', 'clock' );
$tel     = preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) );
$map     = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( str_replace( "\n", ', ', pcs_opt( 'address' ) ) );
$hero    = pcs_about_image( 'none', 5 );
if ( pcs_quality( 'hero_image' ) ) {
	$hero = pcs_quality( 'hero_image' );
}
$prods = get_posts( array( 'post_type' => 'product', 'numberposts' => 24, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
$timeline = function ( $list, $kicker, $title, $text ) {
	?>
	<section class="section"><div class="wrap q-split">
		<div class="q-head">
			<?php if ( $kicker ) : ?><span class="kicker"><?php echo esc_html( $kicker ); ?></span><?php endif; ?>
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php if ( $text ) : ?><p><?php echo esc_html( $text ); ?></p><?php endif; ?>
		</div>
		<ol class="q-time">
			<?php foreach ( $list as $i => $st ) : ?>
			<li><span class="num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><div><h3><?php echo esc_html( $st[0] ); ?></h3><p><?php echo esc_html( $st[1] ?? '' ); ?></p></div></li>
			<?php endforeach; ?>
		</ol>
	</div></section>
	<?php
};
?>
<section class="ab-hero">
	<div class="wrap ab-hero-in">
		<div>
			<span class="kicker"><?php echo esc_html( pcs_quality( 'hero_kicker' ) ); ?></span>
			<h1><?php echo esc_html( pcs_quality( 'hero_title' ) ); ?></h1>
			<p><?php echo esc_html( pcs_quality( 'hero_text' ) ); ?></p>
			<div class="hero2-cta"><a class="btn" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Shop fresh meat</a><a class="btn btn-line" href="#visit">Visit the counter</a></div>
		</div>
		<?php if ( $hero ) : ?><div class="ab-hero-img"><img src="<?php echo esc_url( $hero ); ?>" alt="" width="800" height="600" fetchpriority="high"></div><?php endif; ?>
	</div>
</section>

<?php if ( $pillars ) : ?>
<section class="q-pillars"><div class="wrap">
	<?php foreach ( $pillars as $i => $p ) : ?>
	<div><?php echo pcs_icon( $icons[ $i % count( $icons ) ] ); // phpcs:ignore ?><h3><?php echo esc_html( $p[0] ); ?></h3><p><?php echo esc_html( $p[1] ?? '' ); ?></p></div>
	<?php endforeach; ?>
</div></section>
<?php endif; ?>

<?php
if ( $steps ) {
	$timeline( $steps, pcs_quality( 'steps_kicker' ), pcs_quality( 'steps_title' ), pcs_quality( 'steps_text' ) );
}
if ( $steps2 && pcs_quality( 'steps2_title' ) ) {
	$timeline( $steps2, '', pcs_quality( 'steps2_title' ), pcs_quality( 'steps2_text' ) );
}
?>

<?php if ( $prods ) : ?>
<section class="section soft"><div class="wrap">
	<div class="section-head"><h2><?php echo esc_html( pcs_quality( 'cuts_title' ) ); ?></h2><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">View all</a></div>
	<div class="q-cuts">
		<?php foreach ( $prods as $p ) : ?>
		<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo get_the_post_thumbnail( $p, 'medium', array( 'loading' => 'lazy' ) ); ?><span><?php echo esc_html( $p->post_title ); ?></span></a>
		<?php endforeach; ?>
	</div>
</div></section>
<?php endif; ?>

<?php while ( have_posts() ) : the_post(); if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
<section class="section"><div class="wrap"><div class="content"><?php the_content(); ?></div></div></section>
<?php endif; endwhile; ?>

<section class="section" id="visit"><div class="wrap ab-split">
	<div>
		<span class="kicker">See it for yourself</span>
		<h2><?php echo esc_html( pcs_quality( 'visit_title' ) ); ?></h2>
		<p><?php echo esc_html( pcs_quality( 'visit_text' ) ); ?></p>
		<div class="ab-info">
			<p><?php echo pcs_icon( 'pin' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></span></p>
			<p><?php echo pcs_icon( 'clock' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></span></p>
		</div>
		<a class="btn" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">Get directions</a>
		<a class="btn btn-line" href="tel:<?php echo esc_attr( $tel ); ?>">Call us</a>
	</div>
	<form class="panel" id="pcs-contact">
		<h3>Request a call</h3>
		<p class="muted">Leave your number and we will call you back.</p>
		<label>Name<input type="text" name="name" required autocomplete="name"></label>
		<label>Phone<input type="tel" name="phone" required autocomplete="tel"></label>
		<input type="hidden" name="message" value="Please call me back (from the Quality &amp; Hygiene page).">
		<button class="btn btn-block" type="submit">Request a call</button>
		<p class="msg" id="ct-msg"></p>
	</form>
</div></section>
<?php get_footer(); ?>

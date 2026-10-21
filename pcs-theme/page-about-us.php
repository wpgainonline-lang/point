<?php
/**
 * About page. Words and photos are edited at Meat Shop → About page.
 */
get_header();
$vals   = pcs_about_lines( 'values' );
$steps  = pcs_about_lines( 'steps' );
$stats  = pcs_about_lines( 'stats' );
$icons  = array( 'snow', 'shield', 'knife', 'star' );
$tel    = preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) );
$map    = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( str_replace( "\n", ', ', pcs_opt( 'address' ) ) );
$hero   = pcs_about_image( 'hero_image', 0 );
$story1 = pcs_about_image( 'story_image', 1 );
$story2 = pcs_about( 'story_image' ) ? '' : pcs_about_image( 'none', 11 );
$cats   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
?>
<section class="ab-hero">
	<div class="wrap ab-hero-in">
		<div>
			<span class="kicker"><?php echo esc_html( pcs_about( 'hero_kicker' ) ); ?></span>
			<h1><?php echo esc_html( pcs_about( 'hero_title' ) ); ?></h1>
			<p><?php echo esc_html( pcs_about( 'hero_text' ) ); ?></p>
			<div class="hero2-cta">
				<?php foreach ( $cats as $t ) : ?><a class="btn" href="<?php echo esc_url( get_term_link( $t ) ); ?>">Shop <?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
				<a class="btn btn-line" href="#visit">Visit the shop</a>
			</div>
		</div>
		<?php if ( $hero ) : ?><div class="ab-hero-img"><img src="<?php echo esc_url( $hero ); ?>" alt="" width="800" height="600" fetchpriority="high"></div><?php endif; ?>
	</div>
</section>

<section class="section"><div class="wrap ab-split">
	<div class="ab-imgs<?php echo $story2 ? ' two' : ''; ?>">
		<?php if ( $story1 ) : ?><img src="<?php echo esc_url( $story1 ); ?>" alt="" loading="lazy" width="600" height="450"><?php endif; ?>
		<?php if ( $story2 ) : ?><img src="<?php echo esc_url( $story2 ); ?>" alt="" loading="lazy" width="600" height="450"><?php endif; ?>
	</div>
	<div>
		<span class="kicker">Our story</span>
		<h2><?php echo esc_html( pcs_about( 'story_title' ) ); ?></h2>
		<?php foreach ( preg_split( '/\n\s*\n/', trim( (string) pcs_about( 'story' ) ) ) as $para ) : ?><p><?php echo esc_html( trim( $para ) ); ?></p><?php endforeach; ?>
	</div>
</div></section>

<?php if ( $stats ) : ?>
<section class="ab-stats"><div class="wrap">
	<?php foreach ( $stats as $st ) : ?><div><strong><?php echo esc_html( pcs_about_stat_text( $st[0] ) ); ?></strong><span><?php echo esc_html( $st[1] ?? '' ); ?></span></div><?php endforeach; ?>
</div></section>
<?php endif; ?>

<section class="section"><div class="wrap ab-vm">
	<div class="ab-card"><span class="kicker">Our vision</span><h3>What we aim for</h3><p><?php echo esc_html( pcs_about( 'vision' ) ); ?></p></div>
	<div class="ab-card dark"><span class="kicker">Our mission</span><h3>How we get there</h3><p><?php echo esc_html( pcs_about( 'mission' ) ); ?></p></div>
</div></section>

<?php if ( $vals ) : ?>
<section class="section soft"><div class="wrap">
	<div class="section-head"><h2><?php echo esc_html( pcs_about( 'values_title' ) ); ?></h2></div>
	<div class="ab-values">
		<?php foreach ( $vals as $i => $v ) : ?>
		<div class="ab-val"><?php echo pcs_icon( $icons[ $i % count( $icons ) ] ); // phpcs:ignore ?><h3><?php echo esc_html( $v[0] ); ?></h3><p><?php echo esc_html( $v[1] ?? '' ); ?></p></div>
		<?php endforeach; ?>
	</div>
</div></section>
<?php endif; ?>

<?php if ( $steps ) : ?>
<section class="section"><div class="wrap">
	<div class="section-head"><h2><?php echo esc_html( pcs_about( 'steps_title' ) ); ?></h2></div>
	<ol class="ab-steps">
		<?php foreach ( $steps as $i => $st ) : ?>
		<li><span class="num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><h3><?php echo esc_html( $st[0] ); ?></h3><p><?php echo esc_html( $st[1] ?? '' ); ?></p></li>
		<?php endforeach; ?>
	</ol>
</div></section>
<?php endif; ?>

<?php while ( have_posts() ) : the_post(); if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
<section class="section"><div class="wrap"><div class="content"><?php the_content(); ?></div></div></section>
<?php endif; endwhile; ?>

<section class="section soft" id="visit"><div class="wrap ab-split">
	<div>
		<span class="kicker">Visit us</span>
		<h2><?php echo esc_html( pcs_about( 'visit_title' ) ); ?></h2>
		<p><?php echo esc_html( pcs_about( 'visit_text' ) ); ?></p>
		<a class="btn" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">Get directions</a>
		<a class="btn btn-line" href="tel:<?php echo esc_attr( $tel ); ?>">Call us</a>
	</div>
	<div class="ab-info">
		<p><?php echo pcs_icon( 'pin' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></span></p>
		<p><?php echo pcs_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a></p>
		<p><?php echo pcs_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>"><?php echo esc_html( pcs_opt( 'email' ) ); ?></a></p>
		<p><?php echo pcs_icon( 'clock' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></span></p>
	</div>
</div></section>

<section class="ab-cta"><div class="wrap">
	<h2><?php echo esc_html( pcs_about( 'cta_title' ) ); ?></h2>
	<p><?php echo esc_html( pcs_about( 'cta_text' ) ); ?></p>
	<a class="btn btn-white" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Order now</a>
</div></section>
<?php get_footer(); ?>

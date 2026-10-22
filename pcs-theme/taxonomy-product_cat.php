<?php
get_header();
$cur   = get_queried_object();
$img   = pcs_opt( 'hero_img_' . $cur->slug );
$total = $GLOBALS['wp_query']->found_posts;
$lead  = array(
	'chicken' => 'Fresh chicken, cleaned, cut and packed hygienically. Pick the cut and weight you need.',
	'mutton'  => 'Tender mutton cuts, cut to order and packed chilled for rich, flavourful curries.',
);
?>
<div class="wrap">
	<section class="cat-hero">
		<div class="cat-hero-t">
			<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <?php echo esc_html( $cur->name ); ?></div>
			<h1><?php echo esc_html( $cur->name ); ?></h1>
			<p><?php echo esc_html( wp_strip_all_tags( $cur->description ) ?: ( $lead[ $cur->slug ] ?? 'Fresh cuts, delivered chilled across Chennai.' ) ); ?></p>
			<div class="cat-hero-n"><span><?php echo (int) $total; ?> products</span><span>Fresh &amp; chilled</span><span>Cash on delivery</span></div>
		</div>
		<?php if ( $img ) : ?><div class="cat-hero-i"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $cur->name ); ?>" loading="eager"></div><?php endif; ?>
	</section>
	<div class="tabs cat-tabs">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">All</a>
		<?php foreach ( get_terms( array( 'taxonomy' => 'product_cat' ) ) as $t ) : ?><a href="<?php echo esc_url( get_term_link( $t ) ); ?>" <?php echo $t->term_id === $cur->term_id ? 'class="on"' : ''; ?>><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
	</div>
	<div class="grid grid4"><?php while ( have_posts() ) { the_post(); echo pcs_card( get_the_ID() ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php if ( ! have_posts() ) : ?><p>No products yet.</p><?php endif; ?>
</div>
<?php get_footer(); ?>

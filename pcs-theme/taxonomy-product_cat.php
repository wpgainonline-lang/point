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
	<div class="page-head"><div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <?php echo esc_html( $cur->name ); ?></div><h1><?php echo esc_html( $cur->name ); ?></h1></div>
	<div class="tabs cat-tabs">
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">All</a>
		<?php foreach ( get_terms( array( 'taxonomy' => 'product_cat' ) ) as $t ) : ?><a href="<?php echo esc_url( get_term_link( $t ) ); ?>" <?php echo $t->term_id === $cur->term_id ? 'class="on"' : ''; ?>><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
	</div>
	<div class="grid grid4"><?php while ( have_posts() ) { the_post(); echo pcs_card( get_the_ID() ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php if ( ! have_posts() ) : ?><p>No products yet.</p><?php endif; ?>
</div>
<?php get_footer(); ?>

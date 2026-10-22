<?php get_header(); $total = $GLOBALS['wp_query']->found_posts; ?>
<div class="wrap">
	<section class="cat-hero">
		<div class="cat-hero-t">
			<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / All products</div>
			<h1>All products</h1>
			<p>Fresh chicken and mutton, cut your way and delivered chilled across Chennai.</p>
			<div class="cat-hero-n"><span><?php echo (int) $total; ?> products</span><span>Fresh &amp; chilled</span><span>Cash on delivery</span></div>
		</div>
	</section>
	<div class="tabs cat-tabs">
		<a class="on" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">All</a>
		<?php foreach ( get_terms( array( 'taxonomy' => 'product_cat' ) ) as $t ) : ?><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
	</div>
	<div class="grid grid4"><?php while ( have_posts() ) { the_post(); echo pcs_card( get_the_ID() ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
</div>
<?php get_footer(); ?>

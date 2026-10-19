<?php get_header(); ?>
<div class="wrap">
	<div class="page-head"><h1>Results for “<?php echo esc_html( get_search_query() ); ?>”</h1></div>
	<?php if ( have_posts() ) : ?><div class="grid"><?php while ( have_posts() ) { the_post(); if ( 'product' === get_post_type() ) { echo pcs_card( get_the_ID() ); } } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php else : ?><p>Nothing found. <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Browse all products</a>.</p><?php endif; ?>
</div>
<?php get_footer(); ?>

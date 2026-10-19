<?php get_header(); ?>
<div class="wrap">
	<?php while ( have_posts() ) : the_post(); ?>
	<div class="page-head"><h1><?php the_title(); ?></h1></div>
	<div class="<?php echo ( function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || has_shortcode( get_the_content(), 'pcs_contact' ) ? 'woo-page' : 'content'; ?>"><?php the_content(); ?></div>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>

<?php
get_header();
$guest_login = function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() && ! is_wc_endpoint_url( 'lost-password' );
if ( $guest_login ) : ?>
<div class="crumbs-bar"><div class="wrap"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo; Customer Login</div></div>
<?php endif; ?>
<div class="wrap">
	<?php while ( have_posts() ) : the_post(); ?>
	<?php if ( $guest_login ) : ?><h1 class="sr-only">Customer Login</h1><?php else : ?><div class="page-head"><h1><?php the_title(); ?></h1></div><?php endif; ?>
	<div class="<?php echo ( function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || has_shortcode( get_the_content(), 'pcs_contact' ) ? 'woo-page' : 'content'; ?>"><?php the_content(); ?></div>
	<?php endwhile; ?>
</div>
<?php get_footer(); ?>

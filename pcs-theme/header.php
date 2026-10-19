<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> id="top">
<?php wp_body_open(); ?>
<div class="topbar"><div class="wrap tb">
	<span><?php echo pcs_icon( 'pin' ); // phpcs:ignore ?> Delivering in <?php echo esc_html( pcs_opt( 'city' ) ); ?></span>
	<span class="tb-mid"><?php echo esc_html( pcs_opt( 'announce' ) ); ?></span>
	<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) ) ); ?>"><?php echo pcs_icon( 'phone' ); // phpcs:ignore ?> <?php echo esc_html( pcs_opt( 'phone' ) ); ?></a>
</div></div>
<header class="site-header">
	<div class="wrap hd">
		<button class="menu-btn" type="button" aria-controls="nav" aria-expanded="false" aria-label="Menu" onclick="var n=document.getElementById('nav');n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">☰</button>
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo has_custom_logo() ? wp_kses_post( get_custom_logo() ) : esc_html( pcs_opt( 'store_name' ) ); ?></a>
		<form class="hd-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="search" name="s" placeholder="Search for chicken curry cut, mutton liver..." aria-label="Search">
			<input type="hidden" name="post_type" value="pcs_product">
		</form>
		<div class="hd-actions">
			<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>" class="hd-user"><?php echo pcs_icon( 'user' ); // phpcs:ignore ?><span><?php echo is_user_logged_in() ? 'Account' : 'Login'; ?></span></a>
			<a href="<?php echo esc_url( pcs_page_url( 'checkout' ) ); ?>" class="hd-cart"><?php echo pcs_icon( 'cart' ); // phpcs:ignore ?><span>Cart</span><b class="cart-count" hidden>0</b></a>
		</div>
	</div>
	<nav class="nav" id="nav" aria-label="Main"><div class="wrap"><?php pcs_theme_nav(); ?></div></nav>
</header>
<main id="main">

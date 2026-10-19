<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if ( pcs_opt( 'announce' ) ) : ?><div class="topbar"><?php echo esc_html( pcs_opt( 'announce' ) ); ?></div><?php endif; ?>
<header class="site-header">
	<div class="wrap hd">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo has_custom_logo() ? wp_kses_post( get_custom_logo() ) : esc_html( pcs_opt( 'store_name' ) ); ?></a>
		<nav class="nav" id="nav" aria-label="Main"><?php pcs_theme_nav(); ?></nav>
		<form class="hd-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="search" name="s" placeholder="Search chicken, mutton..." aria-label="Search">
			<input type="hidden" name="post_type" value="pcs_product">
		</form>
		<div class="hd-actions">
			<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>"><?php echo is_user_logged_in() ? 'Account' : 'Login'; ?></a>
			<a href="<?php echo esc_url( pcs_page_url( 'checkout' ) ); ?>">Cart<span class="cart-count" hidden>0</span></a>
			<button class="menu-btn" type="button" aria-controls="nav" aria-expanded="false" onclick="var n=document.getElementById('nav');n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">Menu</button>
		</div>
	</div>
</header>
<main id="main">

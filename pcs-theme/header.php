<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> id="top">
<?php wp_body_open(); ?>
<header class="site-header">
	<div class="wrap hd">
		<button class="menu-btn" type="button" aria-controls="nav" aria-expanded="false" aria-label="Menu" onclick="var n=document.getElementById('nav');n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">☰</button>
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php if ( pcs_opt( 'logo' ) ) : ?><img src="<?php echo esc_url( pcs_opt( 'logo' ) ); ?>" alt="<?php echo esc_attr( pcs_opt( 'store_name' ) ); ?>" width="133" height="90"><?php else : echo esc_html( pcs_opt( 'store_name' ) ); endif; ?></a>
		<nav class="nav" id="nav" aria-label="Main"><?php pcs_theme_nav(); ?></nav>
		<form class="hd-search" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" autocomplete="off">
			<?php echo pcs_icon( 'search' ); // phpcs:ignore ?>
			<input type="search" name="s" id="hd-q" placeholder="Search chicken, mutton, cuts..." aria-label="Search products" role="combobox" aria-expanded="false" aria-controls="hd-sug" aria-autocomplete="list">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="Search"><?php echo pcs_icon( 'search' ); // phpcs:ignore ?></button>
			<div class="sug" id="hd-sug" role="listbox" hidden></div>
		</form>
		<div class="hd-actions">
			<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>" class="hd-user"><?php echo pcs_icon( 'user' ); // phpcs:ignore ?><span><?php echo is_user_logged_in() ? 'Account' : 'Login'; ?></span></a>
			<a href="<?php echo esc_url( pcs_page_url( 'checkout' ) ); ?>" class="hd-cart js-cart"><?php echo pcs_icon( 'cart' ); // phpcs:ignore ?><span>Cart</span><b class="cart-count" hidden>0</b></a>
		</div>
	</div>
</header>
<main id="main">

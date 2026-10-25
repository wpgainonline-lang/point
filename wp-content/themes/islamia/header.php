<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="topbar"><div class="wrap">
  <span><?php echo esc_html(ic_get('address')); ?> &nbsp;|&nbsp; <a href="mailto:<?php echo esc_attr(ic_get('email')); ?>"><?php echo esc_html(ic_get('email')); ?></a></span>
  <span><?php if (is_user_logged_in()) : ?><a href="<?php echo esc_url(ic_page_url('portal')); ?>">My portal</a> · <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log out</a><?php else : ?><a href="<?php echo esc_url(ic_page_url('portal')); ?>">Staff / Parent / Student login</a><?php endif; ?></span>
</div></div>
<header class="site-header"><div class="wrap">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
    <?php if (has_custom_logo()) the_custom_logo(); else echo '<span>' . esc_html(get_bloginfo('name')) . '</span>'; ?>
  </a>
  <button class="nav-toggle" aria-label="Menu" aria-controls="main-nav">☰</button>
  <nav class="main-nav" id="main-nav">
    <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'ic_fallback_menu']); ?>
  </nav>
</div></header>

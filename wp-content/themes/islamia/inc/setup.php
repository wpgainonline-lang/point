<?php
defined('ABSPATH') || exit;

function ic_opt($key, $default = '') {
    $v = get_theme_mod($key, null);
    return ($v === null || $v === '') ? $default : $v;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 120, 'width' => 320, 'flex-width' => true, 'flex-height' => true]);
    register_nav_menus(['primary' => 'Main menu', 'footer' => 'Footer menu']);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ic-fonts', 'https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Epilogue:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('ic-style', get_stylesheet_uri(), ['ic-fonts'], IC_VER);
    $css = ':root{';
    foreach (['primary' => '#004B39', 'accent' => '#DB9E30', 'dark' => '#5D2B00'] as $k => $d) {
        $css .= '--ic-' . $k . ':' . esc_attr(ic_opt('color_' . $k, $d)) . ';';
    }
    $css .= '}';
    if ($img = ic_opt('hero_image')) {
        $css .= '.hero{--ic-hero-img:url("' . esc_url($img) . '")}';
    }
    wp_add_inline_style('ic-style', $css);
    wp_enqueue_script('ic-main', IC_URI . '/assets/js/main.js', [], IC_VER, true);
});

/** Fallback menu used until an admin builds one in Appearance → Menus. */
function ic_fallback_menu() {
    $items = [
        'Home' => home_url('/'),
        'Courses' => get_post_type_archive_link('ic_course'),
        'Gallery' => ic_page_url('gallery'),
        'Calendar' => ic_page_url('calendar'),
        'Notices' => get_post_type_archive_link('ic_notice'),
        'Contact' => ic_page_url('contact'),
    ];
    echo '<ul>';
    foreach ($items as $label => $url) {
        if ($url) echo '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
    }
    echo '</ul>';
}

function ic_page_url($slug) {
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : '';
}

/** Hide the toolbar and keep portal users out of wp-admin. */
add_filter('show_admin_bar', function ($show) {
    return ic_is_portal_user() ? false : $show;
});
add_action('admin_init', function () {
    if (ic_is_portal_user() && !wp_doing_ajax() && !(defined('DOING_CRON') && DOING_CRON)
        && !(isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'admin-post.php')) {
        wp_safe_redirect(ic_page_url('portal') ?: home_url('/'));
        exit;
    }
});
add_filter('login_redirect', function ($to, $req, $user) {
    if ($user instanceof WP_User && ic_is_portal_user($user)) return ic_page_url('portal') ?: home_url('/');
    return $to;
}, 10, 3);

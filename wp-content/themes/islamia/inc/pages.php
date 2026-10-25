<?php
defined('ABSPATH') || exit;

/** On activation: create the standard pages, set the front page and build the menu. */
add_action('after_switch_theme', function () {
    ic_setup_roles();
    ic_install_tables();
    $pages = [
        'register' => ['Register', 'templates/register.php', 'Student registration'],
        'portal'   => ['Portal', 'templates/portal.php', ''],
        'gallery'  => ['Gallery', 'templates/gallery.php', ''],
        'calendar' => ['School Calendar', 'templates/calendar.php', ''],
        'contact'  => ['Contact', 'templates/contact.php', ''],
        'donations' => ['Donations', '', 'Add your donation details here (bank account, online link, etc.).'],
        'student-resources' => ['Student Resources', '', 'Add links and downloads for students here.'],
    ];
    $ids = [];
    foreach ($pages as $slug => [$title, $tpl, $content]) {
        $p = get_page_by_path($slug);
        if (!$p) {
            $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content]);
        } else $id = $p->ID;
        if ($tpl) update_post_meta($id, '_wp_page_template', $tpl);
        $ids[$slug] = $id;
    }
    $home = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home']);
    if ($home && !is_wp_error($home)) { update_option('show_on_front', 'page'); update_option('page_on_front', $home); }

    if (!wp_get_nav_menu_object('Main menu')) {
        $mid = wp_create_nav_menu('Main menu');
        $order = 1;
        $add = function ($title, $url, $classes = '') use ($mid, &$order) {
            wp_update_nav_menu_item($mid, 0, ['menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-status' => 'publish', 'menu-item-type' => 'custom', 'menu-item-position' => $order++, 'menu-item-classes' => $classes]);
        };
        $add('Home', home_url('/'));
        $add('Courses', get_post_type_archive_link('ic_course'));
        $add('Gallery', get_permalink($ids['gallery']));
        $add('Calendar', get_permalink($ids['calendar']));
        $add('Notices', get_post_type_archive_link('ic_notice'));
        $add('Contact', get_permalink($ids['contact']));
        $add('Portal', get_permalink($ids['portal']));
        $add('Register Now', get_permalink($ids['register']), 'nav-cta');
        $locs = get_theme_mod('nav_menu_locations', []);
        $locs['primary'] = $mid;
        set_theme_mod('nav_menu_locations', $locs);
    }
    flush_rewrite_rules();
});
add_action('switch_theme', 'flush_rewrite_rules');

<?php
defined('ABSPATH') || exit;

/** Content types the admin edits from the dashboard. */
add_action('init', function () {
    $common = ['public' => true, 'show_in_rest' => true, 'show_in_menu' => 'ic-school', 'menu_icon' => 'dashicons-welcome-learn-more'];
    $types = [
        'ic_course'  => ['Courses', 'Course', true, ['title', 'editor', 'thumbnail', 'excerpt'], 'courses'],
        'ic_notice'  => ['Notices', 'Notice', true, ['title', 'editor', 'thumbnail', 'excerpt'], 'notices'],
        'ic_event'   => ['Calendar Events', 'Event', true, ['title', 'editor', 'thumbnail'], 'events'],
        'ic_gallery' => ['Gallery', 'Photo', false, ['title', 'thumbnail'], 'gallery-item'],
        'ic_review'  => ['Reviews', 'Review', false, ['title', 'editor', 'thumbnail'], 'reviews'],
    ];
    foreach ($types as $slug => [$plural, $single, $archive, $supports, $rewrite]) {
        register_post_type($slug, $common + [
            'labels' => ['name' => $plural, 'singular_name' => $single, 'add_new_item' => "Add $single", 'edit_item' => "Edit $single", 'all_items' => $plural],
            'has_archive' => $archive,
            'publicly_queryable' => $archive,
            'supports' => $supports,
            'rewrite' => ['slug' => $rewrite],
        ]);
    }

    // Private school records — never public, only visible to admins in the dashboard.
    foreach ([
        'ic_student' => ['Students', 'Student', 'ic_student', 'ic_students'],
        'ic_class'   => ['Classes', 'Class', 'ic_class', 'ic_classes'],
    ] as $slug => [$plural, $single, $cs, $cp]) {
        register_post_type($slug, [
            'labels' => ['name' => $plural, 'singular_name' => $single, 'add_new_item' => "Add $single", 'edit_item' => "Edit $single", 'all_items' => $plural, 'search_items' => "Search $plural"],
            'public' => false, 'show_ui' => true, 'show_in_menu' => 'ic-school', 'show_in_rest' => false,
            'supports' => ['title'],
            'capability_type' => [$cs, $cp], 'map_meta_cap' => true,
        ]);
    }
});

add_filter('enter_title_here', function ($t, $post) {
    return $post->post_type === 'ic_student' ? 'Student full name' : ($post->post_type === 'ic_class' ? 'Class name (e.g. Quran Level 1)' : $t);
}, 10, 2);

add_filter('pre_get_posts', function (WP_Query $q) {
    if (!is_admin() && $q->is_main_query()) {
        if ($q->is_post_type_archive('ic_event')) { $q->set('meta_key', 'ic_date'); $q->set('orderby', 'meta_value'); $q->set('order', 'ASC'); $q->set('posts_per_page', 50); }
        if ($q->is_post_type_archive(['ic_notice', 'ic_course'])) $q->set('posts_per_page', 12);
    }
});

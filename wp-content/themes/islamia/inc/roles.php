<?php
defined('ABSPATH') || exit;

const IC_PORTAL_ROLES = ['ic_teacher', 'ic_parent', 'ic_student_user'];

function ic_setup_roles() {
    add_role('ic_teacher', 'Islamia Teacher', ['read' => true]);
    add_role('ic_parent', 'Islamia Parent', ['read' => true]);
    add_role('ic_student_user', 'Islamia Student', ['read' => true]);

    // Admins manage the private school records.
    if ($admin = get_role('administrator')) {
        foreach (['ic_student', 'ic_class'] as $pt) {
            $obj = get_post_type_object($pt);
            if ($obj) foreach ((array) $obj->cap as $cap) $admin->add_cap($cap);
        }
    }
}
add_action('init', function () {
    if (get_option('ic_roles_ver') !== IC_VER) {
        ic_setup_roles();
        update_option('ic_roles_ver', IC_VER);
    }
}, 30);

function ic_user_role($user = null) {
    $user = $user ?: wp_get_current_user();
    if (!$user || !$user->exists()) return '';
    if (user_can($user, 'manage_options')) return 'admin';
    foreach (['ic_teacher' => 'teacher', 'ic_parent' => 'parent', 'ic_student_user' => 'student'] as $r => $label) {
        if (in_array($r, (array) $user->roles, true)) return $label;
    }
    return '';
}

/** True for users who only have portal access (not dashboard access). */
function ic_is_portal_user($user = null) {
    $r = ic_user_role($user);
    return in_array($r, ['teacher', 'parent', 'student'], true);
}

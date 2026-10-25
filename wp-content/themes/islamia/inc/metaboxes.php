<?php
defined('ABSPATH') || exit;

/** Generic meta-box helper: fields = key => [label, type, options|null]. */
function ic_metabox($id, $title, $post_type, array $fields, $context = 'normal') {
    add_action('add_meta_boxes', function () use ($id, $title, $post_type, $fields, $context) {
        add_meta_box($id, $title, function ($post) use ($id, $fields) {
            wp_nonce_field($id, $id . '_n');
            echo '<table class="form-table" role="presentation">';
            foreach ($fields as $key => [$label, $type, $opts]) {
                $val = get_post_meta($post->ID, $key, true);
                echo '<tr><th><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
                if ($type === 'select') {
                    echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '"><option value="">— none —</option>';
                    foreach ((array) $opts as $v => $l) printf('<option value="%s"%s>%s</option>', esc_attr($v), selected((string) $val, (string) $v, false), esc_html($l));
                    echo '</select>';
                } elseif ($type === 'multi') {
                    $val = (array) $val;
                    foreach ((array) $opts as $v => $l) printf('<label style="display:block;font-weight:400"><input type="checkbox" name="%s[]" value="%s"%s> %s</label>', esc_attr($key), esc_attr($v), checked(in_array((string) $v, array_map('strval', $val), true), true, false), esc_html($l));
                    if (!$opts) echo '<em>No teachers yet — add users with the role “Islamia Teacher”.</em>';
                } elseif ($type === 'textarea') {
                    printf('<textarea name="%s" id="%s" rows="3" class="large-text">%s</textarea>', esc_attr($key), esc_attr($key), esc_textarea($val));
                } else {
                    printf('<input type="%s" name="%s" id="%s" value="%s" class="regular-text">', esc_attr($type), esc_attr($key), esc_attr($key), esc_attr($val));
                }
                echo '</td></tr>';
            }
            echo '</table>';
        }, $post_type, $context);
    });
    add_action('save_post_' . $post_type, function ($post_id) use ($id, $fields) {
        if (!isset($_POST[$id . '_n']) || !wp_verify_nonce($_POST[$id . '_n'], $id)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        foreach ($fields as $key => [, $type]) {
            if ($type === 'multi') {
                $v = isset($_POST[$key]) ? array_map('absint', (array) wp_unslash($_POST[$key])) : [];
                update_post_meta($post_id, $key, array_map('strval', $v));
                continue;
            }
            $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            $v = $type === 'textarea' ? sanitize_textarea_field($raw) : ($type === 'email' ? sanitize_email($raw) : sanitize_text_field($raw));
            update_post_meta($post_id, $key, $v);
        }
    });
}

function ic_users_by_role($role) {
    $out = [];
    foreach (get_users(['role' => $role, 'orderby' => 'display_name']) as $u) $out[$u->ID] = $u->display_name . ' (' . $u->user_email . ')';
    return $out;
}
function ic_class_options() {
    $out = [];
    foreach (get_posts(['post_type' => 'ic_class', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']) as $c) $out[$c->ID] = $c->post_title;
    return $out;
}

ic_metabox('ic_event_box', 'Event details', 'ic_event', [
    'ic_date' => ['Date', 'date', null],
    'ic_time' => ['Time', 'text', null],
    'ic_place' => ['Place', 'text', null],
]);
ic_metabox('ic_course_box', 'Course details', 'ic_course', [
    'ic_age' => ['Age group', 'text', null],
    'ic_schedule' => ['Days / time', 'text', null],
]);
ic_metabox('ic_review_box', 'Reviewer', 'ic_review', [
    'ic_who' => ['Name / relation (e.g. “Parent of Aisha”)', 'text', null],
]);

// Option lists come from live data, so these boxes are declared after init.
add_action('init', function () {
    if (!is_admin()) return;
    ic_metabox('ic_class_box', 'Class details', 'ic_class', [
        'ic_teachers' => ['Teachers for this class', 'multi', ic_users_by_role('ic_teacher')],
        'ic_timing' => ['Timing (e.g. Sat 10:00–12:00)', 'text', null],
        'ic_room' => ['Room', 'text', null],
    ]);
    ic_metabox('ic_student_box', 'Student record', 'ic_student', [
        'ic_class_id' => ['Class', 'select', ic_class_options()],
        'ic_parent_user' => ['Parent login', 'select', ic_users_by_role('ic_parent')],
        'ic_student_user' => ['Student login (optional)', 'select', ic_users_by_role('ic_student_user')],
        'ic_dob' => ['Date of birth', 'date', null],
        'ic_gender' => ['Gender', 'select', ['Male' => 'Male', 'Female' => 'Female']],
        'ic_parent_name' => ['Parent / guardian name', 'text', null],
        'ic_parent_email' => ['Parent email', 'email', null],
        'ic_parent_phone' => ['Parent phone', 'text', null],
        'ic_student_email' => ['Student email (optional, creates a login on approval)', 'email', null],
        'ic_address' => ['Address', 'textarea', null],
        'ic_medical' => ['Medical / allergies', 'textarea', null],
        'ic_emergency' => ['Emergency contact', 'text', null],
        'ic_admission_no' => ['Admission no.', 'text', null],
    ]);
}, 20);

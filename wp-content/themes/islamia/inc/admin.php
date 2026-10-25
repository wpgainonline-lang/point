<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page('Islamia School', 'Islamia School', 'manage_options', 'ic-school', 'ic_admin_dashboard', 'dashicons-welcome-learn-more', 26);
    add_submenu_page('ic-school', 'Overview', 'Overview', 'manage_options', 'ic-school', 'ic_admin_dashboard');
    add_submenu_page('ic-school', 'Attendance report', 'Attendance report', 'manage_options', 'ic-attendance', 'ic_admin_attendance');
    add_submenu_page('ic-school', 'Progress reports', 'Progress reports', 'manage_options', 'ic-progress', 'ic_admin_progress');
}, 5);

function ic_admin_dashboard() {
    global $wpdb;
    $counts = wp_count_posts('ic_student');
    $today = current_time('Y-m-d');
    $att = $wpdb->get_results($wpdb->prepare('SELECT status, COUNT(*) c FROM ' . ic_table('attendance') . ' WHERE att_date=%s GROUP BY status', $today), OBJECT_K);
    echo '<div class="wrap"><h1>Islamia School</h1><div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0">';
    $box = function ($n, $l, $url = '') { echo '<a href="' . esc_url($url) . '" style="background:#fff;border:1px solid #ccd0d4;border-left:4px solid #DB9E30;padding:16px 26px;text-decoration:none;color:#1d2327"><b style="font-size:28px;display:block">' . esc_html($n) . '</b>' . esc_html($l) . '</a>'; };
    $box((int) $counts->publish, 'Active students', admin_url('edit.php?post_type=ic_student'));
    $box((int) $counts->pending, 'Pending registrations', admin_url('edit.php?post_status=pending&post_type=ic_student'));
    $box(count(get_users(['role' => 'ic_teacher'])), 'Teachers', admin_url('users.php?role=ic_teacher'));
    $box(count(get_users(['role' => 'ic_parent'])), 'Parents', admin_url('users.php?role=ic_parent'));
    $box(($att['P']->c ?? 0) + ($att['L']->c ?? 0) . ' / ' . array_sum(wp_list_pluck($att, 'c')), 'Present today', admin_url('admin.php?page=ic-attendance'));
    echo '</div><h2>How to use</h2><ol>
      <li><b>Classes</b> — add classes and pick their teachers.</li>
      <li><b>Teachers</b> — Users → Add New, role “Islamia Teacher”.</li>
      <li><b>Registrations</b> — new forms arrive as <em>Pending</em> students. Open one, choose the class, press <b>Publish</b>: the parent receives an email to set their password.</li>
      <li><b>Home page text, colours, contact, social links</b> — Appearance → Customize.</li>
      <li><b>Courses, notices, events, gallery, reviews</b> — from this menu.</li></ol></div>';
}

function ic_admin_attendance() {
    global $wpdb;
    $cid = absint($_GET['class'] ?? 0);
    $from = sanitize_text_field($_GET['from'] ?? date('Y-m-01', current_time('timestamp')));
    $to = sanitize_text_field($_GET['to'] ?? current_time('Y-m-d'));
    echo '<div class="wrap"><h1>Attendance report</h1><form method="get"><input type="hidden" name="page" value="ic-attendance"> Class <select name="class"><option value="0">All</option>';
    foreach (ic_class_options() as $id => $t) printf('<option value="%d"%s>%s</option>', $id, selected($cid, $id, false), esc_html($t));
    printf('</select> From <input type="date" name="from" value="%s"> To <input type="date" name="to" value="%s"> <button class="button button-primary">Show</button></form>', esc_attr($from), esc_attr($to));
    $args = ['post_type' => 'ic_student', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC'];
    if ($cid) $args['meta_query'] = [['key' => 'ic_class_id', 'value' => (string) $cid]];
    echo '<table class="widefat striped" style="margin-top:16px"><thead><tr><th>Student</th><th>Class</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th><th>%</th></tr></thead><tbody>';
    foreach (get_posts($args) as $s) {
        $n = ic_att_summary($s->ID, $from, $to);
        echo '<tr><td>' . esc_html($s->post_title) . '</td><td>' . esc_html(ic_class_name($s->ID)) . "</td><td>{$n['P']}</td><td>{$n['L']}</td><td>{$n['A']}</td><td>{$n['E']}</td><td><b>{$n['pct']}%</b></td></tr>";
    }
    echo '</tbody></table></div>';
}

add_action('admin_post_ic_admin_del_progress', function () {
    $id = absint($_GET['id'] ?? 0);
    check_admin_referer('ic_adel_' . $id);
    if (!current_user_can('manage_options')) wp_die('Not allowed.', 403);
    global $wpdb;
    $wpdb->delete(ic_table('progress'), ['id' => $id]);
    wp_safe_redirect(admin_url('admin.php?page=ic-progress')); exit;
});

function ic_admin_progress() {
    global $wpdb;
    $rows = $wpdb->get_results('SELECT * FROM ' . ic_table('progress') . ' ORDER BY created_at DESC, id DESC LIMIT 200');
    echo '<div class="wrap"><h1>Progress reports</h1><p>Latest 200 entries. Teachers add these from the portal.</p><table class="widefat striped"><thead><tr><th>Date</th><th>Student</th><th>Term</th><th>Subject</th><th>Result</th><th>Quran</th><th>Behaviour</th><th>Teacher</th><th></th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $t = get_userdata($r->teacher_id);
        $del = wp_nonce_url(admin_url('admin-post.php?action=ic_admin_del_progress&id=' . $r->id), 'ic_adel_' . $r->id);
        echo '<tr><td>' . esc_html(mysql2date('j M Y', $r->created_at)) . '</td><td>' . esc_html(get_the_title($r->student_id)) . '</td><td>' . esc_html($r->term) . '</td><td>' . esc_html($r->subject) . '</td><td>' . esc_html($r->result) . '</td><td>' . esc_html($r->quran_progress) . '</td><td>' . esc_html($r->behaviour) . '</td><td>' . esc_html($t ? $t->display_name : '') . '</td><td><a href="' . esc_url($del) . '" onclick="return confirm(\'Delete?\')">Delete</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

/** Students list: show class + approval state columns. */
add_filter('manage_ic_student_posts_columns', function ($c) {
    return ['cb' => $c['cb'], 'title' => 'Student', 'ic_class' => 'Class', 'ic_parent' => 'Parent', 'ic_adm' => 'Admission no', 'date' => 'Registered'];
});
add_action('manage_ic_student_posts_custom_column', function ($col, $id) {
    if ($col === 'ic_class') echo esc_html(ic_class_name($id));
    if ($col === 'ic_parent') echo esc_html(get_post_meta($id, 'ic_parent_name', true)) . '<br><small>' . esc_html(get_post_meta($id, 'ic_parent_email', true)) . '</small>';
    if ($col === 'ic_adm') echo esc_html(get_post_meta($id, 'ic_admission_no', true) ?: '—');
}, 10, 2);

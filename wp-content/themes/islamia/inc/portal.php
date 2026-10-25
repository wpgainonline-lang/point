<?php
defined('ABSPATH') || exit;

/* ---------- access helpers ---------- */

function ic_all_classes() {
    return get_posts(['post_type' => 'ic_class', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
}
function ic_teacher_classes($uid = 0) {
    $uid = $uid ?: get_current_user_id();
    if (ic_user_role() === 'admin') return ic_all_classes();
    return array_values(array_filter(ic_all_classes(), function ($c) use ($uid) {
        return in_array((string) $uid, array_map('strval', (array) get_post_meta($c->ID, 'ic_teachers', true)), true);
    }));
}
function ic_class_students($class_id) {
    return get_posts(['post_type' => 'ic_student', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC',
        'meta_query' => [['key' => 'ic_class_id', 'value' => (string) $class_id]]]);
}
/** Students the current user may see. */
function ic_my_students() {
    $uid = get_current_user_id();
    $base = ['post_type' => 'ic_student', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC'];
    switch (ic_user_role()) {
        case 'admin': return get_posts($base);
        case 'parent': return get_posts($base + ['meta_query' => [['key' => 'ic_parent_user', 'value' => (string) $uid]]]);
        case 'student': return get_posts($base + ['meta_query' => [['key' => 'ic_student_user', 'value' => (string) $uid]]]);
        case 'teacher':
            $ids = array_map(function ($c) { return (string) $c->ID; }, ic_teacher_classes());
            return $ids ? get_posts($base + ['meta_query' => [['key' => 'ic_class_id', 'value' => $ids, 'compare' => 'IN']]]) : [];
    }
    return [];
}
function ic_can_view_student($sid) {
    foreach (ic_my_students() as $s) if ((int) $s->ID === (int) $sid) return true;
    return false;
}
function ic_portal_url($args = []) { return add_query_arg($args, ic_page_url('portal') ?: home_url('/')); }
function ic_back($msg, $type = 'ok', $args = []) {
    wp_safe_redirect(add_query_arg(['msg' => rawurlencode($msg), 't' => $type], $args ? ic_portal_url($args) : (wp_get_referer() ?: ic_portal_url())));
    exit;
}
function ic_require_role(array $roles) {
    if (!is_user_logged_in() || !in_array(ic_user_role(), $roles, true)) wp_die('Not allowed.', 403);
}

/* ---------- form handlers (admin-post) ---------- */

add_action('admin_post_ic_save_attendance', function () {
    check_admin_referer('ic_att');
    ic_require_role(['teacher', 'admin']);
    global $wpdb;
    $class_id = absint($_POST['class_id'] ?? 0);
    $date = sanitize_text_field(wp_unslash($_POST['att_date'] ?? ''));
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date || $date > current_time('Y-m-d')) ic_back('Please choose a valid date (not in the future).', 'err');
    if (!in_array($class_id, wp_list_pluck(ic_teacher_classes(), 'ID'), true)) wp_die('This is not your class.', 403);
    $valid = wp_list_pluck(ic_class_students($class_id), 'ID');
    $st = (array) ($_POST['status'] ?? []);
    $notes = (array) ($_POST['note'] ?? []);
    foreach ($valid as $sid) {
        $s = isset($st[$sid]) ? sanitize_text_field(wp_unslash($st[$sid])) : '';
        if (!isset(IC_STATUSES[$s])) continue;
        $wpdb->replace(ic_table('attendance'), [
            'student_id' => $sid, 'class_id' => $class_id, 'att_date' => $date, 'status' => $s,
            'note' => mb_substr(sanitize_text_field(wp_unslash($notes[$sid] ?? '')), 0, 250), 'marked_by' => get_current_user_id(),
        ]);
    }
    ic_back('Attendance saved.', 'ok', ['v' => 'attendance', 'class' => $class_id, 'date' => $date]);
});

add_action('admin_post_ic_save_progress', function () {
    check_admin_referer('ic_prog');
    ic_require_role(['teacher', 'admin']);
    global $wpdb;
    $sid = absint($_POST['student_id'] ?? 0);
    if (!ic_can_view_student($sid)) wp_die('Not allowed.', 403);
    $f = function ($k, $max = 120) { return mb_substr(sanitize_text_field(wp_unslash($_POST[$k] ?? '')), 0, $max); };
    if ($f('subject') === '' || $f('term') === '') ic_back('Term and subject are required.', 'err', ['v' => 'student', 'id' => $sid]);
    $wpdb->insert(ic_table('progress'), [
        'student_id' => $sid, 'term' => $f('term', 80), 'subject' => $f('subject'), 'result' => $f('result', 60),
        'quran_progress' => $f('quran', 160), 'behaviour' => $f('behaviour', 40),
        'comment' => sanitize_textarea_field(wp_unslash($_POST['comment'] ?? '')),
        'teacher_id' => get_current_user_id(), 'created_at' => current_time('mysql'),
    ]);
    ic_back('Progress report added.', 'ok', ['v' => 'student', 'id' => $sid]);
});

add_action('admin_post_ic_delete_progress', function () {
    $id = absint($_GET['id'] ?? 0);
    check_admin_referer('ic_del_' . $id);
    ic_require_role(['teacher', 'admin']);
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ic_table('progress') . ' WHERE id=%d', $id));
    if (!$row || !ic_can_view_student($row->student_id)) wp_die('Not allowed.', 403);
    if (ic_user_role() !== 'admin' && (int) $row->teacher_id !== get_current_user_id()) wp_die('You can only delete your own entries.', 403);
    $wpdb->delete(ic_table('progress'), ['id' => $id]);
    ic_back('Entry deleted.', 'ok', ['v' => 'student', 'id' => $row->student_id]);
});

/** Public registration → pending student; accounts are created when the admin approves. */
function ic_handle_register() {
    check_admin_referer('ic_reg');
    $back = wp_get_referer() ?: home_url('/');
    if (!empty($_POST['website'])) { wp_safe_redirect($back); exit; } // honeypot
    $g = function ($k, $max = 200) { return mb_substr(sanitize_text_field(wp_unslash($_POST[$k] ?? '')), 0, $max); };
    $name = $g('child_name'); $pemail = sanitize_email(wp_unslash($_POST['parent_email'] ?? '')); $pname = $g('parent_name');
    if ($name === '' || $pname === '' || !is_email($pemail) || empty($_POST['consent'])) {
        wp_safe_redirect(add_query_arg('reg', 'err', $back)); exit;
    }
    // Throttle: 5 submissions / hour / IP.
    $key = 'ic_reg_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
    $n = (int) get_transient($key);
    if ($n >= 5) { wp_safe_redirect(add_query_arg('reg', 'limit', $back)); exit; }
    set_transient($key, $n + 1, HOUR_IN_SECONDS);

    $id = wp_insert_post(['post_type' => 'ic_student', 'post_title' => $name, 'post_status' => 'pending']);
    if (is_wp_error($id) || !$id) { wp_safe_redirect(add_query_arg('reg', 'err', $back)); exit; }
    $meta = [
        'ic_dob' => $g('dob', 10), 'ic_gender' => $g('gender', 10), 'ic_parent_name' => $pname, 'ic_parent_email' => $pemail,
        'ic_parent_phone' => $g('parent_phone', 40), 'ic_student_email' => sanitize_email(wp_unslash($_POST['student_email'] ?? '')),
        'ic_address' => sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')),
        'ic_medical' => sanitize_textarea_field(wp_unslash($_POST['medical'] ?? '')),
        'ic_emergency' => $g('emergency', 120), 'ic_class_id' => absint($_POST['class_id'] ?? 0) ?: '',
    ];
    foreach ($meta as $k => $v) update_post_meta($id, $k, $v);
    wp_mail(get_option('admin_email'), 'New student registration: ' . $name, "A new registration is waiting for approval.\n\n" . admin_url('post.php?post=' . $id . '&action=edit'));
    wp_safe_redirect(add_query_arg('reg', 'ok', $back)); exit;
}
add_action('admin_post_nopriv_ic_register', 'ic_handle_register');
add_action('admin_post_ic_register', 'ic_handle_register');

/** When the admin publishes a pending registration: number it, create parent (+ optional student) logins. */
add_action('transition_post_status', function ($new, $old, $post) {
    if ($post->post_type !== 'ic_student' || $new !== 'publish' || $old === 'publish') return;
    $id = $post->ID;
    if (!get_post_meta($id, 'ic_admission_no', true)) update_post_meta($id, 'ic_admission_no', 'IIC-' . date('y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT));
    $make = function ($email, $name, $role) {
        if (!is_email($email)) return 0;
        if ($u = get_user_by('email', $email)) return $u->ID;
        $login = sanitize_user(strstr($email, '@', true), true) ?: 'user';
        $base = $login; $i = 1;
        while (username_exists($login)) $login = $base . (++$i);
        $uid = wp_insert_user(['user_login' => $login, 'user_email' => $email, 'display_name' => $name, 'first_name' => $name, 'user_pass' => wp_generate_password(20), 'role' => $role]);
        if (is_wp_error($uid)) return 0;
        wp_new_user_notification($uid, null, 'user'); // emails a "set your password" link
        return $uid;
    };
    if (!get_post_meta($id, 'ic_parent_user', true) && ($uid = $make(get_post_meta($id, 'ic_parent_email', true), get_post_meta($id, 'ic_parent_name', true), 'ic_parent'))) update_post_meta($id, 'ic_parent_user', (string) $uid);
    if (!get_post_meta($id, 'ic_student_user', true) && ($uid = $make(get_post_meta($id, 'ic_student_email', true), $post->post_title, 'ic_student_user'))) update_post_meta($id, 'ic_student_user', (string) $uid);
}, 10, 3);

/* ---------- login failure back to portal ---------- */
add_action('wp_login_failed', function () {
    $ref = wp_get_referer();
    if ($ref && !str_contains($ref, 'wp-login') && !str_contains($ref, 'wp-admin')) { wp_safe_redirect(add_query_arg('login', 'failed', strtok($ref, '?'))); exit; }
});
add_filter('authenticate', function ($user, $u, $p) {
    $ref = wp_get_referer();
    if ($ref && (empty($u) || empty($p)) && !str_contains($ref, 'wp-login') && isset($_POST['log'])) { wp_safe_redirect(add_query_arg('login', 'failed', strtok($ref, '?'))); exit; }
    return $user;
}, 30, 3);

/* ---------- portal rendering ---------- */

function ic_status_pill($s) { return '<span class="pill ' . esc_attr($s) . '">' . esc_html(IC_STATUSES[$s] ?? $s) . '</span>'; }
function ic_class_name($sid) { $c = get_post_meta($sid, 'ic_class_id', true); return $c ? get_the_title($c) : '—'; }

function ic_render_portal() {
    if (!is_user_logged_in()) { ic_render_login(); return; }
    $role = ic_user_role();
    if (!$role) { echo '<div class="notice err">Your account has no portal access. Please contact the school office.</div>'; return; }
    $view = sanitize_key($_GET['v'] ?? 'home');
    $u = wp_get_current_user();
    $nav = ['home' => 'Dashboard'];
    if (in_array($role, ['teacher', 'admin'], true)) $nav += ['attendance' => 'Take attendance', 'students' => 'My students'];
    if ($role === 'parent') $nav += ['students' => 'My children'];
    if ($role === 'student') $nav += ['students' => 'My progress'];
    $nav['notices'] = 'Notices';
    echo '<div class="portal"><aside class="portal-side"><div class="who"><b>' . esc_html($u->display_name) . '</b><span>' . esc_html($role) . '</span></div>';
    foreach ($nav as $k => $l) printf('<a href="%s" class="%s">%s</a>', esc_url(ic_portal_url(['v' => $k])), $view === $k || ($view === 'student' && $k === 'students') ? 'on' : '', esc_html($l));
    if ($role === 'admin') echo '<a href="' . esc_url(admin_url('admin.php?page=ic-school')) . '">Admin dashboard</a>';
    echo '<a href="' . esc_url(wp_logout_url(home_url('/'))) . '">Log out</a></aside><div class="portal-main">';
    if (!empty($_GET['msg'])) printf('<div class="notice %s">%s</div>', ($_GET['t'] ?? '') === 'err' ? 'err' : 'ok', esc_html(sanitize_text_field(rawurldecode(wp_unslash($_GET['msg'])))));
    switch ($view) {
        case 'attendance': in_array($role, ['teacher', 'admin'], true) ? ic_view_attendance() : print('<div class="notice err">Not allowed.</div>'); break;
        case 'students': ic_view_students(); break;
        case 'student': ic_view_student(absint($_GET['id'] ?? 0)); break;
        case 'notices': ic_view_notices(); break;
        default: ic_view_home($role);
    }
    echo '</div></div>';
}

function ic_render_login() {
    echo '<div class="login-box panel"><h2>Portal login</h2><p class="meta">For parents, students and teachers.</p>';
    if (isset($_GET['login'])) echo '<div class="notice err">Incorrect username/email or password.</div>';
    wp_login_form(['redirect' => ic_portal_url(), 'label_username' => 'Email or username', 'label_log_in' => 'Log in', 'remember' => true]);
    echo '<p style="margin-top:14px"><a href="' . esc_url(wp_lostpassword_url(ic_portal_url())) . '">Forgot password?</a> · <a href="' . esc_url(ic_page_url('register')) . '">Register a student</a></p></div>';
}

function ic_student_card($s) {
    $sum = ic_att_summary($s->ID);
    printf('<div class="card"><h3>%s</h3><p class="meta">Class: %s<br>Admission no: %s</p><p>Attendance: <b>%d%%</b></p><div class="bar"><i style="width:%d%%"></i></div><p style="margin-top:14px"><a class="btn small green" href="%s">Open</a></p></div>',
        esc_html($s->post_title), esc_html(ic_class_name($s->ID)), esc_html(get_post_meta($s->ID, 'ic_admission_no', true) ?: '—'), $sum['pct'], $sum['pct'], esc_url(ic_portal_url(['v' => 'student', 'id' => $s->ID])));
}

function ic_view_home($role) {
    $students = ic_my_students();
    echo '<div class="panel"><h2>Assalamu Alaikum, ' . esc_html(wp_get_current_user()->display_name) . '</h2>';
    if ($role === 'teacher' || $role === 'admin') {
        $cl = ic_teacher_classes();
        printf('<div class="kpi"><div><b>%d</b>Classes</div><div><b>%d</b>Students</div></div>', count($cl), count($students));
        echo '<p><a class="btn green" href="' . esc_url(ic_portal_url(['v' => 'attendance'])) . '">Take today’s attendance</a></p>';
    } else {
        echo $students ? '' : '<p>No student is linked to your account yet. Please contact the school office.</p>';
    }
    echo '</div>';
    if ($role === 'parent' || $role === 'student') {
        echo '<div class="grid g2">';
        foreach ($students as $s) ic_student_card($s);
        echo '</div>';
    }
    echo '<div class="panel"><h2>Latest notices</h2>';
    ic_notice_list(4);
    echo '</div>';
}

function ic_notice_list($n) {
    $q = new WP_Query(['post_type' => 'ic_notice', 'posts_per_page' => $n]);
    if (!$q->have_posts()) { echo '<p class="meta">No notices yet.</p>'; return; }
    echo '<ul class="timeline">';
    while ($q->have_posts()) { $q->the_post(); echo '<li><b><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></b><br><span class="meta">' . esc_html(get_the_date()) . '</span><br>' . esc_html(wp_trim_words(get_the_excerpt(), 28)) . '</li>'; }
    echo '</ul>'; wp_reset_postdata();
}
function ic_view_notices() { echo '<div class="panel"><h2>Notices</h2>'; ic_notice_list(20); echo '</div>'; }

function ic_view_students() {
    $students = ic_my_students();
    echo '<div class="panel"><h2>' . (ic_user_role() === 'parent' ? 'My children' : (ic_user_role() === 'student' ? 'My progress' : 'My students')) . '</h2>';
    if (!$students) { echo '<p>No students found.</p></div>'; return; }
    if (in_array(ic_user_role(), ['parent', 'student'], true)) { echo '<div class="grid g2">'; foreach ($students as $s) ic_student_card($s); echo '</div></div>'; return; }
    echo '<div class="tbl-wrap"><table class="tbl"><tr><th>Name</th><th>Class</th><th>Attendance</th><th></th></tr>';
    foreach ($students as $s) printf('<tr><td>%s</td><td>%s</td><td>%d%%</td><td><a class="btn small green" href="%s">Open</a></td></tr>', esc_html($s->post_title), esc_html(ic_class_name($s->ID)), ic_att_summary($s->ID)['pct'], esc_url(ic_portal_url(['v' => 'student', 'id' => $s->ID])));
    echo '</table></div></div>';
}

function ic_view_student($sid) {
    if (!$sid || !ic_can_view_student($sid)) { echo '<div class="notice err">Student not found.</div>'; return; }
    $s = get_post($sid); $role = ic_user_role(); $staff = in_array($role, ['teacher', 'admin'], true);
    $sum = ic_att_summary($sid);
    echo '<div class="panel"><h2>' . esc_html($s->post_title) . '</h2><p class="meta">Class: ' . esc_html(ic_class_name($sid)) . ' · Admission no: ' . esc_html(get_post_meta($sid, 'ic_admission_no', true) ?: '—');
    if ($dob = get_post_meta($sid, 'ic_dob', true)) echo ' · DOB: ' . esc_html($dob);
    echo '</p>';
    if ($staff) {
        printf('<p class="meta">Parent: %s · %s · %s</p>', esc_html(get_post_meta($sid, 'ic_parent_name', true)), esc_html(get_post_meta($sid, 'ic_parent_phone', true)), esc_html(get_post_meta($sid, 'ic_parent_email', true)));
        if ($m = get_post_meta($sid, 'ic_medical', true)) echo '<p><b>Medical:</b> ' . esc_html($m) . '</p>';
    }
    printf('<div class="kpi"><div><b>%d%%</b>Attendance</div><div><b>%d</b>Present</div><div><b>%d</b>Late</div><div><b>%d</b>Absent</div></div><div class="bar"><i style="width:%d%%"></i></div></div>', $sum['pct'], $sum['P'], $sum['L'], $sum['A'], $sum['pct']);

    echo '<div class="panel"><h2>Progress reports</h2>';
    $rows = ic_progress_rows($sid);
    if ($rows) {
        echo '<div class="tbl-wrap"><table class="tbl"><tr><th>Term</th><th>Subject</th><th>Result</th><th>Quran</th><th>Behaviour</th><th>Teacher comment</th>' . ($staff ? '<th></th>' : '') . '</tr>';
        foreach ($rows as $r) {
            $t = get_userdata($r->teacher_id);
            echo '<tr><td>' . esc_html($r->term) . '<br><span class="meta">' . esc_html(mysql2date('j M Y', $r->created_at)) . '</span></td><td>' . esc_html($r->subject) . '</td><td>' . esc_html($r->result) . '</td><td>' . esc_html($r->quran_progress) . '</td><td>' . esc_html($r->behaviour) . '</td><td>' . nl2br(esc_html($r->comment)) . '<br><span class="meta">' . esc_html($t ? $t->display_name : '') . '</span></td>';
            if ($staff && (ic_user_role() === 'admin' || (int) $r->teacher_id === get_current_user_id())) {
                $del = wp_nonce_url(admin_url('admin-post.php?action=ic_delete_progress&id=' . $r->id), 'ic_del_' . $r->id);
                echo '<td><a href="' . esc_url($del) . '" onclick="return confirm(\'Delete this entry?\')">Delete</a></td>';
            } elseif ($staff) echo '<td></td>';
            echo '</tr>';
        }
        echo '</table></div>';
    } else echo '<p class="meta">No reports yet.</p>';
    echo '</div>';

    if ($staff) {
        echo '<div class="panel"><h2>Add progress entry</h2><form class="form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ic_save_progress"><input type="hidden" name="student_id" value="' . (int) $sid . '">';
        wp_nonce_field('ic_prog');
        echo '<div class="row"><div class="field"><label>Term *</label><input type="text" name="term" placeholder="e.g. Autumn 2026" required></div><div class="field"><label>Subject *</label><input type="text" name="subject" placeholder="e.g. Quran, Tajweed, Islamic Studies" required></div></div>
        <div class="row"><div class="field"><label>Result / grade</label><input type="text" name="result" placeholder="e.g. A, 85/100"></div><div class="field"><label>Quran progress</label><input type="text" name="quran" placeholder="e.g. Surah Al-Mulk, 10 ayah"></div></div>
        <div class="field"><label>Behaviour</label><select name="behaviour"><option>Excellent</option><option>Very good</option><option>Good</option><option>Needs improvement</option></select></div>
        <div class="field"><label>Teacher comment</label><textarea name="comment" rows="3"></textarea></div><button class="btn green">Save entry</button></form></div>';
    }

    echo '<div class="panel"><h2>Attendance &amp; activity</h2><div class="tbl-wrap"><table class="tbl"><tr><th>Date</th><th>Status</th><th>Note</th></tr>';
    $att = ic_att_rows($sid, 40);
    if (!$att) echo '<tr><td colspan="3" class="meta">No attendance recorded yet.</td></tr>';
    foreach ($att as $a) echo '<tr><td>' . esc_html(mysql2date('D j M Y', $a->att_date)) . '</td><td>' . ic_status_pill($a->status) . '</td><td>' . esc_html($a->note) . '</td></tr>';
    echo '</table></div></div>';
}

function ic_view_attendance() {
    $classes = ic_teacher_classes();
    echo '<div class="panel"><h2>Take attendance</h2>';
    if (!$classes) { echo '<p>No class is assigned to you yet. Ask the admin to add you to a class.</p></div>'; return; }
    $cid = absint($_GET['class'] ?? $classes[0]->ID);
    $date = sanitize_text_field($_GET['date'] ?? current_time('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = current_time('Y-m-d');
    if (!in_array($cid, wp_list_pluck($classes, 'ID'), true)) $cid = $classes[0]->ID;
    echo '<form method="get" class="row"><input type="hidden" name="page_id" value="' . (int) get_queried_object_id() . '"><input type="hidden" name="v" value="attendance"><div class="field"><label>Class</label><select name="class" onchange="this.form.submit()">';
    foreach ($classes as $c) printf('<option value="%d"%s>%s</option>', $c->ID, selected($cid, $c->ID, false), esc_html($c->post_title));
    echo '</select></div><div class="field"><label>Date</label><input type="date" name="date" value="' . esc_attr($date) . '" max="' . esc_attr(current_time('Y-m-d')) . '" onchange="this.form.submit()"></div></form>';
    $students = ic_class_students($cid);
    if (!$students) { echo '<p>No students in this class yet.</p></div>'; return; }
    global $wpdb;
    $existing = $wpdb->get_results($wpdb->prepare('SELECT student_id,status,note FROM ' . ic_table('attendance') . ' WHERE class_id=%d AND att_date=%s', $cid, $date), OBJECT_K);
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ic_save_attendance"><input type="hidden" name="class_id" value="' . (int) $cid . '"><input type="hidden" name="att_date" value="' . esc_attr($date) . '">';
    wp_nonce_field('ic_att');
    echo '<p><button type="button" class="btn small ghost" style="color:var(--ic-primary);border-color:var(--ic-primary)" data-mark-all="P">Mark all present</button></p><div class="tbl-wrap"><table class="tbl"><tr><th>Student</th><th>Status</th><th>Note</th></tr>';
    foreach ($students as $s) {
        $cur = $existing[$s->ID]->status ?? 'P';
        echo '<tr><td>' . esc_html($s->post_title) . '</td><td class="att-radios">';
        foreach (IC_STATUSES as $k => $l) printf('<label><input type="radio" name="status[%d]" value="%s"%s> %s</label>', $s->ID, $k, checked($cur, $k, false), esc_html($l));
        printf('</td><td><input type="text" name="note[%d]" value="%s" maxlength="250"></td></tr>', $s->ID, esc_attr($existing[$s->ID]->note ?? ''));
    }
    echo '</table></div><p style="margin-top:16px"><button class="btn green">Save attendance</button></p></form></div>';
}

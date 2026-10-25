<?php
defined('ABSPATH') || exit;

const IC_DB_VER = '1';

function ic_table($name) { global $wpdb; return $wpdb->prefix . 'ic_' . $name; }

function ic_install_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $cs = $wpdb->get_charset_collate();
    $a = ic_table('attendance');
    $p = ic_table('progress');
    dbDelta("CREATE TABLE $a (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        student_id bigint(20) unsigned NOT NULL,
        class_id bigint(20) unsigned NOT NULL,
        att_date date NOT NULL,
        status char(1) NOT NULL DEFAULT 'P',
        note varchar(255) NOT NULL DEFAULT '',
        marked_by bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY student_date (student_id,att_date),
        KEY class_date (class_id,att_date)
    ) $cs;");
    dbDelta("CREATE TABLE $p (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        student_id bigint(20) unsigned NOT NULL,
        term varchar(80) NOT NULL DEFAULT '',
        subject varchar(120) NOT NULL DEFAULT '',
        result varchar(60) NOT NULL DEFAULT '',
        quran_progress varchar(160) NOT NULL DEFAULT '',
        behaviour varchar(40) NOT NULL DEFAULT '',
        comment text NOT NULL,
        teacher_id bigint(20) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY student_id (student_id)
    ) $cs;");
    update_option('ic_db_ver', IC_DB_VER);
}
add_action('init', function () {
    if (get_option('ic_db_ver') !== IC_DB_VER) ic_install_tables();
});

const IC_STATUSES = ['P' => 'Present', 'A' => 'Absent', 'L' => 'Late', 'E' => 'Excused'];

/** Attendance totals for one student → [P,A,L,E,total,pct]. Late counts as attended; excused is ignored. */
function ic_att_summary($student_id, $from = '', $to = '') {
    global $wpdb;
    $t = ic_table('attendance');
    $sql = "SELECT status, COUNT(*) c FROM $t WHERE student_id=%d";
    $args = [$student_id];
    if ($from) { $sql .= ' AND att_date>=%s'; $args[] = $from; }
    if ($to) { $sql .= ' AND att_date<=%s'; $args[] = $to; }
    $rows = $wpdb->get_results($wpdb->prepare($sql . ' GROUP BY status', $args), OBJECT_K);
    $n = ['P' => 0, 'A' => 0, 'L' => 0, 'E' => 0];
    foreach ($rows as $s => $r) if (isset($n[$s])) $n[$s] = (int) $r->c;
    $den = $n['P'] + $n['A'] + $n['L'];
    $n['total'] = array_sum([$n['P'], $n['A'], $n['L'], $n['E']]);
    $n['pct'] = $den ? round(($n['P'] + $n['L']) / $den * 100) : 0;
    return $n;
}
function ic_att_rows($student_id, $limit = 30) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ic_table('attendance') . ' WHERE student_id=%d ORDER BY att_date DESC LIMIT %d', $student_id, $limit));
}
function ic_progress_rows($student_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ic_table('progress') . ' WHERE student_id=%d ORDER BY created_at DESC, id DESC', $student_id));
}

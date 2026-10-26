<?php
/**
 * Plugin Name: Islamia Connector (Claude)
 * Description: Secure REST API so Claude can read/update this site's theme files, pages, posts and content remotely. Scoped to the Islamia theme and normal WordPress content — never the server or other files.
 * Version: 1.0.0
 * Author: Islamia Islamic Centre
 */
defined('ABSPATH') || exit;

define('IMC_NS', 'islamia-connector/v1');
define('IMC_OPT_KEY', 'imc_secret_key');
define('IMC_OPT_ENABLED', 'imc_enabled');
/** The ONLY folder this plugin may read/write files in — never wp-config.php, plugins, or anything outside this. */
define('IMC_ROOT', get_theme_root() . '/islamia');

/* ---------------- settings page: view / regenerate key, enable / disable ---------------- */

add_action('admin_menu', function () {
    add_options_page('Islamia Connector', 'Islamia Connector', 'manage_options', 'islamia-connector', 'imc_settings_page');
});

function imc_settings_page() {
    if (!current_user_can('manage_options')) wp_die('Not allowed.');
    if (isset($_POST['imc_action']) && check_admin_referer('imc_settings')) {
        if ($_POST['imc_action'] === 'regenerate') update_option(IMC_OPT_KEY, wp_generate_password(40, false));
        if ($_POST['imc_action'] === 'toggle') update_option(IMC_OPT_ENABLED, !get_option(IMC_OPT_ENABLED) ? 1 : 0);
        echo '<script>location.href=location.href;</script>';
    }
    if (!get_option(IMC_OPT_KEY)) update_option(IMC_OPT_KEY, wp_generate_password(40, false));
    $enabled = (bool) get_option(IMC_OPT_ENABLED, 1);
    $key = get_option(IMC_OPT_KEY);
    $endpoint = esc_url(rest_url(IMC_NS));
    ?>
    <div class="wrap"><h1>Islamia Connector (Claude)</h1>
      <p>This lets Claude read/update <b>only</b> this theme's files and your pages, posts, courses, notices, events, gallery and reviews — never the server, plugins or other files.</p>
      <table class="form-table">
        <tr><th>Status</th><td><b style="color:<?php echo $enabled ? 'green' : '#b3261e'; ?>"><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></b></td></tr>
        <tr><th>Endpoint URL</th><td><code><?php echo $endpoint; ?></code></td></tr>
        <tr><th>Secret Key</th><td><code id="imc-key"><?php echo esc_html($key); ?></code> <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('imc-key').textContent)">Copy</button>
          <p class="description">Give the Endpoint URL and this key to Claude / your Claude workspace admin when adding a custom connector. Keep it secret — anyone with this key can edit the theme and content.</p></td></tr>
      </table>
      <form method="post">
        <?php wp_nonce_field('imc_settings'); ?>
        <input type="hidden" name="imc_action" value="regenerate">
        <button class="button button-primary" onclick="return confirm('Old key will stop working immediately. Continue?')">Regenerate key</button>
      </form>
      <form method="post" style="margin-top:10px">
        <?php wp_nonce_field('imc_settings'); ?>
        <input type="hidden" name="imc_action" value="toggle">
        <button class="button"><?php echo $enabled ? 'Disable connector' : 'Enable connector'; ?></button>
      </form>
    </div>
    <?php
}

/* ---------------- auth ---------------- */

function imc_check_auth(WP_REST_Request $req) {
    if (!get_option(IMC_OPT_ENABLED, 1)) return new WP_Error('imc_disabled', 'Connector is disabled.', ['status' => 403]);
    $given = $req->get_header('x-connector-key') ?: $req->get_param('key');
    $real = get_option(IMC_OPT_KEY);
    if (!$given || !$real || !hash_equals((string) $real, (string) $given)) {
        return new WP_Error('imc_unauthorized', 'Invalid or missing connector key.', ['status' => 401]);
    }
    return true;
}

/** Resolve a relative path safely inside IMC_ROOT. Returns real path or WP_Error. */
function imc_safe_path($rel) {
    $rel = ltrim((string) $rel, '/');
    $full = realpath(IMC_ROOT . '/' . $rel);
    $root = realpath(IMC_ROOT);
    if ($rel === '' || $full === false || $root === false || strpos($full, $root) !== 0) {
        return new WP_Error('imc_bad_path', 'Path is outside the allowed theme folder.', ['status' => 400]);
    }
    return $full;
}
/** Same, but for a path that may not exist yet (new file) — validates the parent dir instead. */
function imc_safe_new_path($rel) {
    $rel = ltrim((string) $rel, '/');
    $root = realpath(IMC_ROOT);
    $parent = realpath(IMC_ROOT . '/' . dirname($rel));
    if ($rel === '' || $parent === false || $root === false || strpos($parent, $root) !== 0 || strpos($rel, '..') !== false) {
        return new WP_Error('imc_bad_path', 'Path is outside the allowed theme folder.', ['status' => 400]);
    }
    return $parent . '/' . basename($rel);
}

const IMC_TEXT_EXT = ['php', 'css', 'js', 'txt', 'md', 'json', 'html'];

/* ---------------- routes ---------------- */

add_action('rest_api_init', function () {
    $auth = 'imc_check_auth';

    register_rest_route(IMC_NS, '/site-info', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function () {
        return [
            'site_url' => site_url(), 'home_url' => home_url(), 'wp_version' => get_bloginfo('version'),
            'active_theme' => get_option('stylesheet'), 'theme_root' => IMC_ROOT,
            'post_types' => array_values(get_post_types(['public' => true])) + array_values(get_post_types(['_builtin' => false])),
        ];
    }]);

    register_rest_route(IMC_NS, '/files', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $rel = $r->get_param('path') ?: '';
        $dir = imc_safe_path($rel ?: '.');
        if (is_wp_error($dir)) return $dir;
        if (!is_dir($dir)) return new WP_Error('imc_not_dir', 'Not a directory.', ['status' => 400]);
        $out = [];
        foreach (new DirectoryIterator($dir) as $f) {
            if ($f->isDot()) continue;
            $out[] = ['name' => $f->getFilename(), 'type' => $f->isDir() ? 'dir' : 'file', 'size' => $f->isFile() ? $f->getSize() : null];
        }
        return $out;
    }]);

    register_rest_route(IMC_NS, '/file', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $full = imc_safe_path($r->get_param('path'));
        if (is_wp_error($full)) return $full;
        if (!is_file($full)) return new WP_Error('imc_not_found', 'File not found.', ['status' => 404]);
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if (!in_array($ext, IMC_TEXT_EXT, true)) return new WP_Error('imc_bad_type', 'Only text/code files can be read.', ['status' => 400]);
        return ['path' => $r->get_param('path'), 'content' => file_get_contents($full)];
    }]);

    register_rest_route(IMC_NS, '/file', ['methods' => 'POST', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $rel = (string) $r->get_param('path');
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
        if (!in_array($ext, IMC_TEXT_EXT, true)) return new WP_Error('imc_bad_type', 'Only text/code files can be written.', ['status' => 400]);
        $existing = imc_safe_path($rel);
        $target = is_wp_error($existing) ? imc_safe_new_path($rel) : $existing;
        if (is_wp_error($target)) return $target;
        $content = $r->get_param('content');
        if ($content === null) return new WP_Error('imc_missing_content', 'content is required.', ['status' => 400]);
        if (is_file($target)) {
            $bdir = WP_CONTENT_DIR . '/uploads/islamia-connector-backups';
            wp_mkdir_p($bdir);
            copy($target, $bdir . '/' . date('Y-m-d_His') . '_' . sanitize_file_name(basename($target)) . '.bak');
        } else {
            wp_mkdir_p(dirname($target));
        }
        if (strpos($ext, 'php') === 0 ) {
            $tmp = $target . '.imc_check.php';
            file_put_contents($tmp, $content);
            $out = []; $ret = 0;
            exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $ret);
            @unlink($tmp);
            if ($ret !== 0) return new WP_Error('imc_php_error', 'PHP syntax error — file not saved: ' . implode("\n", $out), ['status' => 400]);
        }
        file_put_contents($target, $content);
        return ['ok' => true, 'path' => $rel];
    }]);

    register_rest_route(IMC_NS, '/posts', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $q = get_posts([
            'post_type' => $r->get_param('type') ?: 'post', 'post_status' => $r->get_param('status') ?: 'any',
            's' => $r->get_param('search') ?: '', 'numberposts' => min(absint($r->get_param('per_page') ?: 20), 100),
        ]);
        return array_map(function ($p) { return ['id' => $p->ID, 'title' => $p->post_title, 'status' => $p->post_status, 'type' => $p->post_type, 'date' => $p->post_date]; }, $q);
    }]);

    register_rest_route(IMC_NS, '/post/(?P<id>\d+)', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $p = get_post((int) $r['id']);
        if (!$p) return new WP_Error('imc_not_found', 'Post not found.', ['status' => 404]);
        return ['id' => $p->ID, 'title' => $p->post_title, 'content' => $p->post_content, 'excerpt' => $p->post_excerpt, 'status' => $p->post_status, 'type' => $p->post_type, 'meta' => get_post_meta($p->ID)];
    }]);

    register_rest_route(IMC_NS, '/post', ['methods' => 'POST', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $id = absint($r->get_param('id'));
        $data = ['post_title' => $r->get_param('title'), 'post_content' => $r->get_param('content'), 'post_excerpt' => $r->get_param('excerpt'), 'post_status' => $r->get_param('status') ?: 'publish'];
        $data = array_filter($data, function ($v) { return $v !== null; });
        if ($id) { $data['ID'] = $id; $res = wp_update_post($data, true); }
        else { $data['post_type'] = $r->get_param('type') ?: 'post'; $res = wp_insert_post($data, true); }
        if (is_wp_error($res)) return $res;
        foreach ((array) $r->get_param('meta') as $k => $v) update_post_meta($res, sanitize_key($k), $v);
        return ['ok' => true, 'id' => $res];
    }]);

    register_rest_route(IMC_NS, '/post/(?P<id>\d+)', ['methods' => 'DELETE', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        $res = wp_trash_post((int) $r['id']);
        return $res ? ['ok' => true] : new WP_Error('imc_delete_failed', 'Could not delete.', ['status' => 400]);
    }]);

    register_rest_route(IMC_NS, '/theme-mods', ['methods' => 'GET', 'permission_callback' => $auth, 'callback' => function () {
        return get_theme_mods();
    }]);
    register_rest_route(IMC_NS, '/theme-mods', ['methods' => 'POST', 'permission_callback' => $auth, 'callback' => function (WP_REST_Request $r) {
        foreach ((array) $r->get_param('mods') as $k => $v) set_theme_mod(sanitize_key($k), $v);
        return ['ok' => true];
    }]);
});

<?php
/**
 * Islamia Islamic Centre — theme bootstrap.
 * Everything (content types, roles, attendance, reports, portal) lives in this theme: no plugins needed.
 */
defined('ABSPATH') || exit;

define('IC_VER', '1.0.0');
define('IC_DIR', get_template_directory());
define('IC_URI', get_template_directory_uri());

foreach (['setup', 'customizer', 'content', 'roles', 'database', 'metaboxes', 'portal', 'admin', 'pages'] as $ic_file) {
    require_once IC_DIR . "/inc/{$ic_file}.php";
}

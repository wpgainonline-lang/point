<?php
/**
 * Plugin Name: Primecutss MCP
 * Description: Exposes an MCP (Model Context Protocol) endpoint so an AI assistant can manage this WordPress site: content, menus, CSS, options, plugins, themes and files.
 * Version: 0.5.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Primecutss_MCP {

	const OPT_TOKEN = 'pcmcp_token_hash';
	const OPT_FILES = 'pcmcp_allow_files';
	const OPT_LOG   = 'pcmcp_log';

	/** Options the assistant may never read or change. */
	const BLOCKED_OPTIONS = array( 'siteurl', 'home', 'admin_email', 'active_plugins', 'users_can_register', 'default_role', 'auth_key', 'secure_auth_key', 'logged_in_key', 'nonce_key', self::OPT_TOKEN, self::OPT_FILES );

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Transport + auth                                                    */
	/* ------------------------------------------------------------------ */

	public static function register_route() {
		register_rest_route( 'pcmcp/v1', '/mcp', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle' ),
			'permission_callback' => array( __CLASS__, 'authorized' ),
		) );
	}

	public static function authorized( WP_REST_Request $req ) {
		$token = '';
		$auth  = $req->get_header( 'authorization' );
		if ( $auth && 0 === stripos( $auth, 'Bearer ' ) ) {
			$token = trim( substr( $auth, 7 ) );
		}
		if ( ! $token ) {
			$token = (string) $req->get_header( 'x-pcmcp-token' );
		}
		$hash = get_option( self::OPT_TOKEN );
		if ( $token && $hash && hash_equals( $hash, hash( 'sha256', $token ) ) ) {
			// Act as the first administrator so capability checks inside WP APIs pass.
			$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
			if ( $admins ) {
				wp_set_current_user( (int) $admins[0] );
				return true;
			}
		}
		// Fallback: application-password / cookie auth by an admin.
		return current_user_can( 'manage_options' );
	}

	public static function handle( WP_REST_Request $req ) {
		$msg = $req->get_json_params();
		if ( ! is_array( $msg ) || ! isset( $msg['method'] ) ) {
			return self::rpc_error( null, -32600, 'Invalid request' );
		}
		$id = $msg['id'] ?? null;

		switch ( $msg['method'] ) {
			case 'initialize':
				return self::rpc_result( $id, array(
					'protocolVersion' => '2025-03-26',
					'capabilities'    => array( 'tools' => new stdClass() ),
					'serverInfo'      => array( 'name' => 'primecutss-mcp', 'version' => '0.5.0' ),
					'instructions'    => 'WordPress site ' . home_url() . '. Tools edit the LIVE site. File paths are relative to wp-content. File writes are backed up; use list_backups/restore_backup to undo.',
				) );
			case 'ping':
				return self::rpc_result( $id, new stdClass() );
			case 'tools/list':
				return self::rpc_result( $id, array( 'tools' => self::tool_defs() ) );
			case 'tools/call':
				$name = $msg['params']['name'] ?? '';
				$args = $msg['params']['arguments'] ?? array();
				$defs = self::tool_defs();
				if ( ! in_array( $name, array_column( $defs, 'name' ), true ) ) {
					return self::rpc_error( $id, -32602, 'Unknown tool: ' . $name );
				}
				try {
					$out = call_user_func( array( __CLASS__, 'tool_' . $name ), (array) $args );
					self::log( $name, $args );
					return self::rpc_result( $id, array(
						'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) ),
					) );
				} catch ( Throwable $e ) {
					return self::rpc_result( $id, array(
						'isError' => true,
						'content' => array( array( 'type' => 'text', 'text' => $e->getMessage() ) ),
					) );
				}
			default:
				// Notifications (no id) get an empty 202.
				if ( null === $id ) {
					return new WP_REST_Response( null, 202 );
				}
				return self::rpc_error( $id, -32601, 'Method not found' );
		}
	}

	private static function rpc_result( $id, $result ) {
		return new WP_REST_Response( array( 'jsonrpc' => '2.0', 'id' => $id, 'result' => $result ) );
	}

	private static function rpc_error( $id, $code, $message ) {
		return new WP_REST_Response( array( 'jsonrpc' => '2.0', 'id' => $id, 'error' => array( 'code' => $code, 'message' => $message ) ) );
	}

	private static function log( $tool, $args ) {
		$log   = get_option( self::OPT_LOG, array() );
		$log[] = array( 'time' => gmdate( 'c' ), 'tool' => $tool, 'args' => mb_substr( wp_json_encode( $args ), 0, 300 ) );
		update_option( self::OPT_LOG, array_slice( $log, -100 ), false );
	}

	/* ------------------------------------------------------------------ */
	/* Tool definitions                                                    */
	/* ------------------------------------------------------------------ */

	private static function t( $name, $desc, $props = array(), $required = array() ) {
		return array(
			'name'        => $name,
			'description' => $desc,
			'inputSchema' => array(
				'type'       => 'object',
				'properties' => $props ? $props : new stdClass(),
				'required'   => $required,
			),
		);
	}

	private static function tool_defs() {
		$s = array( 'type' => 'string' );
		$i = array( 'type' => 'integer' );
		$b = array( 'type' => 'boolean' );
		return array(
			self::t( 'site_info', 'WordPress/PHP versions, active theme, plugins, URLs.' ),
			self::t( 'list_posts', 'List posts/pages/any post type.', array( 'post_type' => $s, 'status' => $s, 'search' => $s, 'per_page' => $i, 'page' => $i ) ),
			self::t( 'get_post', 'Get one post incl. full content and meta.', array( 'id' => $i ), array( 'id' ) ),
			self::t( 'create_post', 'Create a post/page. Content is HTML (or block markup).', array( 'title' => $s, 'content' => $s, 'post_type' => $s, 'status' => $s, 'slug' => $s, 'parent' => $i, 'meta' => array( 'type' => 'object' ) ), array( 'title' ) ),
			self::t( 'update_post', 'Update fields of a post/page.', array( 'id' => $i, 'title' => $s, 'content' => $s, 'status' => $s, 'slug' => $s, 'parent' => $i, 'meta' => array( 'type' => 'object' ) ), array( 'id' ) ),
			self::t( 'trash_post', 'Move a post to trash.', array( 'id' => $i ), array( 'id' ) ),
			self::t( 'list_menus', 'List nav menus and their items.' ),
			self::t( 'get_custom_css', 'Get the Additional CSS of the active theme.' ),
			self::t( 'update_custom_css', 'Replace the Additional CSS of the active theme.', array( 'css' => $s ), array( 'css' ) ),
			self::t( 'get_option', 'Read a WordPress option.', array( 'name' => $s ), array( 'name' ) ),
			self::t( 'update_option', 'Set a WordPress option.', array( 'name' => $s, 'value' => array() ), array( 'name', 'value' ) ),
			self::t( 'list_plugins', 'List installed plugins and active state.' ),
			self::t( 'toggle_plugin', 'Activate or deactivate a plugin by file (e.g. akismet/akismet.php).', array( 'plugin' => $s, 'active' => $b ), array( 'plugin', 'active' ) ),
			self::t( 'list_themes', 'List installed themes.' ),
			self::t( 'switch_theme', 'Activate a theme by stylesheet slug.', array( 'stylesheet' => $s ), array( 'stylesheet' ) ),
			self::t( 'list_files', 'List files in a directory under wp-content.', array( 'path' => $s ) ),
			self::t( 'read_file', 'Read a text file under wp-content.', array( 'path' => $s ), array( 'path' ) ),
			self::t( 'write_file', 'Create/overwrite a file under wp-content. Needs "Allow file writes" enabled. PHP is syntax-checked; old version is backed up.', array( 'path' => $s, 'content' => $s ), array( 'path', 'content' ) ),
			self::t( 'replace_in_file', 'Replace one exact string occurrence in a file (preferred for small edits).', array( 'path' => $s, 'old' => $s, 'new' => $s ), array( 'path', 'old', 'new' ) ),
			self::t( 'list_backups', 'List file backups (newest first).' ),
			self::t( 'restore_backup', 'Restore a backup by its id from list_backups.', array( 'id' => $s ), array( 'id' ) ),
			self::t( 'clear_cache', 'Flush object cache and rewrite rules.' ),
			self::t( 'upload_media', 'Add an image (base64, up to 5 MB) to the Media Library. Returns its id and url.', array( 'filename' => $s, 'base64' => $s, 'title' => $s ), array( 'filename', 'base64' ) ),
			self::t( 'install_plugin', 'Install a free plugin from wordpress.org by slug (e.g. woocommerce). Does not activate it.', array( 'slug' => $s ), array( 'slug' ) ),
			self::t( 'run_action', 'Run a whitelisted maintenance action registered by another plugin (e.g. pcs_seed).', array( 'name' => $s ), array( 'name' ) ),
			self::t( 'get_log', 'Recent tool calls made through this endpoint.' ),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Content tools                                                       */
	/* ------------------------------------------------------------------ */

	private static function tool_site_info( $a ) {
		$theme = wp_get_theme();
		return array(
			'name'         => get_bloginfo( 'name' ),
			'url'          => home_url(),
			'wp_version'   => get_bloginfo( 'version' ),
			'php_version'  => PHP_VERSION,
			'theme'        => $theme->get_stylesheet(),
			'is_block_theme' => wp_is_block_theme(),
			'active_plugins' => (array) get_option( 'active_plugins' ),
			'file_writes_enabled' => (bool) get_option( self::OPT_FILES ),
		);
	}

	private static function post_summary( $p ) {
		return array( 'id' => $p->ID, 'title' => $p->post_title, 'type' => $p->post_type, 'status' => $p->post_status, 'slug' => $p->post_name, 'url' => get_permalink( $p ), 'modified' => $p->post_modified_gmt );
	}

	private static function tool_list_posts( $a ) {
		$q = new WP_Query( array(
			'post_type'      => $a['post_type'] ?? 'any',
			'post_status'    => $a['status'] ?? 'any',
			's'              => $a['search'] ?? '',
			'posts_per_page' => min( 100, (int) ( $a['per_page'] ?? 20 ) ),
			'paged'          => max( 1, (int) ( $a['page'] ?? 1 ) ),
		) );
		return array( 'total' => $q->found_posts, 'posts' => array_map( array( __CLASS__, 'post_summary' ), $q->posts ) );
	}

	private static function tool_get_post( $a ) {
		$p = get_post( (int) $a['id'] );
		if ( ! $p ) {
			throw new Exception( 'Post not found' );
		}
		$meta = array();
		foreach ( get_post_meta( $p->ID ) as $k => $v ) {
			$meta[ $k ] = count( $v ) > 1 ? $v : $v[0];
		}
		return self::post_summary( $p ) + array( 'content' => $p->post_content, 'excerpt' => $p->post_excerpt, 'meta' => $meta );
	}

	private static function apply_meta( $id, $meta ) {
		foreach ( (array) $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
	}

	private static function tool_create_post( $a ) {
		$id = wp_insert_post( wp_slash( array(
			'post_title'   => $a['title'],
			'post_content' => $a['content'] ?? '',
			'post_type'    => $a['post_type'] ?? 'page',
			'post_status'  => $a['status'] ?? 'draft',
			'post_name'    => $a['slug'] ?? '',
			'post_parent'  => (int) ( $a['parent'] ?? 0 ),
		) ), true );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		self::apply_meta( $id, $a['meta'] ?? array() );
		return self::post_summary( get_post( $id ) );
	}

	private static function tool_update_post( $a ) {
		$arr = array( 'ID' => (int) $a['id'] );
		foreach ( array( 'title' => 'post_title', 'content' => 'post_content', 'status' => 'post_status', 'slug' => 'post_name', 'parent' => 'post_parent' ) as $k => $f ) {
			if ( isset( $a[ $k ] ) ) {
				$arr[ $f ] = $a[ $k ];
			}
		}
		$id = wp_update_post( wp_slash( $arr ), true );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		self::apply_meta( $id, $a['meta'] ?? array() );
		return self::post_summary( get_post( $id ) );
	}

	private static function tool_trash_post( $a ) {
		return array( 'trashed' => (bool) wp_trash_post( (int) $a['id'] ) );
	}

	private static function tool_list_menus( $a ) {
		$out = array();
		foreach ( wp_get_nav_menus() as $m ) {
			$items = array();
			foreach ( (array) wp_get_nav_menu_items( $m->term_id ) as $it ) {
				$items[] = array( 'id' => $it->ID, 'title' => $it->title, 'url' => $it->url, 'parent' => (int) $it->menu_item_parent );
			}
			$out[] = array( 'id' => $m->term_id, 'name' => $m->name, 'items' => $items );
		}
		return $out;
	}

	private static function tool_get_custom_css( $a ) {
		return array( 'css' => wp_get_custom_css() );
	}

	private static function tool_update_custom_css( $a ) {
		$r = wp_update_custom_css_post( (string) $a['css'] );
		if ( is_wp_error( $r ) ) {
			throw new Exception( $r->get_error_message() );
		}
		return array( 'ok' => true );
	}

	/* ------------------------------------------------------------------ */
	/* Options / plugins / themes                                          */
	/* ------------------------------------------------------------------ */

	private static function check_option( $name ) {
		if ( in_array( $name, self::BLOCKED_OPTIONS, true ) || false !== strpos( $name, 'pcmcp_' ) ) {
			throw new Exception( "Option '$name' is protected." );
		}
	}

	private static function tool_get_option( $a ) {
		self::check_option( $a['name'] );
		return array( 'name' => $a['name'], 'value' => get_option( $a['name'] ) );
	}

	private static function tool_update_option( $a ) {
		self::check_option( $a['name'] );
		return array( 'updated' => update_option( $a['name'], $a['value'] ) );
	}

	private static function tool_list_plugins( $a ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$out = array();
		foreach ( get_plugins() as $file => $d ) {
			$out[] = array( 'file' => $file, 'name' => $d['Name'], 'version' => $d['Version'], 'active' => is_plugin_active( $file ) );
		}
		return $out;
	}

	private static function tool_toggle_plugin( $a ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( plugin_basename( __FILE__ ) === $a['plugin'] && ! $a['active'] ) {
			throw new Exception( 'Refusing to deactivate the MCP plugin itself.' );
		}
		if ( $a['active'] ) {
			$r = activate_plugin( $a['plugin'] );
			if ( is_wp_error( $r ) ) {
				throw new Exception( $r->get_error_message() );
			}
		} else {
			deactivate_plugins( $a['plugin'] );
		}
		self::purge();
		return array( 'plugin' => $a['plugin'], 'active' => is_plugin_active( $a['plugin'] ) );
	}

	private static function tool_list_themes( $a ) {
		$out = array();
		foreach ( wp_get_themes() as $slug => $t ) {
			$out[] = array( 'stylesheet' => $slug, 'name' => $t->get( 'Name' ), 'version' => $t->get( 'Version' ), 'active' => get_stylesheet() === $slug );
		}
		return $out;
	}

	private static function tool_switch_theme( $a ) {
		if ( ! wp_get_theme( $a['stylesheet'] )->exists() ) {
			throw new Exception( 'Theme not found' );
		}
		switch_theme( $a['stylesheet'] );
		self::purge();
		return array( 'active' => get_stylesheet() );
	}

	private static function tool_clear_cache( $a ) {
		self::purge();
		flush_rewrite_rules();
		return array( 'ok' => true );
	}

	private static function tool_upload_media( $a ) {
		self::require_writes();
		$name = sanitize_file_name( (string) ( $a['filename'] ?? '' ) );
		$data = base64_decode( (string) ( $a['base64'] ?? '' ), true );
		if ( '' === $name || ! $data || strlen( $data ) > 5 * 1024 * 1024 ) {
			throw new Exception( 'A filename and valid base64 data (max 5 MB) are required.' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$tmp = wp_tempnam( $name );
		file_put_contents( $tmp, $data );
		$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, isset( $a['title'] ) ? sanitize_text_field( $a['title'] ) : null );
		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore
			throw new Exception( $id->get_error_message() );
		}
		return array( 'id' => $id, 'url' => wp_get_attachment_url( $id ) );
	}

	private static function tool_install_plugin( $a ) {
		self::require_writes();
		$slug = sanitize_key( $a['slug'] ?? '' );
		if ( ! $slug ) {
			throw new Exception( 'slug is required' );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		if ( ! WP_Filesystem() ) {
			throw new Exception( 'WordPress cannot write files directly on this host (filesystem credentials needed).' );
		}
		$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
		if ( is_wp_error( $api ) ) {
			throw new Exception( 'wordpress.org: ' . $api->get_error_message() );
		}
		$skin = new Automatic_Upgrader_Skin();
		$up   = new Plugin_Upgrader( $skin );
		$r    = $up->install( $api->download_link );
		if ( is_wp_error( $r ) ) {
			throw new Exception( $r->get_error_message() );
		}
		if ( ! $r ) {
			$err = $skin->get_upgrade_messages();
			throw new Exception( 'Install failed: ' . implode( ' / ', (array) $err ) );
		}
		self::purge();
		return array( 'installed' => $slug, 'version' => $api->version, 'plugin_file' => $up->plugin_info() );
	}

	private static function tool_run_action( $a ) {
		$allowed = apply_filters( 'pcmcp_allowed_actions', array() );
		$name    = $a['name'] ?? '';
		if ( ! isset( $allowed[ $name ] ) || ! is_callable( $allowed[ $name ] ) ) {
			throw new Exception( 'Action not allowed. Available: ' . implode( ', ', array_keys( $allowed ) ) );
		}
		return array( 'result' => call_user_func( $allowed[ $name ] ) );
	}

	private static function tool_get_log( $a ) {
		return array_reverse( get_option( self::OPT_LOG, array() ) );
	}

	/* ------------------------------------------------------------------ */
	/* Files (sandboxed to wp-content, backed up)                          */
	/* ------------------------------------------------------------------ */

	private static function resolve( $rel, $must_exist = true ) {
		$rel  = ltrim( str_replace( '\\', '/', (string) $rel ), '/' );
		$root = realpath( WP_CONTENT_DIR );
		if ( false !== strpos( $rel, '..' ) ) {
			throw new Exception( 'Path traversal not allowed.' );
		}
		$full = $root . '/' . $rel;
		$real = $must_exist ? realpath( $full ) : realpath( dirname( $full ) );
		if ( ! $real || 0 !== strpos( $real, $root ) ) {
			throw new Exception( 'Path not found or outside wp-content.' );
		}
		if ( 0 === strpos( $real, self::backup_dir() ) ) {
			throw new Exception( 'Backup directory is off limits.' );
		}
		return $must_exist ? $real : $real . '/' . basename( $full );
	}

	/** Purge page caches (LiteSpeed, WP Rocket, W3TC, WP Super Cache, object cache) after a change. */
	private static function purge() {
		wp_cache_flush();
		do_action( 'litespeed_purge_all' );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
	}

	private static function backup_dir() {
		$dir = WP_CONTENT_DIR . '/uploads/pcmcp-backups';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" );
			file_put_contents( $dir . '/index.html', '' );
		}
		return realpath( $dir );
	}

	private static function require_writes() {
		if ( ! get_option( self::OPT_FILES ) ) {
			throw new Exception( 'File writes are disabled. Enable them in Settings → Primecutss MCP.' );
		}
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
			throw new Exception( 'DISALLOW_FILE_EDIT is set in wp-config.php.' );
		}
	}

	private static function lint_php( $code ) {
		try {
			token_get_all( $code, TOKEN_PARSE );
		} catch ( ParseError $e ) {
			throw new Exception( 'PHP syntax error, not written: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')' );
		}
	}

	private static function backup( $full ) {
		if ( ! file_exists( $full ) ) {
			return null;
		}
		$id = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false );
		copy( $full, self::backup_dir() . '/' . $id . '.bak' );
		$idx        = get_option( 'pcmcp_backups', array() );
		$idx[ $id ] = substr( $full, strlen( realpath( WP_CONTENT_DIR ) ) + 1 );
		update_option( 'pcmcp_backups', array_slice( $idx, -200, null, true ), false );
		return $id;
	}

	private static function tool_list_files( $a ) {
		$dir = self::resolve( $a['path'] ?? '' );
		if ( ! is_dir( $dir ) ) {
			throw new Exception( 'Not a directory' );
		}
		$out = array();
		foreach ( scandir( $dir ) as $f ) {
			if ( '.' === $f || '..' === $f ) {
				continue;
			}
			$out[] = array( 'name' => $f, 'type' => is_dir( "$dir/$f" ) ? 'dir' : 'file', 'size' => is_file( "$dir/$f" ) ? filesize( "$dir/$f" ) : null );
		}
		return $out;
	}

	private static function tool_read_file( $a ) {
		$f = self::resolve( $a['path'] );
		if ( ! is_file( $f ) || filesize( $f ) > 1048576 ) {
			throw new Exception( 'Not a file, or larger than 1 MB.' );
		}
		return array( 'path' => $a['path'], 'content' => file_get_contents( $f ) );
	}

	private static function tool_write_file( $a ) {
		self::require_writes();
		$rel = ltrim( str_replace( '\\', '/', (string) $a['path'] ), '/' );
		if ( false === strpos( $rel, '..' ) && dirname( $rel ) !== '.' && ! is_dir( WP_CONTENT_DIR . '/' . dirname( $rel ) ) ) {
			wp_mkdir_p( WP_CONTENT_DIR . '/' . dirname( $rel ) );
		}
		$f = self::resolve( $a['path'], false );
		if ( preg_match( '/\.php$/i', $f ) ) {
			self::lint_php( $a['content'] );
		}
		$bk = self::backup( $f );
		if ( false === file_put_contents( $f, $a['content'] ) ) {
			throw new Exception( 'Write failed' );
		}
		self::purge();
		return array( 'written' => $a['path'], 'bytes' => strlen( $a['content'] ), 'backup_id' => $bk, 'cache_purged' => true );
	}

	private static function tool_replace_in_file( $a ) {
		self::require_writes();
		$f   = self::resolve( $a['path'] );
		$src = file_get_contents( $f );
		if ( 1 !== substr_count( $src, $a['old'] ) ) {
			throw new Exception( '"old" must occur exactly once (found ' . substr_count( $src, $a['old'] ) . ').' );
		}
		$new = str_replace( $a['old'], $a['new'], $src );
		if ( preg_match( '/\.php$/i', $f ) ) {
			self::lint_php( $new );
		}
		$bk = self::backup( $f );
		file_put_contents( $f, $new );
		self::purge();
		return array( 'edited' => $a['path'], 'backup_id' => $bk, 'cache_purged' => true );
	}

	private static function tool_list_backups( $a ) {
		$out = array();
		foreach ( array_reverse( get_option( 'pcmcp_backups', array() ), true ) as $id => $path ) {
			$out[] = array( 'id' => $id, 'path' => $path );
		}
		return $out;
	}

	private static function tool_restore_backup( $a ) {
		self::require_writes();
		$idx = get_option( 'pcmcp_backups', array() );
		$id  = $a['id'];
		if ( ! isset( $idx[ $id ] ) ) {
			throw new Exception( 'Unknown backup id' );
		}
		$src  = self::backup_dir() . '/' . basename( $id ) . '.bak';
		$full = self::resolve( $idx[ $id ], false );
		$bk   = self::backup( $full );
		copy( $src, $full );
		self::purge();
		return array( 'restored' => $idx[ $id ], 'previous_version_backup_id' => $bk );
	}

	/* ------------------------------------------------------------------ */
	/* Settings page                                                       */
	/* ------------------------------------------------------------------ */

	public static function admin_menu() {
		add_options_page( 'Primecutss MCP', 'Primecutss MCP', 'manage_options', 'pcmcp', array( __CLASS__, 'settings_page' ) );
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$new_token = '';
		if ( isset( $_POST['pcmcp_nonce'] ) && wp_verify_nonce( $_POST['pcmcp_nonce'], 'pcmcp' ) ) {
			update_option( self::OPT_FILES, empty( $_POST['allow_files'] ) ? 0 : 1 );
			if ( isset( $_POST['regen'] ) ) {
				$new_token = wp_generate_password( 40, false );
				update_option( self::OPT_TOKEN, hash( 'sha256', $new_token ) );
			}
			if ( isset( $_POST['revoke'] ) ) {
				delete_option( self::OPT_TOKEN );
			}
		}
		$endpoint = rest_url( 'pcmcp/v1/mcp' );
		?>
		<div class="wrap">
			<h1>Primecutss MCP</h1>
			<p>MCP endpoint: <code><?php echo esc_html( $endpoint ); ?></code></p>
			<form method="post">
				<?php wp_nonce_field( 'pcmcp', 'pcmcp_nonce' ); ?>
				<?php if ( $new_token ) : ?>
					<div class="notice notice-success"><p><strong>New token (shown once, copy it now):</strong><br><code><?php echo esc_html( $new_token ); ?></code></p></div>
				<?php endif; ?>
				<p>Token status: <strong><?php echo get_option( self::OPT_TOKEN ) ? 'active' : 'none (generate one)'; ?></strong></p>
				<p>
					<button class="button button-primary" name="regen" value="1">Generate new token</button>
					<button class="button" name="revoke" value="1">Revoke token</button>
				</p>
				<p><label><input type="checkbox" name="allow_files" value="1" <?php checked( get_option( self::OPT_FILES ) ); ?>> Allow file writes (theme/plugin files under wp-content). PHP is syntax-checked and every write is backed up.</label></p>
				<p><button class="button">Save settings</button></p>
			</form>
		</div>
		<?php
	}
}

Primecutss_MCP::init();

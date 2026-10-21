<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Shop Staff: a role that sees only the shop, with a simple home page
 * and a Price & Stock table. Admins are unaffected.
 * ------------------------------------------------------------------ */

const PCS_STAFF_ROLE = 'pcs_staff';
const PCS_STAFF_V    = '3';

function pcs_staff_caps() {
	$caps = array( 'read', 'upload_files', 'manage_woocommerce', 'view_woocommerce_reports', 'list_users', 'create_users', 'edit_users', 'delete_users', 'promote_users' );
	foreach ( array( 'product', 'shop_order', 'shop_coupon' ) as $t ) {
		foreach ( array( 'edit_%s', 'read_%s', 'delete_%s' ) as $f ) {
			$caps[] = sprintf( $f, $t );
		}
		foreach ( array( 'edit_%ss', 'edit_others_%ss', 'publish_%ss', 'read_private_%ss', 'delete_%ss', 'delete_private_%ss', 'delete_published_%ss', 'delete_others_%ss', 'edit_private_%ss', 'edit_published_%ss' ) as $f ) {
			$caps[] = sprintf( $f, $t );
		}
	}
	foreach ( array( 'manage_product_terms', 'edit_product_terms', 'delete_product_terms', 'assign_product_terms' ) as $c ) {
		$caps[] = $c;
	}
	return array_fill_keys( $caps, true );
}

add_action( 'init', function () {
	if ( get_option( 'pcs_staff_v' ) !== PCS_STAFF_V ) {
		remove_role( PCS_STAFF_ROLE );
		add_role( PCS_STAFF_ROLE, 'Shop Staff', pcs_staff_caps() );
		update_option( 'pcs_staff_v', PCS_STAFF_V );
	}
}, 20 );

/** True for a Shop Staff user (not an administrator). */
function pcs_is_staff( $user_id = 0 ) {
	$u = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $u && $u->exists() && in_array( PCS_STAFF_ROLE, (array) $u->roles, true ) && ! in_array( 'administrator', (array) $u->roles, true );
}

/* ---------------- staff can add and manage only customers / staff ---------------- */

add_filter( 'editable_roles', function ( $roles ) {
	return pcs_is_staff() ? array_intersect_key( $roles, array( 'customer' => 1, PCS_STAFF_ROLE => 1 ) ) : $roles;
} );
add_filter( 'map_meta_cap', function ( $caps, $cap, $uid, $args ) {
	if ( in_array( $cap, array( 'edit_user', 'delete_user', 'promote_user', 'remove_user' ), true ) && pcs_is_staff( $uid ) && ! empty( $args[0] ) ) {
		$t = get_userdata( (int) $args[0] );
		if ( $t && ! array_intersect( (array) $t->roles, array( 'customer', PCS_STAFF_ROLE ) ) ) {
			$caps[] = 'do_not_allow';
		}
	}
	return $caps;
}, 10, 4 );
add_action( 'pre_get_users', function ( $q ) {
	if ( is_admin() && pcs_is_staff() ) {
		$q->set( 'role__in', array( 'customer', PCS_STAFF_ROLE ) );
	}
} );

/* ---------------- menu: only the shop ---------------- */

add_action( 'admin_menu', function () {
	$title = 'Home';
	if ( pcs_is_staff() ) {
		add_menu_page( 'Home', 'Home', 'manage_woocommerce', 'pcs-home', 'pcs_staff_home', 'dashicons-store', 2 );
		add_menu_page( 'Price & Stock', 'Price & Stock', 'edit_products', 'pcs-stock', 'pcs_stock_page', 'dashicons-tag', 3 );
	} else {
		add_submenu_page( 'pcs-settings', 'Shop home', 'Shop home', 'manage_woocommerce', 'pcs-home', 'pcs_staff_home' );
		add_submenu_page( 'pcs-settings', 'Price & Stock', 'Price & Stock', 'edit_products', 'pcs-stock', 'pcs_stock_page' );
	}
}, 5 );

add_action( 'admin_menu', function () {
	if ( ! pcs_is_staff() ) {
		return;
	}
	global $menu, $submenu;
	$keep_top = array( 'pcs-home', 'pcs-stock', 'woocommerce', 'edit.php?post_type=product', 'users.php', 'woocommerce-marketing' );
	foreach ( (array) $menu as $k => $m ) {
		if ( ! in_array( $m[2], $keep_top, true ) ) {
			$menu[ $k ][4] = ( $m[4] ?? '' ) . ' pcs-hide';
		}
	}
	$sub_keep = array(
		'woocommerce'               => array( 'wc-orders', 'edit.php?post_type=shop_order', 'wc-admin&path=/customers', 'wc-reports', 'wc-admin&path=/analytics/overview' ),
		'woocommerce-marketing'     => array( 'edit.php?post_type=shop_coupon' ),
		'edit.php?post_type=product' => array( 'edit.php?post_type=product', 'post-new.php?post_type=product', 'edit-tags.php?taxonomy=product_cat&post_type=product' ),
		'users.php'                 => array( 'users.php', 'user-new.php' ),
	);
	foreach ( $sub_keep as $parent => $slugs ) {
		foreach ( (array) ( $submenu[ $parent ] ?? array() ) as $i => $item ) {
			$ok = false;
			foreach ( $slugs as $sl ) {
				if ( 0 === strpos( $item[2], $sl ) ) {
					$ok = true;
				}
			}
			if ( ! $ok ) {
				$submenu[ $parent ][ $i ][4] = ( $item[4] ?? '' ) . ' pcs-hide';
			}
		}
	}
	// friendlier names
	foreach ( (array) $menu as $k => $m ) {
		if ( 'woocommerce' === $m[2] ) {
			$menu[ $k ][0] = 'Orders & Reports';
		} elseif ( 'woocommerce-marketing' === $m[2] ) {
			$menu[ $k ][0] = 'Coupons';
		} elseif ( 'users.php' === $m[2] ) {
			$menu[ $k ][0] = 'Customers & Staff';
		}
	}
}, 99999 );

/* ---------------- landing page, look and feel ---------------- */

add_filter( 'login_redirect', function ( $to, $req, $user ) {
	return ( $user && ! is_wp_error( $user ) && pcs_is_staff( $user->ID ) ) ? admin_url( 'admin.php?page=pcs-home' ) : $to;
}, 10, 3 );
add_action( 'admin_init', function () {
	global $pagenow;
	if ( pcs_is_staff() && 'index.php' === $pagenow && ! wp_doing_ajax() ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pcs-home' ) );
		exit;
	}
} );
// Staff never reach WooCommerce settings (payments, emails, keys), status or extensions.
add_action( 'admin_init', function () {
	if ( ! pcs_is_staff() || wp_doing_ajax() ) {
		return;
	}
	$page = sanitize_key( $_GET['page'] ?? '' );
	$path = (string) ( $_GET['path'] ?? '' );
	$bad  = in_array( $page, array( 'wc-settings', 'wc-status', 'wc-addons', 'pcs-settings', 'pcs-about', 'pcs-quality', 'pcs-contact' ), true );
	if ( 'wc-admin' === $page && $path && ! preg_match( '#^/(customers|analytics)#', $path ) ) {
		$bad = true;
	}
	if ( $bad ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pcs-home' ) );
		exit;
	}
} );

add_filter( 'admin_body_class', function ( $c ) {
	return pcs_is_staff() ? $c . ' pcs-staff' : $c;
} );
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( pcs_is_staff() ) {
		foreach ( array( 'wp-logo', 'comments', 'new-content', 'updates', 'customize', 'search', 'site-name' ) as $n ) {
			$bar->remove_node( $n );
		}
		$bar->add_node( array( 'id' => 'pcs-site', 'title' => 'View shop', 'href' => home_url( '/' ), 'meta' => array( 'target' => '_blank' ) ) );
	}
}, 999 );
add_action( 'admin_head', function () {
	if ( ! pcs_is_staff() ) {
		return;
	}
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_action( 'admin_notices', 'maintenance_nag', 10 );
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Afacad:wght@400..700&display=swap">';
	echo '<style>' . pcs_staff_css() . '</style>';
} );
add_filter( 'admin_footer_text', function ( $t ) {
	return pcs_is_staff() ? '' : $t;
} );
add_filter( 'update_footer', function ( $t ) {
	return pcs_is_staff() ? '' : $t;
}, 99 );

function pcs_staff_css() {
	return <<<'CSS'
body.pcs-staff,body.pcs-staff #wpbody,body.pcs-staff input,body.pcs-staff select,body.pcs-staff textarea,body.pcs-staff button{font-family:'Afacad',system-ui,sans-serif}
body.pcs-staff{font-size:16px;background:#f6f6f6}
body.pcs-staff #adminmenu .pcs-hide,body.pcs-staff #adminmenu li.wp-menu-separator,body.pcs-staff #wp-admin-bar-menu-toggle~*[id*=wp-logo]{display:none!important}
body.pcs-staff #adminmenuback,body.pcs-staff #adminmenuwrap,body.pcs-staff #adminmenu{background:#1c1c1c}
body.pcs-staff #adminmenu a{font-size:17px;padding-top:4px;padding-bottom:4px;color:#e8e8e8}
body.pcs-staff #adminmenu li.menu-top>a{padding:6px 0}
body.pcs-staff #adminmenu div.wp-menu-image:before{color:#ff6b7f}
body.pcs-staff #adminmenu li.current>a.menu-top,body.pcs-staff #adminmenu li.wp-has-current-submenu>a.wp-has-current-submenu,body.pcs-staff #adminmenu .wp-submenu li.current a{background:#c8102e;color:#fff}
body.pcs-staff #adminmenu li.current div.wp-menu-image:before,body.pcs-staff #adminmenu li.wp-has-current-submenu div.wp-menu-image:before{color:#fff}
body.pcs-staff #adminmenu .wp-submenu{background:#2a2a2a}
body.pcs-staff #adminmenu .wp-submenu a{font-size:15px;color:#ddd}
body.pcs-staff #adminmenu li.menu-top:hover,body.pcs-staff #adminmenu li.opensub>a.menu-top{background:#333}
body.pcs-staff #wpadminbar{background:#c8102e}
body.pcs-staff #wpadminbar *{color:#fff!important}
body.pcs-staff #wpfooter,body.pcs-staff #screen-meta-links,body.pcs-staff .update-nag,body.pcs-staff #contextual-help-link-wrap,body.pcs-staff .woocommerce-message.wc-connect,body.pcs-staff .notice.notice-info.is-dismissible:not(.pcs-keep){display:none!important}
body.pcs-staff .button-primary,body.pcs-staff .page-title-action{background:#c8102e;border-color:#c8102e;color:#fff;font-size:15px;padding:4px 16px;height:auto}
body.pcs-staff .button,body.pcs-staff .button-secondary{font-size:15px}
body.pcs-staff h1,body.pcs-staff .wp-heading-inline{font-size:28px;font-weight:700}
body.pcs-staff #wpbody-content{padding-bottom:30px}
body.pcs-staff #wpadminbar li[id*=visibility],body.pcs-staff #wpadminbar li[id*=command],body.pcs-staff #wpadminbar li[id*=woocommerce]{display:none!important}
CSS;
}

/* ---------------- Shop home ---------------- */

function pcs_status_label( $s ) {
	return array( 'processing' => 'New', 'on-hold' => 'On hold', 'pending' => 'Pending payment', 'pcs-packed' => 'Packed', 'pcs-out' => 'Out for delivery', 'completed' => 'Delivered', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded', 'failed' => 'Failed' )[ $s ] ?? ucfirst( $s );
}

function pcs_order_items_text( $order ) {
	$out = array();
	foreach ( $order->get_items() as $it ) {
		$w     = $it->get_meta( 'Weight' );
		$out[] = $it->get_quantity() . ' × ' . $it->get_name() . ( $w ? ' (' . $w . ')' : '' );
	}
	return implode( ', ', $out );
}

function pcs_staff_home() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$u      = wp_get_current_user();
	$active = array( 'processing', 'pcs-packed', 'pcs-out', 'on-hold', 'pending' );
	$orders = wc_get_orders( array( 'status' => $active, 'limit' => 200, 'orderby' => 'date', 'order' => 'ASC' ) );
	$count  = array();
	foreach ( $orders as $o ) {
		$count[ $o->get_status() ] = ( $count[ $o->get_status() ] ?? 0 ) + 1;
	}
	$today = wp_date( 'Y-m-d' );
	$day   = wc_get_orders( array( 'date_created' => strtotime( $today . ' 00:00:00' ) . '...' . time(), 'status' => array( 'processing', 'pcs-packed', 'pcs-out', 'completed' ), 'limit' => -1 ) );
	$sales = 0;
	$done  = 0;
	foreach ( $day as $o ) {
		$sales += (float) $o->get_total();
		if ( 'completed' === $o->get_status() ) {
			$done++;
		}
	}
	// group active orders by slot day + slot
	$groups = array();
	foreach ( $orders as $o ) {
		$d = $o->get_meta( '_pcs_slot_date' ) ?: 'Unscheduled';
		$s = $o->get_meta( '_pcs_slot' ) ?: '';
		$groups[ $d ][ $s ][] = $o;
	}
	ksort( $groups );
	$link = function ( $path ) { return esc_url( admin_url( $path ) ); };
	$orders_url = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order';
	?>
	<div class="wrap pcs-home">
		<style>
		.pcs-home{max-width:1200px}
		.pcs-hi{font-size:30px;margin:10px 0 2px}
		.pcs-sub{color:#666;margin:0 0 20px;font-size:17px}
		.pcs-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:22px}
		.pcs-tile{background:#fff;border:1px solid #e3e3e3;border-top:4px solid #c8102e;padding:16px 18px;text-decoration:none;color:#1c1c1c;display:block}
		.pcs-tile b{display:block;font-size:38px;line-height:1.1;color:#c8102e}
		.pcs-tile span{font-size:16px;color:#444}
		.pcs-tile:hover{background:#fff7f8}
		.pcs-actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:26px}
		.pcs-actions a{background:#1c1c1c;color:#fff;text-decoration:none;padding:12px 20px;font-size:17px;font-weight:600;display:inline-flex;align-items:center;gap:8px}
		.pcs-actions a:hover{background:#c8102e;color:#fff}
		.pcs-day{margin:0 0 6px;font-size:22px}
		.pcs-slot{margin:14px 0 8px;font-weight:700;font-size:17px;background:#fdf1f2;border-left:4px solid #c8102e;padding:8px 14px}
		.pcs-ord{background:#fff;border:1px solid #e3e3e3;margin-bottom:10px;padding:14px 18px;display:grid;grid-template-columns:1.2fr 2fr auto;gap:16px;align-items:center}
		.pcs-ord h4{margin:0 0 2px;font-size:18px}
		.pcs-ord .meta{color:#666;font-size:15px}
		.pcs-ord .items{font-size:16px}
		.pcs-ord .tot{font-weight:700;font-size:18px}
		.pcs-badge{display:inline-block;padding:3px 10px;font-weight:700;font-size:13px;background:#eee;color:#333;text-transform:uppercase;letter-spacing:.03em}
		.pcs-badge.processing{background:#c8102e;color:#fff}.pcs-badge.pcs-packed{background:#f0b429;color:#222}.pcs-badge.pcs-out{background:#2c7be5;color:#fff}.pcs-badge.completed{background:#1e7a3c;color:#fff}
		.pcs-btns{display:flex;flex-direction:column;gap:6px;min-width:170px}
		.pcs-btns .button{text-align:center;font-size:15px!important;padding:6px 12px!important;height:auto!important;line-height:1.3!important}
		.pcs-empty{background:#fff;border:1px solid #e3e3e3;padding:36px;text-align:center;font-size:18px;color:#666}
		@media(max-width:900px){.pcs-ord{grid-template-columns:1fr}.pcs-btns{flex-direction:row;flex-wrap:wrap}}
		</style>
		<h1 class="pcs-hi">Hello, <?php echo esc_html( $u->display_name ); ?></h1>
		<p class="pcs-sub"><?php echo esc_html( wp_date( 'l, j F Y' ) ); ?> · here is what needs your attention.</p>

		<div class="pcs-tiles">
			<a class="pcs-tile" href="<?php echo $link( $orders_url . '&status=wc-processing' ); // phpcs:ignore ?>"><b><?php echo (int) ( $count['processing'] ?? 0 ); ?></b><span>New orders</span></a>
			<a class="pcs-tile" href="<?php echo $link( $orders_url . '&status=wc-pcs-packed' ); // phpcs:ignore ?>"><b><?php echo (int) ( $count['pcs-packed'] ?? 0 ); ?></b><span>Packed</span></a>
			<a class="pcs-tile" href="<?php echo $link( $orders_url . '&status=wc-pcs-out' ); // phpcs:ignore ?>"><b><?php echo (int) ( $count['pcs-out'] ?? 0 ); ?></b><span>Out for delivery</span></a>
			<a class="pcs-tile" href="<?php echo $link( $orders_url . '&status=wc-completed' ); // phpcs:ignore ?>"><b><?php echo (int) $done; ?></b><span>Delivered today</span></a>
			<a class="pcs-tile" href="<?php echo $link( 'admin.php?page=wc-admin&path=/analytics/overview' ); // phpcs:ignore ?>"><b><?php echo esc_html( pcs_money( $sales ) ); ?></b><span>Today's sales</span></a>
		</div>

		<div class="pcs-actions">
			<a href="<?php echo $link( $orders_url ); // phpcs:ignore ?>">All orders</a>
			<a href="<?php echo $link( 'admin.php?page=pcs-stock' ); // phpcs:ignore ?>">Price &amp; Stock</a>
			<a href="<?php echo $link( 'post-new.php?post_type=product' ); // phpcs:ignore ?>">+ Add product</a>
			<a href="<?php echo $link( 'edit.php?post_type=shop_coupon' ); // phpcs:ignore ?>">Coupons</a>
			<a href="<?php echo $link( 'admin.php?page=wc-admin&path=/customers' ); // phpcs:ignore ?>">Customers</a>
			<a href="<?php echo $link( 'admin.php?page=wc-admin&path=/analytics/overview' ); // phpcs:ignore ?>">Reports</a>
			<a href="<?php echo $link( 'user-new.php' ); // phpcs:ignore ?>">+ Add user</a>
		</div>

		<h2>Orders to deliver</h2>
		<?php if ( ! $groups ) : ?>
			<div class="pcs-empty">No orders waiting. New orders will show here.</div>
		<?php endif; ?>
		<?php foreach ( $groups as $date => $slots ) : ?>
			<h3 class="pcs-day"><?php echo esc_html( 'Unscheduled' === $date ? 'No slot' : wp_date( 'l, j M', strtotime( $date ) ) . ( $date === $today ? ' (today)' : '' ) ); ?></h3>
			<?php foreach ( $slots as $slot => $list ) : ?>
				<div class="pcs-slot"><?php echo esc_html( $slot ?: 'Time not chosen' ); ?> · <?php echo count( $list ); ?> order<?php echo count( $list ) > 1 ? 's' : ''; ?></div>
				<?php foreach ( $list as $o ) :
					$st = $o->get_status();
					?>
					<div class="pcs-ord">
						<div>
							<h4>#<?php echo (int) $o->get_id(); ?> · <?php echo esc_html( $o->get_billing_first_name() ); ?></h4>
							<div class="meta"><a href="tel:<?php echo esc_attr( $o->get_billing_phone() ); ?>"><?php echo esc_html( $o->get_billing_phone() ); ?></a> · <?php echo esc_html( $o->get_meta( '_pcs_area' ) ?: $o->get_billing_postcode() ); ?></div>
							<span class="pcs-badge <?php echo esc_attr( $st ); ?>"><?php echo esc_html( pcs_status_label( $st ) ); ?></span>
						</div>
						<div>
							<div class="items"><?php echo esc_html( pcs_order_items_text( $o ) ); ?></div>
							<div class="meta"><?php echo esc_html( $o->get_billing_address_1() ); ?></div>
							<div class="tot"><?php echo esc_html( pcs_money( $o->get_total() ) ); ?> <span class="meta">· <?php echo esc_html( $o->get_payment_method_title() ); ?></span></div>
						</div>
						<div class="pcs-btns">
							<?php
							$next = array( 'processing' => array( 'pcs-packed', 'Mark as Packed' ), 'on-hold' => array( 'pcs-packed', 'Mark as Packed' ), 'pending' => array( 'pcs-packed', 'Mark as Packed' ), 'pcs-packed' => array( 'pcs-out', 'Out for delivery' ), 'pcs-out' => array( 'completed', 'Mark Delivered' ) );
							if ( isset( $next[ $st ] ) ) {
								printf( '<a class="button button-primary" href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pcs_order_status&order=' . $o->get_id() . '&to=' . $next[ $st ][0] ), 'pcs_os_' . $o->get_id() ) ), esc_html( $next[ $st ][1] ) );
							}
							printf( '<a class="button" href="%s">Open order</a>', esc_url( $o->get_edit_order_url() ) );
							?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

add_action( 'admin_post_pcs_order_status', function () {
	$id = (int) ( $_GET['order'] ?? 0 );
	$to = sanitize_key( $_GET['to'] ?? '' );
	if ( ! current_user_can( 'edit_shop_orders' ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'pcs_os_' . $id ) || ! in_array( $to, array( 'pcs-packed', 'pcs-out', 'completed' ), true ) ) {
		wp_die( 'Not allowed.' );
	}
	$o = wc_get_order( $id );
	if ( $o ) {
		$o->update_status( $to, 'Updated from Shop home. ' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=pcs-home' ) );
	exit;
} );

/* ---------------- Price & Stock ---------------- */

function pcs_stock_page() {
	if ( ! current_user_can( 'edit_products' ) ) {
		return;
	}
	$cat      = sanitize_key( $_GET['cat'] ?? '' );
	$args     = array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'menu_order', 'order' => 'ASC' );
	if ( $cat ) {
		$args['category'] = array( $cat );
	}
	$products = wc_get_products( $args );
	$cats     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	?>
	<div class="wrap pcs-stock">
		<style>
		.pcs-stock{max-width:1200px}
		.pcs-stock .tabs{display:flex;gap:8px;margin:12px 0 16px;flex-wrap:wrap}
		.pcs-stock .tabs a{background:#fff;border:1px solid #ddd;padding:8px 18px;text-decoration:none;font-weight:600;color:#1c1c1c;font-size:16px}
		.pcs-stock .tabs a.on{background:#c8102e;border-color:#c8102e;color:#fff}
		.pcs-stock table{border-collapse:collapse;width:100%;background:#fff}
		.pcs-stock th{background:#1c1c1c;color:#fff;text-align:left;padding:12px 14px;font-size:15px}
		.pcs-stock td{padding:10px 14px;border-bottom:1px solid #eee;vertical-align:middle;font-size:16px}
		.pcs-stock td img{width:54px;height:54px;object-fit:cover;display:block}
		.pcs-stock input[type=number]{width:110px;font-size:17px;padding:6px 8px}
		.pcs-stock select{font-size:16px;padding:5px 30px 5px 10px;min-width:130px}
		.pcs-stock tr.out td{background:#fdf1f2}
		.pcs-stock .save{position:sticky;bottom:0;background:#fff;border-top:3px solid #c8102e;padding:12px 16px;display:flex;gap:14px;align-items:center;margin-top:0}
		.pcs-stock .save .button{font-size:18px!important;padding:8px 28px!important;height:auto!important}
		.pcs-stock .hint{color:#666;font-size:15px}
		.pcs-stock .ok{background:#e8f6ec;border-left:4px solid #1e7a3c;padding:12px 16px;margin:10px 0;font-size:16px}
		</style>
		<h1>Price &amp; Stock</h1>
		<p class="hint">Change prices and stock for all products in one place, then press <b>Save changes</b>. Price is per kg. Offer price is optional.</p>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="ok">Saved. <?php echo (int) $_GET['saved']; ?> product(s) updated.</div><?php endif; ?>
		<div class="tabs">
			<a class="<?php echo $cat ? '' : 'on'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=pcs-stock' ) ); ?>">All</a>
			<?php foreach ( $cats as $t ) : ?><a class="<?php echo $cat === $t->slug ? 'on' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=pcs-stock&cat=' . $t->slug ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pcs_save_stock">
			<input type="hidden" name="cat" value="<?php echo esc_attr( $cat ); ?>">
			<?php wp_nonce_field( 'pcs_stock', 'pcs_stock_nonce' ); ?>
			<table>
				<thead><tr><th></th><th>Product</th><th>Price per kg (₹)</th><th>Offer price (₹)</th><th>Stock</th><th>Show in shop</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $products as $p ) : $id = $p->get_id(); ?>
				<tr class="<?php echo $p->is_in_stock() ? '' : 'out'; ?>">
					<td><?php echo $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), array( 54, 54 ) ) : ''; ?></td>
					<td><strong><?php echo esc_html( $p->get_name() ); ?></strong><br><span class="hint"><?php echo wp_kses_post( wc_get_product_category_list( $id ) ); ?></span></td>
					<td><input type="number" min="0" step="1" name="price[<?php echo (int) $id; ?>]" value="<?php echo esc_attr( $p->get_regular_price() ); ?>"></td>
					<td><input type="number" min="0" step="1" name="sale[<?php echo (int) $id; ?>]" value="<?php echo esc_attr( $p->get_sale_price() ); ?>" placeholder="none"></td>
					<td><select name="stock[<?php echo (int) $id; ?>]"><option value="instock" <?php selected( $p->get_stock_status(), 'instock' ); ?>>In stock</option><option value="outofstock" <?php selected( $p->get_stock_status(), 'outofstock' ); ?>>Out of stock</option></select></td>
					<td><select name="show[<?php echo (int) $id; ?>]"><option value="publish" <?php selected( $p->get_status(), 'publish' ); ?>>Visible</option><option value="draft" <?php selected( $p->get_status(), 'draft' ); ?>>Hidden</option></select></td>
					<td><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Edit details</a></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<div class="save"><button class="button button-primary" type="submit">Save changes</button><span class="hint">Only the products you changed are updated.</span></div>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_pcs_save_stock', function () {
	if ( ! current_user_can( 'edit_products' ) || ! wp_verify_nonce( $_POST['pcs_stock_nonce'] ?? '', 'pcs_stock' ) ) {
		wp_die( 'Not allowed.' );
	}
	$n = 0;
	foreach ( (array) ( $_POST['price'] ?? array() ) as $id => $price ) {
		$p = wc_get_product( (int) $id );
		if ( ! $p ) {
			continue;
		}
		$new_price = '' === trim( (string) $price ) ? '' : (string) max( 0, (float) $price );
		$sale      = isset( $_POST['sale'][ $id ] ) && '' !== trim( (string) $_POST['sale'][ $id ] ) ? (string) max( 0, (float) $_POST['sale'][ $id ] ) : '';
		if ( '' !== $sale && '' !== $new_price && (float) $sale >= (float) $new_price ) {
			$sale = '';
		}
		$stock  = ( $_POST['stock'][ $id ] ?? '' ) === 'outofstock' ? 'outofstock' : 'instock';
		$status = ( $_POST['show'][ $id ] ?? '' ) === 'draft' ? 'draft' : 'publish';
		$ch     = $p->get_regular_price() !== $new_price || $p->get_sale_price() !== $sale || $p->get_stock_status() !== $stock || ( 'private' !== $p->get_status() && $p->get_status() !== $status );
		if ( $ch ) {
			$p->set_regular_price( $new_price );
			$p->set_sale_price( $sale );
			$p->set_stock_status( $stock );
			if ( 'private' !== $p->get_status() ) {
				$p->set_status( $status );
			}
			$p->save();
			$n++;
		}
	}
	if ( $n ) {
		do_action( 'litespeed_purge_all' );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'pcs-stock', 'cat' => sanitize_key( $_POST['cat'] ?? '' ), 'saved' => $n ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ---------------- create the staff user (run once) ---------------- */

add_filter( 'pcmcp_allowed_actions', function ( $a ) {
	$a['pcs_create_staff'] = 'pcs_create_staff_user';
	return $a;
} );

function pcs_create_staff_user() {
	$login = 'shopstaff';
	if ( username_exists( $login ) ) {
		return 'User already exists: ' . $login;
	}
	$pw  = wp_generate_password( 14, false, false );
	$uid = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $pw, 'user_email' => 'wpgainonline+staff@gmail.com', 'display_name' => 'Shop Staff', 'first_name' => 'Shop', 'last_name' => 'Staff', 'role' => PCS_STAFF_ROLE ) );
	return is_wp_error( $uid ) ? $uid->get_error_message() : "Created user $login / $pw";
}

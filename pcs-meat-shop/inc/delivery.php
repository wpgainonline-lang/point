<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Delivery partners: own role, phone-friendly screen, automatic assignment.
 * Rule: an order goes to a delivery boy who is on duty and has no order
 * in hand; busy ones are skipped. If everyone is busy, the order waits
 * and is assigned the moment someone finishes.
 * ------------------------------------------------------------------ */

const PCS_DRIVER_ROLE = 'pcs_delivery';
const PCS_DRIVER_V    = '1';

add_action( 'init', function () {
	if ( get_option( 'pcs_driver_v' ) !== PCS_DRIVER_V ) {
		remove_role( PCS_DRIVER_ROLE );
		add_role( PCS_DRIVER_ROLE, 'Delivery Partner', array( 'read' => true, 'pcs_deliver' => true ) );
		update_option( 'pcs_driver_v', PCS_DRIVER_V );
	}
}, 20 );

function pcs_is_driver( $user_id = 0 ) {
	$u = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	return $u && $u->exists() && in_array( PCS_DRIVER_ROLE, (array) $u->roles, true ) && ! in_array( 'administrator', (array) $u->roles, true );
}

function pcs_active_statuses() {
	return array( 'processing', 'pcs-packed', 'pcs-out' );
}

function pcs_drivers() {
	return get_users( array( 'role' => PCS_DRIVER_ROLE, 'orderby' => 'ID', 'order' => 'ASC' ) );
}

function pcs_order_driver( $order ) {
	return (int) $order->get_meta( '_pcs_driver' );
}

/** Active orders (not yet delivered), oldest first. */
function pcs_active_orders() {
	return wc_get_orders( array( 'status' => pcs_active_statuses(), 'limit' => 300, 'orderby' => 'date', 'order' => 'ASC' ) );
}

/** Orders in hand per driver id (orders with a reported problem do not count). */
function pcs_driver_loads( $orders = null ) {
	$load = array();
	foreach ( null === $orders ? pcs_active_orders() : $orders as $o ) {
		$d = pcs_order_driver( $o );
		if ( $d && ! $o->get_meta( '_pcs_issue' ) ) {
			$load[ $d ] = ( $load[ $d ] ?? 0 ) + 1;
		}
	}
	return $load;
}

function pcs_max_active() {
	return max( 1, (int) pcs_opt( 'driver_max_active', 1 ) );
}

/** The delivery boy who should get the next order, or 0 if everyone is busy / off duty. */
function pcs_pick_driver( $loads = null, $skip = array() ) {
	$loads = null === $loads ? pcs_driver_loads() : $loads;
	$best  = 0;
	$key   = null;
	foreach ( pcs_drivers() as $d ) {
		if ( 'no' === get_user_meta( $d->ID, 'pcs_on_duty', true ) || in_array( $d->ID, $skip, true ) ) {
			continue;
		}
		$n = $loads[ $d->ID ] ?? 0;
		if ( $n >= pcs_max_active() ) {
			continue;
		}
		$k = array( $n, (int) get_user_meta( $d->ID, 'pcs_last_assigned', true ) ); // least busy, then waited longest
		if ( null === $key || $k < $key ) {
			$key  = $k;
			$best = $d->ID;
		}
	}
	return $best;
}

function pcs_assign_to( $order, $driver_id, $by = 'Auto' ) {
	$driver_id = (int) $driver_id;
	$u         = $driver_id ? get_userdata( $driver_id ) : null;
	$order->update_meta_data( '_pcs_driver', $driver_id );
	$order->delete_meta_data( '_pcs_issue' );
	if ( $driver_id ) {
		$order->update_meta_data( '_pcs_assigned_at', time() );
		update_user_meta( $driver_id, 'pcs_last_assigned', time() );
	}
	$order->add_order_note( $driver_id ? "$by: assigned to " . ( $u ? $u->display_name : "#$driver_id" ) : "$by: delivery assignment removed" );
	$order->save();
}

function pcs_try_assign( $order ) {
	if ( ! $order || pcs_order_driver( $order ) || ! in_array( $order->get_status(), pcs_active_statuses(), true ) ) {
		return 0;
	}
	$d = pcs_pick_driver();
	if ( $d ) {
		pcs_assign_to( $order, $d, 'Auto' );
	}
	return $d;
}

/** Give waiting orders to whoever is free now. */
function pcs_assign_waiting() {
	$orders = pcs_active_orders();
	$loads  = pcs_driver_loads( $orders );
	$n      = 0;
	foreach ( $orders as $o ) {
		if ( pcs_order_driver( $o ) ) {
			continue;
		}
		$d = pcs_pick_driver( $loads );
		if ( ! $d ) {
			break;
		}
		pcs_assign_to( $o, $d, 'Auto' );
		$loads[ $d ] = ( $loads[ $d ] ?? 0 ) + 1;
		$n++;
	}
	return $n;
}

add_action( 'woocommerce_order_status_processing', function ( $order_id ) {
	pcs_try_assign( wc_get_order( $order_id ) );
} );
// A cancelled / delivered-by-staff order frees the boy: pass the work on.
add_action( 'woocommerce_order_status_changed', function ( $id, $from, $to ) {
	if ( in_array( $to, array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) && in_array( $from, pcs_active_statuses(), true ) ) {
		pcs_assign_waiting();
	}
}, 20, 3 );

/* ---------------- who can do what ---------------- */

add_action( 'admin_menu', function () {
	if ( pcs_is_driver() ) {
		add_menu_page( 'My deliveries', 'My deliveries', 'pcs_deliver', 'pcs-delivery', 'pcs_driver_page', 'dashicons-location', 2 );
	}
}, 5 );
add_filter( 'login_redirect', function ( $to, $req, $user ) {
	return ( $user && ! is_wp_error( $user ) && pcs_is_driver( $user->ID ) ) ? admin_url( 'admin.php?page=pcs-delivery' ) : $to;
}, 10, 3 );
add_action( 'admin_init', function () {
	global $pagenow;
	if ( ! pcs_is_driver() || wp_doing_ajax() ) {
		return;
	}
	$ok = in_array( $pagenow, array( 'admin-post.php', 'profile.php', 'async-upload.php' ), true ) || ( 'admin.php' === $pagenow && 'pcs-delivery' === ( $_GET['page'] ?? '' ) );
	if ( ! $ok ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pcs-delivery' ) );
		exit;
	}
} );
// WooCommerce would send accounts without shop caps to My Account; delivery boys work in wp-admin.
add_filter( 'woocommerce_prevent_admin_access', function ( $prevent ) {
	return current_user_can( 'pcs_deliver' ) ? false : $prevent;
} );
add_filter( 'show_admin_bar', function ( $s ) {
	return pcs_is_driver() ? false : $s;
}, 99 );
add_filter( 'admin_body_class', function ( $c ) {
	return pcs_is_driver() ? $c . ' pcs-driver' : $c;
} );
add_action( 'admin_head', function () {
	if ( ! pcs_is_driver() ) {
		return;
	}
	echo '<meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Afacad:wght@400..700&display=swap"><style>
	body.pcs-driver,body.pcs-driver *{font-family:"Afacad",system-ui,sans-serif}
	body.pcs-driver{background:#f3f3f3;font-size:18px}
	body.pcs-driver #adminmenumain,body.pcs-driver #wpfooter,body.pcs-driver #wpadminbar,body.pcs-driver .update-nag,body.pcs-driver .notice:not(.pcs-keep){display:none!important}
	body.pcs-driver #wpcontent,body.pcs-driver #wpfooter{margin-left:0!important;padding-left:0!important}
	body.pcs-driver.wp-admin{padding-top:0!important}
	html.wp-toolbar{padding-top:0!important}
	</style>';
} );
add_filter( 'admin_footer_text', function ( $t ) {
	return pcs_is_driver() ? '' : $t;
} );

/* ---------------- the delivery boy's screen ---------------- */

function pcs_driver_page() {
	if ( ! pcs_is_driver() ) {
		return;
	}
	$me     = get_current_user_id();
	$u      = wp_get_current_user();
	$duty   = 'no' !== get_user_meta( $me, 'pcs_on_duty', true );
	$mine   = array();
	foreach ( pcs_active_orders() as $o ) {
		if ( pcs_order_driver( $o ) === $me ) {
			$mine[] = $o;
		}
	}
	$today0 = strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00' );
	$done   = array();
	foreach ( wc_get_orders( array( 'status' => 'completed', 'limit' => 100, 'orderby' => 'date_modified', 'order' => 'DESC', 'date_modified' => $today0 . '...' . ( time() + 60 ) ) ) as $o ) {
		if ( pcs_order_driver( $o ) === $me ) {
			$done[] = $o;
		}
	}
	$to_collect = 0;
	foreach ( $mine as $o ) {
		if ( 'cod' === $o->get_payment_method() && ! $o->get_meta( '_pcs_issue' ) ) {
			$to_collect += (float) $o->get_total();
		}
	}
	$collected = 0;
	foreach ( $done as $o ) {
		$collected += (float) $o->get_meta( '_pcs_cash' );
	}
	$act = function ( $order, $what ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=pcs_driver_act&order=' . $order->get_id() . '&what=' . $what ), 'pcs_da_' . $order->get_id() );
	};
	?>
	<div class="pcs-dv">
		<style>
		.pcs-dv{max-width:620px;margin:0 auto;padding:14px 14px 80px}
		.pcs-dv *{box-sizing:border-box}
		.dv-top{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
		.dv-top h1{font-size:26px;margin:0;font-weight:700}
		.dv-top a{color:#c8102e;font-weight:600;text-decoration:none;font-size:16px}
		.dv-duty{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e3e3e3;padding:12px 16px;margin-bottom:12px}
		.dv-duty b{font-size:19px}
		.dv-duty a{display:inline-block;padding:10px 20px;font-weight:700;text-decoration:none;color:#fff;font-size:17px}
		.dv-duty .on{background:#1e7a3c}.dv-duty .off{background:#888}
		.dv-tiles{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
		.dv-tile{background:#fff;border:1px solid #e3e3e3;border-top:4px solid #c8102e;padding:12px 14px}
		.dv-tile b{display:block;font-size:30px;line-height:1.1;color:#c8102e}
		.dv-tile span{font-size:15px;color:#555}
		.dv-h{font-size:21px;margin:18px 0 8px;font-weight:700}
		.dv-card{background:#fff;border:1px solid #e3e3e3;border-left:5px solid #c8102e;margin-bottom:14px;padding:14px 16px}
		.dv-card.wait{border-left-color:#f0b429}.dv-card.go{border-left-color:#2c7be5}.dv-card.ok{border-left-color:#1e7a3c}.dv-card.bad{border-left-color:#000;background:#fff5f5}
		.dv-row{display:flex;justify-content:space-between;align-items:center;gap:8px}
		.dv-id{font-weight:700;font-size:20px}
		.dv-badge{font-size:13px;font-weight:700;text-transform:uppercase;padding:3px 9px;background:#eee;letter-spacing:.03em}
		.dv-slot{color:#555;font-size:16px;margin:2px 0 8px}
		.dv-name{font-size:20px;font-weight:700;margin:6px 0 2px}
		.dv-addr{font-size:18px;line-height:1.35;margin:0 0 8px}
		.dv-note{background:#fff8e1;border-left:4px solid #f0b429;padding:8px 12px;margin:8px 0;font-size:16px}
		.dv-items{font-size:17px;margin:8px 0;padding:8px 12px;background:#f7f7f7}
		.dv-cash{background:#c8102e;color:#fff;font-size:22px;font-weight:700;text-align:center;padding:10px;margin:10px 0}
		.dv-paid{background:#e8f6ec;color:#1e7a3c;font-weight:700;text-align:center;padding:8px;margin:10px 0}
		.dv-two{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0}
		.dv-btn{display:block;text-align:center;padding:15px 10px;font-size:19px;font-weight:700;text-decoration:none;border:0;cursor:pointer;width:100%;color:#fff;background:#1c1c1c;font-family:inherit}
		.dv-btn.red{background:#c8102e}.dv-btn.green{background:#1e7a3c}.dv-btn.blue{background:#2c7be5}.dv-btn.grey{background:#999}
		.dv-btn:hover{color:#fff;opacity:.92}
		.dv-wait{text-align:center;color:#666;padding:10px;font-size:17px}
		.dv-prob summary{cursor:pointer;color:#c8102e;font-weight:600;padding:12px 0 4px;font-size:17px}
		.dv-prob form{display:grid;gap:8px;margin-top:6px}
		.dv-prob select,.dv-prob input{font-size:18px;padding:10px;width:100%}
		.dv-empty{background:#fff;border:1px solid #e3e3e3;padding:34px 16px;text-align:center;color:#666;font-size:19px}
		</style>
		<div class="dv-top"><h1>Hi, <?php echo esc_html( $u->display_name ); ?></h1><span><a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Password</a> · <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Logout</a></span></div>

		<div class="dv-duty">
			<div><b><?php echo $duty ? 'You are ON duty' : 'You are OFF duty'; ?></b><br><span style="color:#666;font-size:15px"><?php echo $duty ? 'New orders are sent to you.' : 'You will not get new orders.'; ?></span></div>
			<a class="<?php echo $duty ? 'off' : 'on'; ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pcs_driver_duty&to=' . ( $duty ? 'no' : 'yes' ) ), 'pcs_duty' ) ); ?>"><?php echo $duty ? 'Go off duty' : 'Go on duty'; ?></a>
		</div>

		<div class="dv-tiles">
			<div class="dv-tile"><b><?php echo count( $mine ); ?></b><span>To deliver</span></div>
			<div class="dv-tile"><b><?php echo count( $done ); ?></b><span>Delivered today</span></div>
			<div class="dv-tile"><b><?php echo esc_html( pcs_money( $to_collect ) ); ?></b><span>Cash to collect</span></div>
			<div class="dv-tile"><b><?php echo esc_html( pcs_money( $collected ) ); ?></b><span>Cash collected today</span></div>
		</div>

		<h2 class="dv-h">My orders</h2>
		<?php if ( ! $mine ) : ?><div class="dv-empty"><?php echo $duty ? 'No order right now.<br>You will see new orders here.' : 'You are off duty.'; ?></div><?php endif; ?>
		<?php foreach ( $mine as $o ) :
			$st    = $o->get_status();
			$issue = $o->get_meta( '_pcs_issue' );
			$cls   = $issue ? 'bad' : ( 'pcs-out' === $st ? 'go' : ( 'pcs-packed' === $st ? 'red' : 'wait' ) );
			$addr  = trim( $o->get_billing_address_1() . ', ' . $o->get_meta( '_pcs_area' ) . ' ' . $o->get_billing_postcode() );
			$map   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $addr . ', ' . pcs_opt( 'city' ) );
			$slot  = pcs_order_slot_text( $o );
			?>
		<div class="dv-card <?php echo esc_attr( $cls ); ?>">
			<div class="dv-row"><span class="dv-id">#<?php echo (int) $o->get_id(); ?></span><span class="dv-badge"><?php echo esc_html( $issue ? 'Problem reported' : pcs_status_label( $st ) ); ?></span></div>
			<?php if ( $slot ) : ?><div class="dv-slot">Slot: <?php echo esc_html( $slot ); ?></div><?php endif; ?>
			<div class="dv-name"><?php echo esc_html( $o->get_billing_first_name() ); ?></div>
			<p class="dv-addr"><?php echo esc_html( $addr ); ?></p>
			<div class="dv-two"><a class="dv-btn green" href="tel:<?php echo esc_attr( $o->get_billing_phone() ); ?>">Call</a><a class="dv-btn blue" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">Map</a></div>
			<div class="dv-items"><?php echo esc_html( pcs_order_items_text( $o ) ); ?></div>
			<?php if ( $o->get_customer_note() ) : ?><div class="dv-note">Note: <?php echo esc_html( $o->get_customer_note() ); ?></div><?php endif; ?>
			<?php if ( 'cod' === $o->get_payment_method() ) : ?><div class="dv-cash">Collect <?php echo esc_html( pcs_money( $o->get_total() ) ); ?> cash</div><?php else : ?><div class="dv-paid">Already paid</div><?php endif; ?>
			<?php if ( $issue ) : ?>
				<div class="dv-note">Problem: <?php echo esc_html( $issue ); ?>. The shop has been told.</div>
			<?php elseif ( 'pcs-packed' === $st ) : ?>
				<a class="dv-btn red" href="<?php echo esc_url( $act( $o, 'out' ) ); ?>">Picked up. Start delivery</a>
			<?php elseif ( 'pcs-out' === $st ) : ?>
				<a class="dv-btn green" href="<?php echo esc_url( $act( $o, 'delivered' ) ); ?>" onclick="return confirm('Delivered<?php echo 'cod' === $o->get_payment_method() ? ' and cash collected' : ''; ?>?')">Delivered<?php echo 'cod' === $o->get_payment_method() ? ' + cash taken' : ''; ?></a>
			<?php else : ?>
				<div class="dv-wait">The shop is packing this order. Wait for it.</div>
			<?php endif; ?>
			<?php if ( ! $issue && in_array( $st, array( 'pcs-packed', 'pcs-out' ), true ) ) : ?>
			<details class="dv-prob"><summary>Having a problem?</summary>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pcs_driver_act"><input type="hidden" name="what" value="issue"><input type="hidden" name="order" value="<?php echo (int) $o->get_id(); ?>">
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'pcs_da_' . $o->get_id() ) ); ?>">
					<select name="reason"><option>Customer is not picking up the phone</option><option>Customer is not at the address</option><option>Address is wrong or not found</option><option>Customer refused the order</option><option>Other problem</option></select>
					<button class="dv-btn grey" type="submit">Tell the shop</button>
				</form>
			</details>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>

		<?php if ( $done ) : ?>
		<h2 class="dv-h">Delivered today</h2>
		<?php foreach ( $done as $o ) : ?>
		<div class="dv-card ok"><div class="dv-row"><span class="dv-id">#<?php echo (int) $o->get_id(); ?> · <?php echo esc_html( $o->get_billing_first_name() ); ?></span><span><?php echo esc_html( pcs_money( $o->get_total() ) ); ?></span></div></div>
		<?php endforeach; endif; ?>
		<script>
		setTimeout(function(){ if(!document.hidden && !document.querySelector('details[open]') && !document.activeElement.matches('select,input')) location.reload(); }, 60000);
		</script>
	</div>
	<?php
}

add_action( 'admin_post_pcs_driver_duty', function () {
	if ( ! pcs_is_driver() || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'pcs_duty' ) ) {
		wp_die( 'Not allowed.' );
	}
	$on = ( $_GET['to'] ?? '' ) === 'yes';
	update_user_meta( get_current_user_id(), 'pcs_on_duty', $on ? 'yes' : 'no' );
	if ( $on ) {
		pcs_assign_waiting();
	}
	wp_safe_redirect( admin_url( 'admin.php?page=pcs-delivery' ) );
	exit;
} );

add_action( 'admin_post_pcs_driver_act', function () {
	$id = (int) ( $_GET['order'] ?? 0 );
	if ( ! pcs_is_driver() || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'pcs_da_' . $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$o  = wc_get_order( $id );
	$me = get_current_user_id();
	if ( ! $o || pcs_order_driver( $o ) !== $me ) {
		wp_die( 'This order is not assigned to you.' );
	}
	$what = sanitize_key( $_GET['what'] ?? '' );
	$name = wp_get_current_user()->display_name;
	if ( 'out' === $what && 'pcs-packed' === $o->get_status() ) {
		$o->update_status( 'pcs-out', "$name picked up the order. " );
	} elseif ( 'delivered' === $what && in_array( $o->get_status(), array( 'pcs-out', 'pcs-packed' ), true ) ) {
		if ( 'cod' === $o->get_payment_method() ) {
			$o->update_meta_data( '_pcs_cash', (float) $o->get_total() );
			$o->set_date_paid( time() );
		}
		$o->update_meta_data( '_pcs_delivered_at', time() );
		$o->save();
		$o->update_status( 'completed', "$name delivered the order. " );
	} elseif ( 'issue' === $what && in_array( $o->get_status(), array( 'pcs-out', 'pcs-packed' ), true ) ) {
		$reason = sanitize_text_field( wp_unslash( $_GET['reason'] ?? 'Problem' ) );
		$o->update_meta_data( '_pcs_issue', $reason );
		$o->add_order_note( "$name reported a problem: $reason" );
		$o->save();
		pcs_assign_waiting(); // he is free for the next order
	}
	wp_safe_redirect( admin_url( 'admin.php?page=pcs-delivery' ) );
	exit;
} );

/* ---------------- shop side: assign by hand, see the team ---------------- */

add_action( 'admin_post_pcs_assign', function () {
	$id = (int) ( $_POST['order'] ?? 0 );
	if ( ! current_user_can( 'edit_shop_orders' ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'pcs_as_' . $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$o = wc_get_order( $id );
	$d = $_POST['driver'] ?? '';
	if ( $o ) {
		if ( 'auto' === $d ) {
			$o->delete_meta_data( '_pcs_driver' );
			$o->delete_meta_data( '_pcs_issue' );
			$o->save();
			if ( ! pcs_try_assign( wc_get_order( $id ) ) ) {
				$o->add_order_note( 'Auto: waiting for a free delivery boy.' );
			}
		} elseif ( 'none' === $d ) {
			pcs_assign_to( $o, 0, wp_get_current_user()->display_name );
		} elseif ( pcs_is_driver( (int) $d ) ) {
			pcs_assign_to( $o, (int) $d, wp_get_current_user()->display_name );
		}
	}
	wp_safe_redirect( admin_url( 'admin.php?page=pcs-home' ) );
	exit;
} );
add_action( 'admin_post_pcs_assign_waiting', function () {
	if ( ! current_user_can( 'edit_shop_orders' ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'pcs_aw' ) ) {
		wp_die( 'Not allowed.' );
	}
	pcs_assign_waiting();
	wp_safe_redirect( admin_url( 'admin.php?page=pcs-home' ) );
	exit;
} );

/** Small panel for Shop home: the team and what each is doing. */
function pcs_team_panel( $orders ) {
	$loads = pcs_driver_loads( $orders );
	$today = strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00' );
	$done  = array();
	foreach ( wc_get_orders( array( 'status' => 'completed', 'limit' => 200, 'date_modified' => $today . '...' . ( time() + 60 ) ) ) as $o ) {
		$d = pcs_order_driver( $o );
		if ( $d ) {
			$done[ $d ]['n']    = ( $done[ $d ]['n'] ?? 0 ) + 1;
			$done[ $d ]['cash'] = ( $done[ $d ]['cash'] ?? 0 ) + (float) $o->get_meta( '_pcs_cash' );
		}
	}
	$waiting = 0;
	foreach ( $orders as $o ) {
		if ( ! pcs_order_driver( $o ) ) {
			$waiting++;
		}
	}
	$drivers = pcs_drivers();
	if ( ! $drivers ) {
		return;
	}
	echo '<h2>Delivery team</h2><div class="pcs-team">';
	foreach ( $drivers as $d ) {
		$on = 'no' !== get_user_meta( $d->ID, 'pcs_on_duty', true );
		$n  = $loads[ $d->ID ] ?? 0;
		printf(
			'<div class="pcs-tm %s"><b>%s</b><span class="st">%s</span><span>%s</span><span>Delivered today: %d · Cash: %s</span></div>',
			$on ? ( $n ? 'busy' : 'free' ) : 'off',
			esc_html( $d->display_name ),
			$on ? ( $n ? 'Busy (' . (int) $n . ' order' . ( $n > 1 ? 's' : '' ) . ')' : 'Free' ) : 'Off duty',
			'',
			(int) ( $done[ $d->ID ]['n'] ?? 0 ),
			esc_html( pcs_money( $done[ $d->ID ]['cash'] ?? 0 ) )
		);
	}
	echo '</div>';
	if ( $waiting ) {
		printf( '<p class="pcs-wait"><b>%d order%s waiting for a free delivery boy.</b> <a class="button button-primary" href="%s">Assign now</a></p>', $waiting, $waiting > 1 ? 's are' : ' is', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pcs_assign_waiting' ), 'pcs_aw' ) ) );
	}
}

/** Driver chip + dropdown for one order on Shop home. */
function pcs_assign_box( $order ) {
	$cur   = pcs_order_driver( $order );
	$issue = $order->get_meta( '_pcs_issue' );
	$loads = pcs_driver_loads();
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pcs-as">';
	echo '<input type="hidden" name="action" value="pcs_assign"><input type="hidden" name="order" value="' . (int) $order->get_id() . '">';
	wp_nonce_field( 'pcs_as_' . $order->get_id(), '_wpnonce', false );
	if ( $issue ) {
		echo '<div class="pcs-issue">Problem: ' . esc_html( $issue ) . '</div>';
	}
	echo '<label>Delivery boy</label><select name="driver" onchange="this.form.submit()">';
	echo '<option value="' . ( $cur ? 'none' : 'auto' ) . '">' . ( $cur ? 'Remove' : 'Waiting / pick for me' ) . '</option>';
	if ( $cur ) {
		echo '<option value="auto">Pick another (auto)</option>';
	}
	foreach ( pcs_drivers() as $d ) {
		printf( '<option value="%d" %s>%s%s</option>', (int) $d->ID, selected( $cur, $d->ID, false ), esc_html( $d->display_name ), isset( $loads[ $d->ID ] ) ? ' (' . (int) $loads[ $d->ID ] . ' now)' : ' (free)' );
	}
	echo '</select></form>';
}

/* ---------------- create the delivery boys (run once) ---------------- */

add_filter( 'pcmcp_allowed_actions', function ( $a ) {
	$a['pcs_create_drivers'] = 'pcs_create_drivers';
	return $a;
} );

function pcs_create_drivers() {
	$out = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$login = 'delivery' . $i;
		if ( username_exists( $login ) ) {
			$out[] = "$login already exists";
			continue;
		}
		$pw  = wp_generate_password( 12, false, false );
		$uid = wp_insert_user( array( 'user_login' => $login, 'user_pass' => $pw, 'user_email' => "wpgainonline+delivery$i@gmail.com", 'display_name' => "Delivery Boy $i", 'first_name' => 'Delivery', 'last_name' => "Boy $i", 'role' => PCS_DRIVER_ROLE ) );
		$out[] = is_wp_error( $uid ) ? $uid->get_error_message() : "$login / $pw";
	}
	return implode( ' ; ', $out );
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pcs_statuses() {
	return array( 'new' => 'New', 'confirmed' => 'Confirmed', 'packed' => 'Packed', 'out' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled' );
}

function pcs_notify_new_order( $id ) {
	$m = get_post_meta( $id );
	$q = maybe_unserialize( $m['pcs_quote'][0] ?? array() );
	$t = "New order #$id\n\n";
	foreach ( (array) ( $q['lines'] ?? array() ) as $l ) {
		$t .= "{$l['q']} x {$l['name']} ({$l['label']}" . ( $l['cut'] ? ", {$l['cut']}" : '' ) . ") = " . pcs_money( $l['total'] ) . "\n";
	}
	$t .= "\nTotal: " . pcs_money( $m['pcs_total'][0] ?? 0 ) . ' (' . strtoupper( $m['pcs_pay'][0] ?? '' ) . ")\n";
	$t .= 'Customer: ' . ( $m['pcs_name'][0] ?? '' ) . ' ' . ( $m['pcs_phone'][0] ?? '' ) . "\n";
	$t .= 'Address: ' . ( $m['pcs_address'][0] ?? '' ) . ', ' . ( $m['pcs_pincode'][0] ?? '' ) . "\n";
	$t .= 'Slot: ' . ( $m['pcs_slot_date'][0] ?? '' ) . ' ' . ( $m['pcs_slot'][0] ?? '' ) . "\n";
	$t .= "\nManage: " . admin_url( "post.php?post=$id&action=edit" );
	wp_mail( pcs_opt( 'email' ), "New order #$id", $t );
}

/* ---------- admin screen ---------- */

add_action( 'add_meta_boxes_pcs_order', function () {
	add_meta_box( 'pcs_order_box', 'Order details', 'pcs_order_box', 'pcs_order', 'normal', 'high' );
} );

function pcs_order_box( $post ) {
	wp_nonce_field( 'pcs_ord', 'pcs_ord_nonce' );
	$g = function ( $k ) use ( $post ) { return get_post_meta( $post->ID, $k, true ); };
	$q = (array) $g( 'pcs_quote' );
	echo '<p><label><strong>Status</strong> <select name="pcs_status">';
	foreach ( pcs_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $g( 'pcs_status' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></label> &nbsp; <label><strong>Payment</strong> <select name="pcs_pay_status">';
	foreach ( array( 'pending' => 'Pending', 'paid' => 'Paid' ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $g( 'pcs_pay_status' ), $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></label> (' . esc_html( strtoupper( (string) $g( 'pcs_pay' ) ) ) . ')</p>';

	echo '<table class="widefat striped"><thead><tr><th>Item</th><th>Weight</th><th>Cut</th><th>Qty</th><th>Amount</th></tr></thead><tbody>';
	foreach ( (array) ( $q['lines'] ?? array() ) as $l ) {
		echo '<tr><td>' . esc_html( $l['name'] ) . '</td><td>' . esc_html( $l['label'] ) . '</td><td>' . esc_html( $l['cut'] ) . '</td><td>' . (int) $l['q'] . '</td><td>' . esc_html( pcs_money( $l['total'] ) ) . '</td></tr>';
	}
	echo '</tbody></table><p>Subtotal ' . esc_html( pcs_money( $q['subtotal'] ?? 0 ) ) . ' · Discount ' . esc_html( pcs_money( $q['discount'] ?? 0 ) ) . ' · Delivery ' . esc_html( pcs_money( $q['delivery'] ?? 0 ) ) . ' · <strong>Total ' . esc_html( pcs_money( $g( 'pcs_total' ) ) ) . '</strong></p>';
	echo '<h4>Customer</h4><p>' . esc_html( $g( 'pcs_name' ) ) . ' · <a href="tel:' . esc_attr( $g( 'pcs_phone' ) ) . '">' . esc_html( $g( 'pcs_phone' ) ) . '</a></p>';
	echo '<p>' . nl2br( esc_html( $g( 'pcs_address' ) ) ) . '<br>' . esc_html( $g( 'pcs_landmark' ) ) . '<br>' . esc_html( $g( 'pcs_area' ) . ' ' . $g( 'pcs_pincode' ) ) . '</p>';
	echo '<p><strong>Slot:</strong> ' . esc_html( $g( 'pcs_slot_date' ) . ' ' . $g( 'pcs_slot' ) ) . '</p>';
	if ( $g( 'pcs_note' ) ) {
		echo '<p><strong>Customer note:</strong> ' . esc_html( $g( 'pcs_note' ) ) . '</p>';
	}
	echo '<p><strong>Internal note</strong><br><textarea class="large-text" rows="3" name="pcs_admin_note">' . esc_textarea( $g( 'pcs_admin_note' ) ) . '</textarea></p>';
}

add_action( 'save_post_pcs_order', function ( $id ) {
	if ( ! isset( $_POST['pcs_ord_nonce'] ) || ! wp_verify_nonce( $_POST['pcs_ord_nonce'], 'pcs_ord' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$st = sanitize_key( $_POST['pcs_status'] ?? '' );
	if ( isset( pcs_statuses()[ $st ] ) ) {
		update_post_meta( $id, 'pcs_status', $st );
	}
	update_post_meta( $id, 'pcs_pay_status', 'paid' === ( $_POST['pcs_pay_status'] ?? '' ) ? 'paid' : 'pending' );
	update_post_meta( $id, 'pcs_admin_note', sanitize_textarea_field( wp_unslash( $_POST['pcs_admin_note'] ?? '' ) ) );
} );

add_filter( 'manage_pcs_order_posts_columns', function () {
	return array( 'cb' => '<input type="checkbox">', 'title' => 'Order', 'pcs_cust' => 'Customer', 'pcs_slot' => 'Delivery slot', 'pcs_total' => 'Total', 'pcs_status' => 'Status', 'date' => 'Placed' );
} );
add_action( 'manage_pcs_order_posts_custom_column', function ( $col, $id ) {
	if ( 'pcs_cust' === $col ) {
		echo esc_html( get_post_meta( $id, 'pcs_name', true ) . ' · ' . get_post_meta( $id, 'pcs_phone', true ) );
	} elseif ( 'pcs_slot' === $col ) {
		echo esc_html( get_post_meta( $id, 'pcs_slot_date', true ) . ' ' . get_post_meta( $id, 'pcs_slot', true ) );
	} elseif ( 'pcs_total' === $col ) {
		echo esc_html( pcs_money( get_post_meta( $id, 'pcs_total', true ) ) . ' ' . strtoupper( (string) get_post_meta( $id, 'pcs_pay', true ) ) . ' ' . get_post_meta( $id, 'pcs_pay_status', true ) );
	} elseif ( 'pcs_status' === $col ) {
		$s = get_post_meta( $id, 'pcs_status', true );
		echo '<strong>' . esc_html( pcs_statuses()[ $s ] ?? $s ) . '</strong>';
	}
}, 10, 2 );

add_filter( 'views_edit-pcs_order', function ( $views ) {
	foreach ( pcs_statuses() as $k => $l ) {
		$n = count( get_posts( array( 'post_type' => 'pcs_order', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'pcs_status', 'meta_value' => $k ) ) );
		$views[ 'pcs_' . $k ] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=pcs_order&pcs_status=' . $k ) ) . '">' . esc_html( $l ) . " ($n)</a>";
	}
	return $views;
} );
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'pcs_order' === $q->get( 'post_type' ) && ! empty( $_GET['pcs_status'] ) ) {
		$q->set( 'meta_key', 'pcs_status' );
		$q->set( 'meta_value', sanitize_key( $_GET['pcs_status'] ) );
	}
} );

/* ---------- customer side ---------- */

function pcs_user_orders( $user_id ) {
	return get_posts( array( 'post_type' => 'pcs_order', 'author' => $user_id, 'numberposts' => 30, 'post_status' => 'publish' ) );
}

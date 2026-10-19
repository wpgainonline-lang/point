<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product card used on home, category and search pages. */
function pcs_card( $id ) {
	$d     = pcs_card_data( $id );
	$img   = get_the_post_thumbnail_url( $id, 'medium_large' );
	$note  = get_post_meta( $id, 'pcs_note', true );
	$mrp   = pcs_mrp_kg( $id );
	ob_start();
	?>
	<article class="card" data-card='<?php echo esc_attr( wp_json_encode( $d ) ); ?>'>
		<a class="card-img" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $d['name'] ); ?>" loading="lazy" width="400" height="300"><?php endif; ?></a>
		<div class="card-body">
			<h3 class="card-title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( $d['name'] ); ?></a></h3>
			<?php if ( $note ) : ?><p class="muted"><?php echo esc_html( $note ); ?></p><?php endif; ?>
			<?php if ( $d['inStock'] ) : ?>
				<div class="row">
					<select class="sel-w" aria-label="Weight"><?php foreach ( $d['weights'] as $i => $g ) : ?><option value="<?php echo (int) $g; ?>"><?php echo esc_html( pcs_weight_label( $g ) ); ?></option><?php endforeach; ?></select>
					<span class="price"><span class="p-now"><?php echo esc_html( pcs_money( $d['prices'][0] ) ); ?></span><?php if ( $mrp ) : ?> <s class="muted"><?php echo esc_html( pcs_money( pcs_price_for( $id, $d['weights'][0], true ) ) ); ?></s><?php endif; ?></span>
				</div>
				<button type="button" class="btn btn-block js-add">Add</button>
			<?php else : ?>
				<p class="muted"><?php echo pcs_price_kg( $id ) > 0 ? 'Out of stock' : 'Call to order: ' . esc_html( pcs_opt( 'phone' ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function pcs_login_box() {
	ob_start();
	?>
	<div class="login-box" id="pcs-login">
		<h3>Login with your mobile number</h3>
		<div class="step step-1">
			<label>Name<input type="text" id="lg-name" autocomplete="name" placeholder="Your name"></label>
			<label>Mobile number<input type="tel" id="lg-phone" inputmode="numeric" maxlength="10" autocomplete="tel-national" placeholder="10-digit mobile number"></label>
			<?php if ( 'email' === pcs_opt( 'otp_provider', 'email' ) ) : ?><label>Email (we send the code here)<input type="email" id="lg-email" autocomplete="email" placeholder="you@example.com"></label><?php endif; ?>
			<button type="button" class="btn btn-block" id="lg-send">Send code</button>
		</div>
		<div class="step step-2" hidden>
			<label>Enter the 6-digit code<input type="text" id="lg-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></label>
			<button type="button" class="btn btn-block" id="lg-verify">Verify &amp; continue</button>
			<button type="button" class="link" id="lg-back">Change number</button>
		</div>
		<p class="msg" id="lg-msg" role="alert"></p>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'pcs_checkout', function () {
	$methods = pcs_payment_methods();
	ob_start();
	?>
	<div id="pcs-checkout" class="checkout">
		<div class="co-main">
			<section class="panel" id="co-cart"><h2>Your cart</h2><div id="co-lines"></div><p id="co-empty" class="muted" hidden>Your cart is empty. <a href="<?php echo esc_url( get_post_type_archive_link( 'pcs_product' ) ); ?>">Browse products</a></p></section>
			<?php if ( ! is_user_logged_in() ) : echo '<section class="panel" id="co-login">' . pcs_login_box() . '</section>'; else : $u = wp_get_current_user(); ?>
			<section class="panel" id="co-form">
				<h2>Delivery address</h2>
				<label>Name<input type="text" id="a-name" value="<?php echo esc_attr( $u->display_name ); ?>"></label>
				<p class="muted">Mobile: <?php echo esc_html( get_user_meta( $u->ID, 'pcs_phone', true ) ); ?></p>
				<label>Address<textarea id="a-line" rows="3" placeholder="House no, street, area"></textarea></label>
				<label>Landmark (optional)<input type="text" id="a-landmark"></label>
				<label>Pincode<input type="text" id="a-pin" inputmode="numeric" maxlength="6"></label>
				<p class="msg" id="a-pin-msg"></p>
				<h2>Delivery slot</h2>
				<div id="co-slots"></div>
				<h2>Payment</h2>
				<?php foreach ( $methods as $k => $l ) : ?><label class="radio"><input type="radio" name="pay" value="<?php echo esc_attr( $k ); ?>" <?php checked( array_key_first( $methods ), $k ); ?>> <?php echo esc_html( $l ); ?></label><?php endforeach; ?>
				<label>Note for the butcher (optional)<input type="text" id="a-note" placeholder="e.g. small pieces, skin off"></label>
			</section>
			<?php endif; ?>
		</div>
		<aside class="co-side panel">
			<h2>Bill</h2>
			<div class="coupon"><input type="text" id="co-coupon" placeholder="Coupon code"><button type="button" class="btn btn-line" id="co-apply">Apply</button></div>
			<p class="msg" id="co-cmsg"></p>
			<dl id="co-bill"></dl>
			<?php if ( is_user_logged_in() ) : ?><button type="button" class="btn btn-block" id="co-place">Place order</button><?php endif; ?>
			<p class="msg" id="co-msg" role="alert"></p>
		</aside>
	</div>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'pcs_account', function () {
	if ( ! is_user_logged_in() ) {
		return '<div class="panel narrow">' . pcs_login_box() . '</div>';
	}
	$u = wp_get_current_user();
	ob_start();
	echo '<div class="panel"><p><strong>' . esc_html( $u->display_name ) . '</strong> · ' . esc_html( get_user_meta( $u->ID, 'pcs_phone', true ) ) . ' <button type="button" class="link" id="pcs-logout">Log out</button></p></div>';
	if ( ! empty( $_GET['order'] ) ) {
		echo '<div class="panel ok"><strong>Thank you!</strong> Your order #' . (int) $_GET['order'] . ' is placed. We will confirm it shortly.</div>';
	}
	echo '<h2>Your orders</h2>';
	$orders = pcs_user_orders( $u->ID );
	if ( ! $orders ) {
		echo '<p class="muted">No orders yet.</p>';
	}
	foreach ( $orders as $o ) {
		$q = (array) get_post_meta( $o->ID, 'pcs_quote', true );
		$s = get_post_meta( $o->ID, 'pcs_status', true );
		echo '<div class="panel"><div class="row"><strong>Order #' . (int) $o->ID . '</strong><span class="tag">' . esc_html( pcs_statuses()[ $s ] ?? $s ) . '</span></div>';
		echo '<p class="muted">' . esc_html( get_the_date( '', $o ) . ' · ' . get_post_meta( $o->ID, 'pcs_slot_date', true ) . ' ' . get_post_meta( $o->ID, 'pcs_slot', true ) ) . '</p><ul>';
		foreach ( (array) ( $q['lines'] ?? array() ) as $l ) {
			echo '<li>' . (int) $l['q'] . ' × ' . esc_html( $l['name'] . ' (' . $l['label'] . ( $l['cut'] ? ', ' . $l['cut'] : '' ) . ')' ) . '</li>';
		}
		echo '</ul><p><strong>' . esc_html( pcs_money( get_post_meta( $o->ID, 'pcs_total', true ) ) ) . '</strong> · ' . esc_html( strtoupper( (string) get_post_meta( $o->ID, 'pcs_pay', true ) ) ) . '</p></div>';
	}
	return ob_get_clean();
} );

add_shortcode( 'pcs_contact', function () {
	ob_start();
	?>
	<div class="grid-2">
		<div class="panel">
			<h3>Visit us</h3><p><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></p>
			<h3>Call or WhatsApp</h3><p><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) ) ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a></p>
			<h3>Email</h3><p><a href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>"><?php echo esc_html( pcs_opt( 'email' ) ); ?></a></p>
			<h3>Store timings</h3><p><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></p>
		</div>
		<form class="panel" id="pcs-contact">
			<h3>Tell us what you need</h3>
			<label>Name<input type="text" name="name" required></label>
			<label>Phone<input type="tel" name="phone"></label>
			<label>Email<input type="email" name="email"></label>
			<label>Message<textarea name="message" rows="4"></textarea></label>
			<button class="btn btn-block" type="submit">Send</button>
			<p class="msg" id="ct-msg"></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
} );

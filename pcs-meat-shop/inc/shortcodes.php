<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pcs_login_box() {
	ob_start();
	?>
	<div class="login-box" id="pcs-login">
		<h3>Login with your mobile number</h3>
		<div class="lstep step-1">
			<label>Name<input type="text" id="lg-name" autocomplete="name" placeholder="Your name"></label>
			<label>Mobile number<input type="tel" id="lg-phone" inputmode="numeric" maxlength="10" autocomplete="tel-national" placeholder="10-digit mobile number"></label>
			<?php if ( 'email' === pcs_opt( 'otp_provider', 'email' ) ) : ?><label>Email (we send the code here)<input type="email" id="lg-email" autocomplete="email" placeholder="you@example.com"></label><?php endif; ?>
			<button type="button" class="btn btn-block" id="lg-send">Send code</button>
		</div>
		<div class="lstep step-2" hidden>
			<label>Enter the 6-digit code<input type="text" id="lg-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></label>
			<button type="button" class="btn btn-block" id="lg-verify">Verify &amp; continue</button>
			<button type="button" class="link" id="lg-back">Change number</button>
		</div>
		<p class="msg" id="lg-msg" role="alert"></p>
	</div>
	<?php
	return ob_get_clean();
}

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

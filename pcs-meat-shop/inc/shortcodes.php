<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pcs_login_box( $stack = false ) {
	$email_otp = 'email' === pcs_opt( 'otp_provider', 'email' );
	ob_start();
	?>
	<div class="auth<?php echo $stack ? ' auth-stack' : ''; ?>" id="pcs-login">
		<section class="auth-col auth-login">
			<h2>Login</h2>
			<form id="lg-pw" class="lform" novalidate>
				<label>Valid Mail Id / Mobile no.*<input type="text" id="lp-id" autocomplete="username" placeholder="Valid Mail Id / Mobile no.*"></label>
				<label class="pwrap">Password*<input type="password" id="lp-pw" autocomplete="current-password" placeholder="Password*"><button type="button" class="eye" aria-label="Show password" data-eye>Show</button></label>
				<p class="lrow"><button type="button" class="link" data-show="lg-otp">Login With OTP</button><a class="link" href="<?php echo esc_url( wc_lostpassword_url() ); ?>">Forgot your password?</a></p>
				<button type="submit" class="btn">Login</button>
			</form>
			<form id="lg-otp" class="lform" hidden novalidate>
				<div class="lstep step-1">
					<label>Mobile number*<input type="tel" id="lo-phone" inputmode="numeric" maxlength="10" autocomplete="tel-national" placeholder="10-digit mobile number"></label>
					<?php if ( $email_otp ) : ?><label>Email (we send the code here)*<input type="email" id="lo-email" autocomplete="email" placeholder="you@example.com"></label><?php endif; ?>
					<button type="submit" class="btn" id="lo-send">Send code</button>
				</div>
				<div class="lstep step-2" hidden>
					<label>Enter the 6-digit code<input type="text" id="lo-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></label>
					<button type="button" class="btn" id="lo-verify">Verify &amp; login</button>
				</div>
				<p class="lrow"><button type="button" class="link" data-show="lg-pw">Login with password</button></p>
			</form>
		</section>
		<aside class="auth-col auth-new">
			<h2>New Customer</h2>
			<div id="nc-intro">
				<p>Freshness delivered to your door. Create an account to order fresh chicken and mutton, choose your delivery slot and track your orders.</p>
				<button type="button" class="btn" data-show="lg-reg">Register</button>
			</div>
			<form id="lg-reg" class="lform" hidden novalidate>
				<div class="lstep step-1">
					<label>Name*<input type="text" id="lr-name" autocomplete="name" placeholder="Your name"></label>
					<label>Mobile number*<input type="tel" id="lr-phone" inputmode="numeric" maxlength="10" autocomplete="tel-national" placeholder="10-digit mobile number"></label>
					<label>Email<?php echo $email_otp ? '* (we send the code here)' : ' (optional)'; ?><input type="email" id="lr-email" autocomplete="email" placeholder="you@example.com"></label>
					<label>Create password*<input type="password" id="lr-pw" autocomplete="new-password" placeholder="At least 6 characters"></label>
					<button type="submit" class="btn" id="lr-send">Send code</button>
				</div>
				<div class="lstep step-2" hidden>
					<label>Enter the 6-digit code<input type="text" id="lr-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></label>
					<button type="button" class="btn" id="lr-verify">Verify &amp; create account</button>
				</div>
			</form>
		</aside>
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

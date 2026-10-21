<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* The WordPress login page (wp-admin) shows the shop's logo and colours. */

add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return pcs_opt( 'store_name' );
} );
add_action( 'login_head', function () {
	$logo = pcs_opt( 'logo' );
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Afacad:wght@400..700&display=swap">';
	?>
	<style>
	body.login{background:#fdf1f2;font-family:'Afacad',system-ui,sans-serif}
	body.login label,body.login input,body.login a,body.login p,body.login .button,body.login .message,body.login #login_error{font-family:'Afacad',system-ui,sans-serif}
	body.login .dashicons,body.login .dashicons-before:before{font-family:dashicons!important}
	body.login h1 a{<?php echo $logo ? 'background-image:url(' . esc_url( $logo ) . ');' : ''; ?>background-size:contain;background-repeat:no-repeat;background-position:center;width:190px;height:130px;margin:0 auto 14px}
	body.login #login{padding-top:5vh;width:min(360px,92vw)}
	body.login form{border:1px solid #efd3d6;border-top:4px solid #c8102e;box-shadow:none;border-radius:0;padding:26px 24px}
	body.login label{font-size:16px;font-weight:600}
	body.login .input,body.login input[type=text],body.login input[type=password]{font-size:18px;border-radius:0;border:1px solid #cfcfcf;box-shadow:none;padding:6px 10px}
	body.login input:focus{border-color:#c8102e;box-shadow:none;outline:0}
	body.login .button-primary,body.login #wp-submit{background:#c8102e;border:1px solid #c8102e;border-radius:0;box-shadow:none;text-shadow:none;font-size:17px;font-weight:700;padding:3px 22px;height:auto;min-height:40px}
	body.login .button-primary:hover,body.login #wp-submit:hover{background:#a50d26;border-color:#a50d26}
	body.login .button.wp-hide-pw{color:#c8102e}
	body.login #nav a,body.login #backtoblog a,body.login .privacy-policy-page-link a{color:#555;font-size:15px}
	body.login #nav a:hover,body.login #backtoblog a:hover{color:#c8102e}
	body.login .message,body.login .success,body.login #login_error{border-radius:0;box-shadow:none;border-left-color:#c8102e}
	.language-switcher{display:none}
	</style>
	<?php
} );

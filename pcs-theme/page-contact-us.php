<?php
/**
 * Contact page. Words are edited at Meat Shop → Contact page; address, phone, email and timings at Meat Shop → Settings.
 */
get_header();
$tel  = preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) );
$addr = str_replace( "\n", ', ', pcs_opt( 'address' ) );
$map  = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $addr );
$emb  = 'https://www.google.com/maps?q=' . rawurlencode( $addr ) . '&output=embed';
$wa   = pcs_opt( 'whatsapp' ) ? 'https://wa.me/' . rawurlencode( pcs_opt( 'whatsapp' ) ) : '';
?>
<section class="ab-hero ct-hero">
	<div class="wrap">
		<span class="kicker"><?php echo esc_html( pcs_contact_p( 'hero_kicker' ) ); ?></span>
		<h1><?php echo esc_html( pcs_contact_p( 'hero_title' ) ); ?></h1>
		<p><?php echo esc_html( pcs_contact_p( 'hero_text' ) ); ?></p>
	</div>
</section>

<section class="ct-cards"><div class="wrap">
	<div class="ct-card"><?php echo pcs_icon( 'pin' ); // phpcs:ignore ?><h3>Visit us</h3><p class="big"><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></p><p class="muted"><?php echo esc_html( pcs_contact_p( 'visit_note' ) ); ?></p><a class="ct-link" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">Get directions →</a></div>
	<div class="ct-card"><?php echo pcs_icon( 'phone' ); // phpcs:ignore ?><h3>Call or WhatsApp</h3><p class="big"><a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a></p><p class="muted"><?php echo esc_html( pcs_contact_p( 'call_note' ) ); ?></p><?php if ( $wa ) : ?><a class="ct-link" href="<?php echo esc_url( $wa ); ?>" rel="noopener">Chat on WhatsApp →</a><?php else : ?><a class="ct-link" href="tel:<?php echo esc_attr( $tel ); ?>">Call now →</a><?php endif; ?></div>
	<div class="ct-card"><?php echo pcs_icon( 'mail' ); // phpcs:ignore ?><h3>Email us</h3><p class="big"><a href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>"><?php echo esc_html( pcs_opt( 'email' ) ); ?></a></p><p class="muted"><?php echo esc_html( pcs_contact_p( 'email_note' ) ); ?></p><a class="ct-link" href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>">Send an email →</a></div>
	<div class="ct-card"><?php echo pcs_icon( 'clock' ); // phpcs:ignore ?><h3>Store timings</h3><p class="big"><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></p><p class="muted"><?php echo esc_html( pcs_contact_p( 'hours_note' ) ); ?></p></div>
</div></section>

<section class="section"><div class="wrap ct-main">
	<form class="panel ct-form" id="pcs-contact">
		<span class="kicker"><?php echo esc_html( pcs_contact_p( 'form_kicker' ) ); ?></span>
		<h2><?php echo esc_html( pcs_contact_p( 'form_title' ) ); ?></h2>
		<p class="muted"><?php echo esc_html( pcs_contact_p( 'form_text' ) ); ?></p>
		<div class="ct-row"><label>Name*<input type="text" name="name" required autocomplete="name"></label><label>Phone<input type="tel" name="phone" autocomplete="tel"></label></div>
		<label>Email*<input type="email" name="email" autocomplete="email"></label>
		<label>Message*<textarea name="message" rows="5" required></textarea></label>
		<button class="btn" type="submit">Send message</button>
		<p class="msg" id="ct-msg" role="status"></p>
	</form>
	<?php if ( 'no' !== strtolower( trim( pcs_contact_p( 'show_map' ) ) ) ) : ?>
	<div class="ct-map"><iframe title="Map to <?php echo esc_attr( pcs_opt( 'store_name' ) ); ?>" src="<?php echo esc_url( $emb ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" width="600" height="450"></iframe></div>
	<?php endif; ?>
</div></section>

<section class="ct-bulk"><div class="wrap">
	<div><h2><?php echo esc_html( pcs_contact_p( 'bulk_title' ) ); ?></h2><p><?php echo esc_html( pcs_contact_p( 'bulk_text' ) ); ?></p></div>
	<div class="ct-bulk-btns"><a class="btn btn-white" href="tel:<?php echo esc_attr( $tel ); ?>">Call us</a><a class="btn btn-ghost" href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>">Email us</a></div>
</div></section>

<?php while ( have_posts() ) : the_post(); if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
<section class="section"><div class="wrap"><div class="content"><?php the_content(); ?></div></div></section>
<?php endif; endwhile; ?>

<section class="ab-cta"><div class="wrap">
	<h2><?php echo esc_html( pcs_contact_p( 'cta_title' ) ); ?></h2>
	<p><?php echo esc_html( pcs_contact_p( 'cta_text' ) ); ?></p>
	<a class="btn btn-white" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Order now</a>
</div></section>
<?php get_footer(); ?>

</main>
<?php
$tel  = preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) );
$cats = get_terms( array( 'taxonomy' => 'pcs_cat', 'hide_empty' => false ) );
?>
<footer class="site-footer">
	<div class="ft-trust"><div class="wrap">
		<div><?php echo pcs_icon( 'snow' ); // phpcs:ignore ?><span><strong>Fresh &amp; chilled</strong>Kept cold till it reaches you</span></div>
		<div><?php echo pcs_icon( 'shield' ); // phpcs:ignore ?><span><strong>Hygiene first</strong>Cleaned, cut and packed with care</span></div>
		<div><?php echo pcs_icon( 'bike' ); // phpcs:ignore ?><span><strong>Home delivery</strong>Choose your day and time slot</span></div>
		<div><?php echo pcs_icon( 'cash' ); // phpcs:ignore ?><span><strong>Cash on delivery</strong>Pay when you receive</span></div>
	</div></div>
	<div class="wrap ft">
		<div class="ft-brand">
			<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( pcs_opt( 'store_name' ) ); ?></a>
			<p>Fresh meat, proper cuts and honest service that customers can trust every day.</p>
			<a class="btn" href="https://wa.me/<?php echo esc_attr( pcs_opt( 'whatsapp' ) ); ?>" rel="noopener">Order on WhatsApp</a>
		</div>
		<div>
			<h4>Shop</h4>
			<?php foreach ( $cats as $t ) : ?><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'pcs_product' ) ); ?>">All products</a>
			<a href="<?php echo esc_url( pcs_page_url( 'checkout' ) ); ?>">Cart</a>
		</div>
		<div>
			<h4>Company</h4>
			<a href="<?php echo esc_url( pcs_page_url( 'about-us' ) ); ?>">About us</a>
			<a href="<?php echo esc_url( pcs_page_url( 'quality-hygiene' ) ); ?>">Quality &amp; hygiene</a>
			<a href="<?php echo esc_url( pcs_page_url( 'contact-us' ) ); ?>">Contact</a>
			<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>">My orders</a>
		</div>
		<div class="ft-contact">
			<h4>Visit &amp; contact</h4>
			<p><?php echo pcs_icon( 'pin' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></span></p>
			<p><?php echo pcs_icon( 'phone' ); // phpcs:ignore ?><a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a></p>
			<p><?php echo pcs_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>"><?php echo esc_html( pcs_opt( 'email' ) ); ?></a></p>
			<p><?php echo pcs_icon( 'clock' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></span></p>
		</div>
	</div>
	<div class="ft-bottom"><div class="wrap">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( pcs_opt( 'store_name' ) ); ?>. All rights reserved.</span>
		<span>Delivering in <?php echo esc_html( pcs_opt( 'city' ) ); ?> · Cash on delivery</span>
		<a href="#top" class="totop"><?php echo pcs_icon( 'up' ); // phpcs:ignore ?> Back to top</a>
	</div></div>
</footer>
<nav class="mbar" aria-label="Quick">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo pcs_icon( 'home' ); // phpcs:ignore ?>Home</a>
	<a href="<?php echo esc_url( get_post_type_archive_link( 'pcs_product' ) ); ?>"><?php echo pcs_icon( 'grid' ); // phpcs:ignore ?>Shop</a>
	<a href="<?php echo esc_url( pcs_page_url( 'checkout' ) ); ?>"><?php echo pcs_icon( 'cart' ); // phpcs:ignore ?>Cart<b class="cart-count" hidden>0</b></a>
	<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>"><?php echo pcs_icon( 'user' ); // phpcs:ignore ?>Account</a>
</nav>
<?php wp_footer(); ?>
</body>
</html>

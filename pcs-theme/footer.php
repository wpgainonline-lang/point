</main>
<footer class="site-footer">
	<div class="wrap">
		<div class="ft">
			<div><h4><?php echo esc_html( pcs_opt( 'store_name' ) ); ?></h4><p>Fresh meat, proper cuts and honest service that customers can trust every day.</p><p><?php echo nl2br( esc_html( pcs_opt( 'address' ) ) ); ?></p></div>
			<div><h4>Shop</h4>
				<?php foreach ( array( 'chicken' => 'Chicken', 'mutton' => 'Mutton' ) as $s => $l ) : $u = get_term_link( $s, 'pcs_cat' ); if ( ! is_wp_error( $u ) ) : ?><a href="<?php echo esc_url( $u ); ?>"><?php echo esc_html( $l ); ?></a><?php endif; endforeach; ?>
				<a href="<?php echo esc_url( pcs_page_url( 'about-us' ) ); ?>">About us</a>
				<a href="<?php echo esc_url( pcs_page_url( 'quality-hygiene' ) ); ?>">Quality &amp; hygiene</a>
				<a href="<?php echo esc_url( pcs_page_url( 'account' ) ); ?>">My orders</a>
			</div>
			<div><h4>Contact</h4><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', pcs_opt( 'phone' ) ) ); ?>"><?php echo esc_html( pcs_opt( 'phone' ) ); ?></a><a href="mailto:<?php echo esc_attr( pcs_opt( 'email' ) ); ?>"><?php echo esc_html( pcs_opt( 'email' ) ); ?></a><p style="margin-top:8px"><?php echo nl2br( esc_html( pcs_opt( 'timings' ) ) ); ?></p></div>
		</div>
		<div class="copy">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( pcs_opt( 'store_name' ) ); ?>. All rights reserved.</div>
	</div>
</footer>
<?php if ( pcs_opt( 'whatsapp' ) ) : ?><a class="wa" href="https://wa.me/<?php echo esc_attr( pcs_opt( 'whatsapp' ) ); ?>" rel="noopener">WhatsApp</a><?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>

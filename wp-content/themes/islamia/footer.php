<?php $wa = preg_replace('/\D/', '', ic_get('phone')); ?>
<footer class="site-footer"><div class="wrap">
  <div class="grid g3">
    <div>
      <h4><?php bloginfo('name'); ?></h4>
      <p><?php echo esc_html(ic_get('hero_text')); ?></p>
      <div class="social">
        <?php foreach (['facebook' => 'f', 'instagram' => 'ig', 'linkedin' => 'in', 'youtube' => 'yt'] as $k => $ab) if ($u = ic_get($k)) printf('<a href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>', esc_url($u), esc_attr($k), esc_html($ab)); ?>
      </div>
    </div>
    <div>
      <h4>Quick links</h4>
      <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => function () {
          echo '<ul><li><a href="' . esc_url(ic_page_url('register')) . '">Register</a></li><li><a href="' . esc_url(ic_page_url('portal')) . '">Portal login</a></li><li><a href="' . esc_url(ic_page_url('calendar')) . '">School calendar</a></li><li><a href="' . esc_url(get_post_type_archive_link('ic_notice')) . '">Notices</a></li><li><a href="' . esc_url(ic_page_url('donations')) . '">Donations</a></li></ul>';
      }]); ?>
    </div>
    <div>
      <h4>Contact</h4>
      <ul>
        <li><?php echo esc_html(ic_get('address')); ?> <?php if ($m = ic_get('map_url')) : ?>(<a href="<?php echo esc_url($m); ?>" target="_blank" rel="noopener">directions</a>)<?php endif; ?></li>
        <li><a href="mailto:<?php echo esc_attr(ic_get('email')); ?>"><?php echo esc_html(ic_get('email')); ?></a></li>
        <?php if ($wa) : ?><li>WhatsApp: <a href="https://wa.me/<?php echo esc_attr($wa); ?>">+<?php echo esc_html($wa); ?></a></li><?php endif; ?>
        <li><?php echo nl2br(esc_html(ic_get('hours'))); ?></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">© <?php echo esc_html(date('Y')); ?> <?php echo esc_html(ic_get('footer_text')); ?></div>
</div></footer>
<?php if ($wa) : ?><a class="wa-float" href="https://wa.me/<?php echo esc_attr($wa); ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
<?php wp_footer(); ?>
</body></html>

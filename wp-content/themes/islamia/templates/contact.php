<?php
/* Template Name: Contact */
get_header(); $wa = preg_replace('/\D/', '', ic_get('phone')); ?>
<div class="page-hero"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap grid g3">
  <div class="card"><div class="icon">📍</div><h3>Address</h3><p><?php echo esc_html(ic_get('address')); ?></p><?php if ($m = ic_get('map_url')) : ?><a href="<?php echo esc_url($m); ?>" target="_blank" rel="noopener">Get directions</a><?php endif; ?></div>
  <div class="card"><div class="icon">✉️</div><h3>Email</h3><p><a href="mailto:<?php echo esc_attr(ic_get('email')); ?>"><?php echo esc_html(ic_get('email')); ?></a></p><p><?php echo nl2br(esc_html(ic_get('hours'))); ?></p></div>
  <div class="card"><div class="icon">💬</div><h3>WhatsApp</h3><p>Quick reply from the admin team.</p><?php if ($wa) : ?><a class="btn small" href="https://wa.me/<?php echo esc_attr($wa); ?>">Chat now</a><?php endif; ?></div>
</div></section>
<?php get_footer();

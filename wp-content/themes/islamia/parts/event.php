<?php $d = get_post_meta(get_the_ID(), 'ic_date', true); $ts = $d ? strtotime($d) : 0; ?>
<div class="event">
  <div class="date"><?php if ($ts) : ?><b><?php echo esc_html(date('d', $ts)); ?></b><?php echo esc_html(date('M Y', $ts)); ?><?php else : ?>—<?php endif; ?></div>
  <div><h3><?php the_title(); ?></h3>
    <span class="meta"><?php echo esc_html(trim(get_post_meta(get_the_ID(), 'ic_time', true) . ' · ' . get_post_meta(get_the_ID(), 'ic_place', true), ' ·')); ?></span>
    <?php if (get_the_content()) echo '<div>' . wp_kses_post(wpautop(get_the_content())) . '</div>'; ?></div>
</div>

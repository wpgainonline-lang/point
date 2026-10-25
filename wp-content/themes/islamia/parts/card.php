<article class="card">
  <?php if (has_post_thumbnail()) : ?><a class="thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium_large'); ?></a><?php endif; ?>
  <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
  <?php if (get_post_type() === 'ic_course') : ?>
    <p class="meta"><?php echo esc_html(get_post_meta(get_the_ID(), 'ic_age', true)); ?> <?php echo esc_html(get_post_meta(get_the_ID(), 'ic_schedule', true)); ?></p>
  <?php else : ?><p class="meta"><?php echo esc_html(get_the_date()); ?></p><?php endif; ?>
  <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
  <a class="btn small green" href="<?php the_permalink(); ?>">Read more</a>
</article>

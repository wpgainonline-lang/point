<?php get_header();
$reg = ic_get('hero_btn_url') ?: ic_page_url('register');
$icons = ['🕌', '📖', '🤝', '🌙'];
?>
<section class="hero"><div class="wrap">
  <span class="kicker"><?php echo esc_html(ic_get('hero_kicker')); ?></span>
  <h1><?php echo esc_html(ic_get('hero_title')); ?></h1>
  <p><?php echo esc_html(ic_get('hero_text')); ?></p>
  <div class="actions">
    <a class="btn" href="<?php echo esc_url($reg); ?>"><?php echo esc_html(ic_get('hero_btn_text')); ?></a>
    <a class="btn ghost" href="<?php echo esc_url(ic_page_url('portal')); ?>">Portal login</a>
  </div>
</div></section>

<section class="stats"><div class="wrap"><div class="grid">
  <?php for ($i = 1; $i <= 4; $i++) printf('<div class="stat"><b>%s</b><span>%s</span></div>', esc_html(ic_get("stat{$i}_num")), esc_html(ic_get("stat{$i}_label"))); ?>
</div></div></section>

<section class="section"><div class="wrap split">
  <div class="photo"><?php if ($p = ic_get('head_photo')) echo '<img src="' . esc_url($p) . '" alt="' . esc_attr(ic_get('head_name')) . '">'; ?></div>
  <div>
    <span class="kicker">Headteacher’s message</span>
    <h2><?php echo esc_html(ic_get('head_name')); ?></h2>
    <p class="meta"><?php echo esc_html(ic_get('head_role')); ?></p>
    <div class="quote"><?php echo wpautop(esc_html(ic_get('head_msg'))); ?></div>
    <a class="btn green" href="<?php echo esc_url(ic_page_url('contact')); ?>">Ask a question</a>
  </div>
</div></section>

<section class="section alt"><div class="wrap">
  <div class="section-head"><span class="kicker">Our strengths</span><h2><?php echo esc_html(ic_get('why_title')); ?></h2><span class="divider"></span></div>
  <div class="grid g4">
    <?php for ($i = 1; $i <= 4; $i++) printf('<div class="card"><div class="icon">%s</div><h3>%s</h3><p>%s</p></div>', $icons[$i - 1], esc_html(ic_get("why{$i}_title")), esc_html(ic_get("why{$i}_text"))); ?>
  </div>
</div></section>

<?php $courses = new WP_Query(['post_type' => 'ic_course', 'posts_per_page' => 6]); if ($courses->have_posts()) : ?>
<section class="section"><div class="wrap">
  <div class="section-head"><span class="kicker">What we teach</span><h2>Our Courses</h2><span class="divider"></span></div>
  <div class="grid g3"><?php while ($courses->have_posts()) : $courses->the_post(); get_template_part('parts/card'); endwhile; wp_reset_postdata(); ?></div>
  <p style="text-align:center;margin-top:30px"><a class="btn green" href="<?php echo esc_url(get_post_type_archive_link('ic_course')); ?>">All courses</a></p>
</div></section>
<?php endif; ?>

<?php $ev = new WP_Query(['post_type' => 'ic_event', 'posts_per_page' => 4, 'meta_key' => 'ic_date', 'orderby' => 'meta_value', 'order' => 'ASC',
    'meta_query' => [['key' => 'ic_date', 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'DATE']]]);
$nt = new WP_Query(['post_type' => 'ic_notice', 'posts_per_page' => 3]);
if ($ev->have_posts() || $nt->have_posts()) : ?>
<section class="section alt"><div class="wrap grid g2">
  <div><h2>Upcoming events</h2>
    <?php if ($ev->have_posts()) while ($ev->have_posts()) : $ev->the_post(); get_template_part('parts/event'); endwhile; else echo '<p class="meta">No upcoming events.</p>'; wp_reset_postdata(); ?>
  </div>
  <div><h2>Latest notices</h2>
    <?php if ($nt->have_posts()) while ($nt->have_posts()) : $nt->the_post(); ?>
      <div class="event"><div><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><span class="meta"><?php echo esc_html(get_the_date()); ?></span><p style="margin:6px 0 0"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p></div></div>
    <?php endwhile; else echo '<p class="meta">No notices yet.</p>'; wp_reset_postdata(); ?>
  </div>
</div></section>
<?php endif; ?>

<?php $gal = new WP_Query(['post_type' => 'ic_gallery', 'posts_per_page' => 8]); if ($gal->have_posts()) : ?>
<section class="section"><div class="wrap">
  <div class="section-head"><span class="kicker">Moments</span><h2>Gallery</h2><span class="divider"></span></div>
  <div class="gallery"><?php while ($gal->have_posts()) : $gal->the_post(); if (has_post_thumbnail()) printf('<a href="%s">%s</a>', esc_url(get_the_post_thumbnail_url(null, 'large')), get_the_post_thumbnail(null, 'medium_large', ['alt' => get_the_title()])); endwhile; wp_reset_postdata(); ?></div>
</div></section>
<?php endif; ?>

<?php $rv = new WP_Query(['post_type' => 'ic_review', 'posts_per_page' => 3]); if ($rv->have_posts()) : ?>
<section class="section alt"><div class="wrap">
  <div class="section-head"><span class="kicker">Parents say</span><h2>Reviews</h2><span class="divider"></span></div>
  <div class="grid g3"><?php while ($rv->have_posts()) : $rv->the_post(); ?>
    <div class="card"><div class="quote"><?php the_content(); ?></div><p><b><?php the_title(); ?></b><br><span class="meta"><?php echo esc_html(get_post_meta(get_the_ID(), 'ic_who', true)); ?></span></p></div>
  <?php endwhile; wp_reset_postdata(); ?></div>
</div></section>
<?php endif; ?>

<section class="cta"><div class="wrap">
  <h2>Enrol your child today</h2>
  <p>Register online in a few minutes — our team will contact you.</p>
  <a class="btn" href="<?php echo esc_url($reg); ?>"><?php echo esc_html(ic_get('hero_btn_text')); ?></a>
</div></section>
<?php get_footer();

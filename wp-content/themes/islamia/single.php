<?php get_header(); while (have_posts()) : the_post();
$pt = get_post_type();
$kicker = ['ic_course' => 'Course', 'ic_notice' => 'Notice', 'ic_event' => 'Event', 'ic_review' => 'Review'][$pt] ?? '';
$back = ['ic_course' => [get_post_type_archive_link('ic_course'), 'All courses'], 'ic_notice' => [get_post_type_archive_link('ic_notice'), 'All notices'], 'ic_event' => [ic_page_url('calendar'), 'School calendar']][$pt] ?? null;
?>
<div class="page-hero">
  <div class="wrap">
    <?php if ($kicker) : ?><span class="kicker" style="color:#f3c876"><?php echo esc_html($kicker); ?></span><?php endif; ?>
    <h1><?php the_title(); ?></h1>
    <p class="meta" style="color:#d8e6df"><?php echo esc_html(get_the_date()); ?></p>
  </div>
</div>
<section class="section"><div class="wrap">
  <div class="split" style="grid-template-columns:1.6fr 1fr;align-items:start">
    <article class="entry" style="margin:0">
      <?php if (has_post_thumbnail()) : ?><div class="thumb" style="margin-bottom:28px;border-radius:var(--ic-radius);overflow:hidden;box-shadow:var(--ic-shadow)"><?php the_post_thumbnail('large', ['style' => 'width:100%;display:block']); ?></div><?php endif; ?>
      <?php if ($pt === 'ic_review') : ?>
        <div class="quote" style="font-size:1.15rem"><?php the_content(); ?></div>
        <p style="margin-top:18px"><b><?php the_title(); ?></b><br><span class="meta"><?php echo esc_html(get_post_meta(get_the_ID(), 'ic_who', true)); ?></span></p>
      <?php else : the_content(); endif; ?>
    </article>

    <aside>
      <?php if ($pt === 'ic_course') : ?>
        <div class="card">
          <div class="icon">📖</div>
          <h3>Course details</h3>
          <p><b>Age group:</b> <?php echo esc_html(get_post_meta(get_the_ID(), 'ic_age', true) ?: '—'); ?></p>
          <p><b>Days / time:</b> <?php echo esc_html(get_post_meta(get_the_ID(), 'ic_schedule', true) ?: '—'); ?></p>
          <a class="btn green small" href="<?php echo esc_url(ic_page_url('register')); ?>" style="margin-top:10px">Register for this course</a>
        </div>
      <?php elseif ($pt === 'ic_event') : ?>
        <div class="card">
          <div class="icon">🗓️</div>
          <h3>When &amp; where</h3>
          <?php if ($d = get_post_meta(get_the_ID(), 'ic_date', true)) : ?><p><b>Date:</b> <?php echo esc_html(mysql2date('l, j F Y', $d)); ?></p><?php endif; ?>
          <?php if ($t = get_post_meta(get_the_ID(), 'ic_time', true)) : ?><p><b>Time:</b> <?php echo esc_html($t); ?></p><?php endif; ?>
          <?php if ($pl = get_post_meta(get_the_ID(), 'ic_place', true)) : ?><p><b>Place:</b> <?php echo esc_html($pl); ?></p><?php endif; ?>
        </div>
      <?php else : ?>
        <div class="card">
          <div class="icon">🕌</div>
          <h3>Islamia Islamic Centre</h3>
          <p class="meta"><?php echo esc_html(ic_get('address')); ?></p>
          <a class="btn green small" href="<?php echo esc_url(ic_page_url('contact')); ?>">Contact us</a>
        </div>
      <?php endif; ?>
      <?php if (!empty($back)) printf('<p style="margin-top:16px"><a href="%s">&larr; %s</a></p>', esc_url($back[0]), esc_html($back[1])); ?>
    </aside>
  </div>
</div></section>
<?php endwhile; get_footer();

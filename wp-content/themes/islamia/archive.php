<?php get_header();
$pt = get_post_type();
$meta = [
    'ic_course' => ['Learn with us', 'Our Courses', 'Weekend classes in Quran, Tajweed, Islamic Studies and Arabic for every age group.'],
    'ic_notice' => ['Stay informed', 'Notices', 'The latest announcements and updates from Islamia Islamic Centre.'],
    'ic_event'  => ['What’s on', 'School Calendar', 'Upcoming events, trips and celebrations at the centre.'],
][$pt] ?? ['', get_the_archive_title(), ''];
?>
<div class="page-hero"><div class="wrap">
  <?php if ($meta[0]) : ?><span class="kicker" style="color:#f3c876"><?php echo esc_html($meta[0]); ?></span><?php endif; ?>
  <h1><?php echo esc_html($meta[1]); ?></h1>
  <?php if ($meta[2]) : ?><p style="color:#d8e6df;max-width:560px;margin:10px auto 0"><?php echo esc_html($meta[2]); ?></p><?php endif; ?>
</div></div>
<section class="section"><div class="wrap">
  <?php if ($pt === 'ic_event') : ?>
    <div class="entry-narrow">
      <?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('parts/event'); endwhile; else : ?>
        <p style="text-align:center">No events scheduled yet — please check back soon.</p>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <div class="grid g3">
      <?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('parts/card'); endwhile; else : ?>
        <p style="text-align:center">Nothing here yet — please check back soon.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div style="text-align:center;margin-top:30px"><?php the_posts_pagination(); ?></div>
</div></section>
<?php get_footer();

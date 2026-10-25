<?php get_header(); ?>
<div class="page-hero"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></div>
<section class="section"><div class="wrap">
  <?php if (get_post_type() === 'ic_event') : ?>
    <?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('parts/event'); endwhile; else : ?>
      <p>No events scheduled yet.</p>
    <?php endif; ?>
  <?php else : ?>
    <div class="grid g3">
      <?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('parts/card'); endwhile; else : ?>
        <p>Nothing here yet.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div style="text-align:center;margin-top:30px"><?php the_posts_pagination(); ?></div>
</div></section>
<?php get_footer();

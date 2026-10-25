<?php get_header(); ?>
<div class="page-hero"><div class="wrap"><h1><?php echo is_home() ? 'News' : wp_title('', false); ?></h1></div></div>
<section class="section"><div class="wrap grid g3">
  <?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('parts/card'); endwhile; else : ?>
    <p>Nothing found.</p>
  <?php endif; ?>
</div></section>
<?php get_footer();

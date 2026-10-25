<?php
/* Template Name: School calendar */
get_header(); ?>
<div class="page-hero"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap entry-narrow">
  <?php $q = new WP_Query(['post_type' => 'ic_event', 'posts_per_page' => 100, 'meta_key' => 'ic_date', 'orderby' => 'meta_value', 'order' => 'ASC']);
  if ($q->have_posts()) while ($q->have_posts()) : $q->the_post(); get_template_part('parts/event'); endwhile; else echo '<p>No events scheduled yet.</p>'; wp_reset_postdata(); ?>
</div></section>
<?php get_footer();

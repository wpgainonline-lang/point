<?php
/* Template Name: Gallery */
get_header(); ?>
<div class="page-hero"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap">
  <?php $q = new WP_Query(['post_type' => 'ic_gallery', 'posts_per_page' => 60]); ?>
  <div class="gallery"><?php while ($q->have_posts()) : $q->the_post(); if (has_post_thumbnail()) printf('<a href="%s">%s</a>', esc_url(get_the_post_thumbnail_url(null, 'full')), get_the_post_thumbnail(null, 'medium_large', ['alt' => get_the_title()])); endwhile; wp_reset_postdata(); ?></div>
  <?php if (!$q->have_posts()) echo '<p style="text-align:center">Photos coming soon.</p>'; ?>
</div></section>
<?php get_footer();

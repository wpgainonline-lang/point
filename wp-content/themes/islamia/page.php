<?php get_header(); while (have_posts()) : the_post(); ?>
<div class="page-hero"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap"><article class="entry"><?php the_content(); ?></article></div></section>
<?php endwhile; get_footer();

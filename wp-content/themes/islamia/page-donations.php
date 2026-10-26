<?php
/* Template for the "Donations" page (matches by slug automatically — no settings needed). */
get_header(); while (have_posts()) : the_post(); ?>
<div class="page-hero"><div class="wrap"><span class="kicker" style="color:#f3c876">Sadaqah &amp; Support</span><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap">
  <div class="split">
    <div>
      <span class="kicker">Support our mission</span>
      <h2>Help us keep teaching</h2>
      <div class="entry" style="margin:0"><?php the_content(); ?></div>
      <div class="quote" style="margin-top:22px">“Whoever builds a path to knowledge, Allah builds for them a path to Paradise.”</div>
    </div>
    <div class="photo" style="aspect-ratio:4/3"><?php if ($img = ic_get('hero_image')) echo '<img src="' . esc_url($img) . '" alt="">'; ?></div>
  </div>

  <div class="grid g3" style="margin-top:56px">
    <div class="card"><div class="icon">📚</div><h3>Sponsor Resources</h3><p>Fund Quran copies, books and classroom materials for our students.</p></div>
    <div class="card"><div class="icon">🏫</div><h3>Support Facilities</h3><p>Help us maintain and improve the centre for every family.</p></div>
    <div class="card"><div class="icon">🤲</div><h3>General Sadaqah</h3><p>Give freely towards wherever the need is greatest.</p></div>
  </div>
</div></section>

<section class="cta"><div class="wrap">
  <h2>Every contribution counts</h2>
  <p>Contact us for bank transfer details or an online donation link.</p>
  <a class="btn" href="<?php echo esc_url(ic_page_url('contact')); ?>">Get in touch</a>
</div></section>
<?php endwhile; get_footer();

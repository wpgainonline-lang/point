<?php
/* Template for the "Student Resources" page (matches by slug automatically — no settings needed). */
get_header(); while (have_posts()) : the_post(); ?>
<div class="page-hero"><div class="wrap"><span class="kicker" style="color:#f3c876">For our students</span><h1><?php the_title(); ?></h1></div></div>
<section class="section"><div class="wrap">
  <div class="entry" style="margin-bottom:40px"><?php the_content(); ?></div>
  <div class="grid g3">
    <div class="card"><div class="icon">📖</div><h3>Quran &amp; Tajweed</h3><p>Reading guides, Tajweed rules and memorisation trackers set by your teacher.</p></div>
    <div class="card"><div class="icon">📝</div><h3>Homework</h3><p>Check your Portal dashboard for homework notes and your teacher's comments.</p></div>
    <div class="card"><div class="icon">📅</div><h3>Timetable</h3><p>See class days and times on the <a href="<?php echo esc_url(ic_page_url('calendar')); ?>">School Calendar</a>.</p></div>
    <div class="card"><div class="icon">📊</div><h3>My Progress</h3><p>View your attendance and progress reports any time in your <a href="<?php echo esc_url(ic_page_url('portal')); ?>">Student Portal</a>.</p></div>
    <div class="card"><div class="icon">🎓</div><h3>Good Manners</h3><p>Islamic etiquette (Adab) reminders for the classroom and at home.</p></div>
    <div class="card"><div class="icon">❓</div><h3>Need help?</h3><p>Ask your teacher, or have your parent reach us via <a href="<?php echo esc_url(ic_page_url('contact')); ?>">Contact</a>.</p></div>
  </div>
</div></section>
<?php endwhile; get_footer();

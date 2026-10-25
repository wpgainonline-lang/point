<?php
/* Template Name: Student registration */
get_header();
$r = sanitize_key($_GET['reg'] ?? '');
$classes = ic_class_options();
?>
<div class="page-hero"><div class="wrap"><h1>Student registration</h1></div></div>
<section class="section"><div class="wrap entry-narrow">
  <?php if ($r === 'ok') : ?><div class="notice ok">JazakAllah Khair! Your registration was received. We will contact you once it is approved, and you will get an email to set up your parent login.</div>
  <?php elseif ($r === 'limit') : ?><div class="notice err">Too many submissions. Please try again later.</div>
  <?php elseif ($r === 'err') : ?><div class="notice err">Please fill in the required fields and tick the consent box.</div><?php endif; ?>
  <?php if ($r !== 'ok') : ?>
  <form class="form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="ic_register"><?php wp_nonce_field('ic_reg'); ?>
    <div class="hp"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <h3>Student</h3>
    <div class="field"><label>Full name *</label><input type="text" name="child_name" required></div>
    <div class="row">
      <div class="field"><label>Date of birth</label><input type="date" name="dob" max="<?php echo esc_attr(date('Y-m-d')); ?>"></div>
      <div class="field"><label>Gender</label><select name="gender"><option value="">—</option><option>Male</option><option>Female</option></select></div>
    </div>
    <?php if ($classes) : ?><div class="field"><label>Preferred class</label><select name="class_id"><option value="">Not sure / decide later</option><?php foreach ($classes as $id => $t) printf('<option value="%d">%s</option>', $id, esc_html($t)); ?></select></div><?php endif; ?>
    <div class="field"><label>Student email (optional — gives the student their own login)</label><input type="email" name="student_email"></div>
    <h3>Parent / guardian</h3>
    <div class="field"><label>Name *</label><input type="text" name="parent_name" required></div>
    <div class="row">
      <div class="field"><label>Email *</label><input type="email" name="parent_email" required></div>
      <div class="field"><label>Phone / WhatsApp</label><input type="tel" name="parent_phone"></div>
    </div>
    <div class="field"><label>Address</label><textarea name="address" rows="2"></textarea></div>
    <div class="field"><label>Emergency contact</label><input type="text" name="emergency"></div>
    <div class="field"><label>Medical conditions / allergies</label><textarea name="medical" rows="2"></textarea></div>
    <div class="field"><label style="font-weight:400"><input type="checkbox" name="consent" value="1" required> I confirm the details are correct and agree to the centre storing them for school administration.</label></div>
    <button class="btn green">Submit registration</button>
  </form>
  <?php endif; ?>
</div></section>
<?php get_footer();

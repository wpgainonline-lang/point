<?php
defined('ABSPATH') || exit;

/**
 * Every editable bit of the home page / footer lives here, so the admin edits it in
 * Appearance → Customize (live preview). No plugin or page-builder needed.
 * Format: section => [title, [ [key, type, label, default], … ]]
 */
function ic_customizer_map() {
    return [
        'ic_hero' => ['Home — Hero banner', [
            ['hero_kicker', 'text', 'Small heading', 'Bismillah ir-Rahman ir-Raheem'],
            ['hero_title', 'text', 'Main title', 'Islamia Islamic Centre'],
            ['hero_text', 'textarea', 'Intro text', 'High-quality weekend Islamic education — strengthening faith, building community and developing character for children and families from all backgrounds.'],
            ['hero_btn_text', 'text', 'Button text', 'Register Now'],
            ['hero_btn_url', 'url', 'Button link (blank = Register page)', ''],
            ['hero_image', 'image', 'Background image', ''],
        ]],
        'ic_stats' => ['Home — Numbers strip', [
            ['stat1_num', 'text', 'Number 1', '250+'], ['stat1_label', 'text', 'Label 1', 'Students'],
            ['stat2_num', 'text', 'Number 2', '20+'], ['stat2_label', 'text', 'Label 2', 'Qualified Teachers'],
            ['stat3_num', 'text', 'Number 3', '10+'], ['stat3_label', 'text', 'Label 3', 'Classes'],
            ['stat4_num', 'text', 'Number 4', '15'], ['stat4_label', 'text', 'Label 4', 'Years of Service'],
        ]],
        'ic_head' => ['Home — Headteacher message', [
            ['head_name', 'text', 'Name', 'Mrs. Sadia Hassan'],
            ['head_role', 'text', 'Role', 'Headteacher'],
            ['head_msg', 'textarea', 'Message', "Assalamu Alaikum and welcome to our website.\n\nThe Centre provides high-quality weekend Islamic education. Our goals are to strengthen faith, build community, develop character and help every child understand the Quran and Islamic studies.\n\nWe offer a welcoming environment for students and families from all backgrounds. Please feel free to ask any questions or learn more about our programmes."],
            ['head_photo', 'image', 'Photo', ''],
        ]],
        'ic_why' => ['Home — Why choose us', [
            ['why_title', 'text', 'Section title', 'Why Choose Islamia?'],
            ['why1_title', 'text', 'Point 1 title', 'Qualified Teachers'], ['why1_text', 'textarea', 'Point 1 text', 'Caring, experienced teachers who nurture every child.'],
            ['why2_title', 'text', 'Point 2 title', 'Quran & Tajweed'], ['why2_text', 'textarea', 'Point 2 text', 'Structured Quran reading, memorisation and understanding.'],
            ['why3_title', 'text', 'Point 3 title', 'Welcoming Community'], ['why3_text', 'textarea', 'Point 3 text', 'A friendly environment for students and families from all backgrounds.'],
            ['why4_title', 'text', 'Point 4 title', 'Character Building'], ['why4_text', 'textarea', 'Point 4 text', 'Islamic values, manners and leadership for everyday life.'],
        ]],
        'ic_contact' => ['Contact details', [
            ['address', 'text', 'Address', 'Pinkwell Lane, Hayes UB3 1PB'],
            ['email', 'text', 'Email', 'admin@islamia.org.uk'],
            ['phone', 'text', 'Phone / WhatsApp number (digits, with country code)', '447875547871'],
            ['hours', 'textarea', 'Opening hours', 'Saturday & Sunday — weekend school hours'],
            ['map_url', 'url', 'Google Maps link', 'https://www.google.com/maps/search/?api=1&query=Pinkwell+Lane+Hayes+UB3+1PB'],
        ]],
        'ic_social' => ['Social links', [
            ['facebook', 'url', 'Facebook', 'https://www.facebook.com/IslamiaIslamicCentre/'],
            ['instagram', 'url', 'Instagram', 'https://www.instagram.com/islamia.org.uk'],
            ['linkedin', 'url', 'LinkedIn', 'https://www.linkedin.com/company/islamiaislamiccentre/'],
            ['youtube', 'url', 'YouTube', 'https://www.youtube.com/@islamiaislamiccentre9147/videos'],
        ]],
        'ic_colors' => ['Theme colours', [
            ['color_primary', 'color', 'Main colour', '#004B39'],
            ['color_accent', 'color', 'Accent (gold) colour', '#DB9E30'],
            ['color_dark', 'color', 'Dark (brown) colour', '#5D2B00'],
        ]],
        'ic_footer' => ['Footer', [
            ['footer_text', 'text', 'Copyright text', 'Islamia Islamic Centre - All Rights Reserved.'],
            ['gform_register', 'url', 'Old Google-Form link (optional, unused)', ''],
        ]],
    ];
}

add_action('customize_register', function (WP_Customize_Manager $c) {
    $prio = 30;
    foreach (ic_customizer_map() as $sid => [$title, $fields]) {
        $c->add_section($sid, ['title' => $title, 'priority' => $prio++]);
        foreach ($fields as [$key, $type, $label, $default]) {
            $san = ['text' => 'sanitize_text_field', 'textarea' => 'sanitize_textarea_field', 'url' => 'esc_url_raw', 'image' => 'esc_url_raw', 'color' => 'sanitize_hex_color'][$type];
            $c->add_setting($key, ['default' => $default, 'sanitize_callback' => $san, 'transport' => 'refresh']);
            $args = ['label' => $label, 'section' => $sid, 'settings' => $key];
            if ($type === 'image') $c->add_control(new WP_Customize_Image_Control($c, $key, $args));
            elseif ($type === 'color') $c->add_control(new WP_Customize_Color_Control($c, $key, $args));
            else $c->add_control($key, $args + ['type' => $type]);
        }
    }
});

/** Default value lookup so templates don't repeat defaults. */
function ic_default($key) {
    foreach (ic_customizer_map() as [, $fields]) {
        foreach ($fields as $f) if ($f[0] === $key) return $f[3];
    }
    return '';
}
function ic_get($key) { return ic_opt($key, ic_default($key)); }

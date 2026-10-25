# Islamia Islamic Centre — WordPress theme (no plugins)

Custom theme for `islamia.org.uk` with a built-in **student registration system**,
and separate **Parent / Student / Teacher** logins — attendance, progress reports,
notices, calendar, gallery, courses and reviews. Everything editable from the
WordPress dashboard. No plugins used; all features are coded into this theme.

## 1. Install WordPress locally (XAMPP)

1. Start XAMPP (Apache + MySQL).
2. Download WordPress: https://wordpress.org/latest.zip → extract into `C:\xampp\htdocs\islamia`
   so you get `C:\xampp\htdocs\islamia\wp-admin`, `wp-content`, etc.
3. Open http://localhost/phpmyadmin → create a database, e.g. `islamia_db`.
4. Visit http://localhost/islamia/ → run the WordPress install wizard, enter the
   database name, user `root`, blank password (default XAMPP), host `localhost`.
5. Finish the wizard and log in to http://localhost/islamia/wp-admin/.

## 2. Add this theme

1. Copy this folder (`islamia`) into
   `C:\xampp\htdocs\islamia\wp-content\themes\islamia`
   (i.e. the folder containing `style.css`, `functions.php`, `inc/`, `templates/`).
2. In wp-admin → **Appearance → Themes** → activate **Islamia Islamic Centre**.
3. On activation the theme automatically:
   - creates the needed database tables (attendance, progress reports),
   - creates the Teacher / Parent / Student roles,
   - creates the pages: Home, Register, Portal, Gallery, School Calendar, Contact,
     Donations, Student Resources,
   - builds the main menu and sets Home as the front page.

## 3. Edit everything from the dashboard

- **Appearance → Customize** — hero text/image, headteacher message, "why choose
  us" points, contact details, social links, theme colours, footer text.
- **Islamia School** menu (left sidebar) — overview, attendance report, progress
  reports, and the normal post-type menus: **Courses, Notices, Calendar Events,
  Gallery, Reviews, Students, Classes**.
- **Users → Add New** — add a teacher with role **Islamia Teacher**.
- **Islamia School → Classes** — add a class, assign a teacher to it.
- Registrations submitted from the **Register** page appear as *pending*
  Students. Open one, pick the class, click **Publish** — a parent account is
  created automatically and emailed a "set your password" link.

## 4. Portal (frontend, no wp-admin access)

The **Portal** page (`/portal/`) is the single login/dashboard for parents,
students and teachers:
- **Teacher**: take attendance per class/date, add progress reports for their
  students, see their classes.
- **Parent**: see their child(ren)'s attendance %, history and progress reports.
- **Student**: see their own attendance and progress reports.
- **Admin** (you): full access, plus a link to the wp-admin dashboard.

## Design

Colours and fonts follow `islamia.org.uk`'s existing palette, blended with a
layout inspired by the "Muezzin" Islamic-center WordPress theme (gold/green,
stats strip, headteacher spotlight, course/notice/event cards, gallery grid).
Change colours any time in Appearance → Customize → Theme colours.

## Notes

- Sending real emails (new-user notifications) needs an SMTP setup on your
  live host; on localhost these emails will usually just fail silently —
  you can still open the pending user's dashboard record to see their login.
- No plugin is required or used. All logic is in this theme's `inc/` files.

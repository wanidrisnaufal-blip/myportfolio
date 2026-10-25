# Personal Portfolio

A responsive PHP and MySQL portfolio with an admin dashboard. It uses plain PHP, CSS, and JavaScript without a framework.

## Requirements

- PHP 7.4+ with PDO MySQL enabled
- MySQL 5.7+ or MariaDB
- Apache (for example, WampServer)

## Setup

1. Create the database and tables by importing `database.sql` in phpMyAdmin or the MySQL command line.
   If you already have the previous database installed, run each unapplied upgrade once, including `upgrade-admin-full-content.sql`, against the database used by the site. Contact portraits are stored in the `contact_portraits` table. The admin also creates the section-copy table and missing logo columns when opened.
2. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in your PHP environment. Defaults are `127.0.0.1`, `myportfolio`, `root`, and an empty password for a local WampServer setup.
3. Open `/myportfolio/` for the public site and `/myportfolio/admin/` for the dashboard. If the `admins` table has no accounts, open `/myportfolio/admin/setup.php` to create the first admin account; setup locks itself once an account exists. Then sign in through `/myportfolio/admin/login.php`.

## Content management

The dashboard has Overview, About, Projects, Skills, Journey, Gallery, and Contact pages. Text, section labels, navigation labels, calls to action, artwork captions, social links, footer copy, and image/logo assets are editable from the matching section. Overview accepts a portrait upload and a replacement badge-back logo. Skills categories support up to three uploaded brand logos each, alongside full category and skill CRUD. Journey entries support organization logos. Contact lets you upload, replace, reorder, and delete up to 10 overlapping portrait images (JPG, PNG, WebP, or GIF; up to 5 MB each); clicking the public portrait advances through them and loops. Project and Gallery lists include image thumbnails. Gallery lets you edit its heading and introduction and manage images, captions, alt text, order, and visibility. Gallery images (JPG, PNG, WebP, GIF; up to 8 MB) are saved in `assets/gallery/`. Projects include alt text, technologies, role, status, and optional demo/source/case-study links. Place your resume at `assets/documents/resume.pdf` or set a different URL in Contact.

The initial project cards, five Skills categories, and current diploma are seeded from the supplied brief. No Gallery images are seeded; the public section uses the profile portrait until Gallery images are uploaded. Missing details are explicitly marked for completion. Skills are intentionally empty until confirmed. Replace “Your Name”, add verified contact details, and update project descriptions, roles, technologies, images, and status through the admin dashboard.

## Structure

```text
index.php                 Public portfolio, rendered from MySQL
includes/bootstrap.php    PDO connection, escaping, session and CSRF helpers
admin/                    Login, dashboard CRUD and admin styles
database.sql              Schema and factual starter content
css/style.css             Base layout, typography and visual system
css/responsive.css        Small-screen layouts and reduced-motion rules
js/main.js                Navigation and active-section state
js/animations.js          IntersectionObserver reveals
js/projects.js            Dedicated project client module
assets/images/            Image assets and preview placeholder
assets/projects/          Optional project media
assets/gallery/           Uploaded gallery media
assets/documents/         Resume document location
```

## Maintenance

Add project or Gallery fields in `database.sql` and the admin form in `admin/index.php` when extending content. Keep output escaped through `e()`, use prepared statements for database writes, and add any new public styles to the appropriate CSS file. Commit source and SQL changes to Git; keep environment secrets and private resume files out of the repository.

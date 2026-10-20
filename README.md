# Personal Portfolio

A responsive PHP and MySQL portfolio with an admin dashboard. It uses plain PHP, CSS, and JavaScript without a framework.

## Requirements

- PHP 7.4+ with PDO MySQL enabled
- MySQL 5.7+ or MariaDB
- Apache (for example, WampServer)

## Setup

1. Create the database and tables by importing `database.sql` in phpMyAdmin or the MySQL command line.
   If you already have the previous database installed, run `upgrade-hero-about.sql` (if not already applied) and `upgrade-lanyard-intro.sql` once in phpMyAdmin to add the editable Hero, About, and lanyard introduction fields.
2. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in your PHP environment. Defaults are `127.0.0.1`, `myportfolio`, `root`, and an empty password for a local WampServer setup.
3. Open `/myportfolio/` for the public site and `/myportfolio/admin/` for the dashboard.

## Content management

The dashboard has separate Hero, About, and Profile & links editors. Profile & links also lets you update the intro badge's name, age, university, programme, and portrait path. It can add, edit, publish/unpublish, and delete projects, skills, and education/experience entries. Project records support image uploads (JPG, PNG, WebP, GIF; up to 5 MB), alt text, technologies, role, status, and optional demo/source/case-study links. Uploaded project images are saved in `assets/projects/`. Place your resume at `assets/documents/resume.pdf` or set a different URL in Profile & links.

The initial project cards and current diploma are seeded from the supplied brief. Missing details are explicitly marked for completion. Skills are intentionally empty until confirmed. Replace “Your Name”, add verified contact details, and update project descriptions, roles, technologies, images, and status through the admin dashboard.

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
assets/documents/         Resume document location
```

## Maintenance

Add project fields in `database.sql` and the admin form in `admin/index.php` when extending project data. Keep output escaped through `e()`, use prepared statements for database writes, and add any new public styles to the appropriate CSS file. Commit source and SQL changes to Git; keep environment secrets and private resume files out of the repository.

<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();

function ensure_admin_column(string $table, string $column, string $definition): void {
    $exists = db()->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
    if (!$exists) db()->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

function receive_admin_image(string $field, string $folder, int $maxMb = 5): array {
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return [null, 'Image upload failed. Please try again.'];
    if (($file['size'] ?? 0) > $maxMb * 1024 * 1024) return [null, "Image must be $maxMb MB or smaller."];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) return [null, 'Use a valid JPG, PNG, WebP, or GIF image.'];
    $directory = __DIR__ . '/../assets/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) return [null, 'Could not create the image folder.'];
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) return [null, 'Could not save the uploaded image.'];
    return ['assets/' . $folder . '/' . $filename, null];
}

db()->exec('CREATE TABLE IF NOT EXISTS site_content(content_key VARCHAR(100) PRIMARY KEY,content_value TEXT NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
ensure_admin_column('timeline', 'logo_url', 'VARCHAR(500) NULL');
ensure_admin_column('skill_categories', 'logo_1_url', 'VARCHAR(500) NULL');
ensure_admin_column('skill_categories', 'logo_2_url', 'VARCHAR(500) NULL');
ensure_admin_column('skill_categories', 'logo_3_url', 'VARCHAR(500) NULL');

function receive_project_image(): array {
    $file = $_FILES['project_image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Image upload failed. Please try a smaller image.'];
    if ($file['size'] > 5 * 1024 * 1024) return [null, 'Image must be 5 MB or smaller.'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) return [null, 'Use a valid JPG, PNG, WebP, or GIF image.'];
    $directory = __DIR__ . '/../assets/projects';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) return [null, 'Could not create the project image folder.'];
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) return [null, 'Could not save the uploaded image.'];
    return ['assets/projects/' . $filename, null];
}

function receive_gallery_image(): array {
    $file = $_FILES['gallery_image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'Image upload failed. Please try a smaller image.'];
    if ($file['size'] > 8 * 1024 * 1024) return [null, 'Image must be 8 MB or smaller.'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($extensions[$mime]) || !@getimagesize($file['tmp_name'])) return [null, 'Use a valid JPG, PNG, WebP, or GIF image.'];
    $directory = __DIR__ . '/../assets/gallery';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) return [null, 'Could not create the gallery image folder.'];
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) return [null, 'Could not save the uploaded image.'];
    return ['assets/gallery/' . $filename, null];
}

function receive_contact_images(int $limit): array {
    $files = $_FILES['contact_images'] ?? null;
    if (!$files || !isset($files['name']) || !is_array($files['name'])) return [[], null];
    $uploads = [];
    foreach ($files['name'] as $index => $name) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return [[], 'An image upload failed. Please try again.'];
        if (($files['size'][$index] ?? 0) > 5 * 1024 * 1024) return [[], 'Each portrait image must be 5 MB or smaller.'];
        $tmp = $files['tmp_name'][$index] ?? '';
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (!isset($extensions[$mime]) || !@getimagesize($tmp)) return [[], 'Use valid JPG, PNG, WebP, or GIF portrait images.'];
        $uploads[] = ['tmp' => $tmp, 'extension' => $extensions[$mime]];
    }
    if (!$uploads) return [[], 'Choose at least one portrait image to upload.'];
    if (count($uploads) > $limit) return [[], 'You can keep up to 10 portrait images. Remove some before adding more.'];
    $directory = __DIR__ . '/../assets/contact';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) return [[], 'Could not create the contact image folder.'];
    $saved = [];
    foreach ($uploads as $upload) {
        $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
        if (!move_uploaded_file($upload['tmp'], $directory . '/' . $filename)) return [[], 'Could not save a contact portrait image.'];
        $saved[] = 'assets/contact/' . $filename;
    }
    return [$saved, null];
}

$tables = ['projects' => 'Projects', 'skills' => 'Skills', 'timeline' => 'Journey', 'gallery' => 'Gallery'];
$contentGroups = [
    'overview' => ['intro_kicker'=>'Intro section label','intro_scroll'=>'Intro scroll hint','intro_student'=>'Intro student label','intro_age_label'=>'Age label','intro_university_label'=>'University label','intro_programme_label'=>'Programme label','intro_meet'=>'Intro bottom link label','intro_location'=>'Intro location label','hero_projects_link'=>'Projects button label','hero_resume_link'=>'Resume button label','hero_art_index'=>'Artwork index','hero_art_caption'=>'Artwork caption (use a new line for line breaks)','hero_art_hint'=>'Artwork interaction hint','hero_bottom_location'=>'Overview location label','hero_bottom_availability'=>'Availability label'],
    'about' => ['about_label'=>'Section label','about_link_label'=>'Contact link label'],
    'projects' => ['projects_label'=>'Section label','projects_title'=>'Heading (first line)','projects_emphasis'=>'Heading (italic line)','projects_intro'=>'Section introduction','projects_open_label'=>'Open project link label','projects_demo_label'=>'Demo link label','projects_source_label'=>'Source code link label','projects_case_label'=>'Case study link label'],
    'journey' => ['journey_label'=>'Section label','journey_title'=>'Heading (first line)','journey_emphasis'=>'Heading (italic line)','journey_intro'=>'Section introduction'],
    'gallery' => ['gallery_label'=>'Section label'],
    'contact' => ['contact_label'=>'Section label','contact_title'=>'Heading (first line)','contact_emphasis'=>'Heading (italic line)','contact_intro'=>'Introduction','contact_student_label'=>'Portrait caption','contact_portrait_hint'=>'Portrait interaction hint'],
    'footer' => ['footer_note'=>'Footer note','footer_back_label'=>'Back to top link label'],
    'navigation' => ['nav_overview'=>'Overview link','nav_about'=>'About link','nav_projects'=>'Projects link','nav_skills'=>'Skills link','nav_journey'=>'Journey link','nav_gallery'=>'Gallery link','nav_contact'=>'Contact link'],
];
$contentDefaults = [
    'intro_kicker'=>'INTRODUCTION / 00','intro_scroll'=>'SCROLL TO EXPLORE ↓','intro_student'=>'COMPUTER SCIENCE STUDENT','intro_age_label'=>'AGE','intro_university_label'=>'UNIVERSITY','intro_programme_label'=>'PROGRAMME','intro_meet'=>'MEET THE PORTFOLIO','intro_location'=>'MALAYSIA','hero_projects_link'=>'View projects','hero_resume_link'=>'Download resume','hero_art_index'=>'01 / 08','hero_art_caption'=>"CURIOUS BY DESIGN\nBUILT WITH PURPOSE",'hero_art_hint'=>'MOVE POINTER OR DRAG TO EXPLORE','hero_bottom_location'=>'BASED IN MALAYSIA','hero_bottom_availability'=>'AVAILABLE FOR OPPORTUNITIES',
    'about_label'=>'01 — ABOUT','about_link_label'=>'A little more about me','projects_label'=>'02 — SELECTED WORK','projects_title'=>'Projects with','projects_emphasis'=>'purpose.','projects_intro'=>'A selection of academic and personal work. Choose a project to explore.','projects_open_label'=>'Open','projects_demo_label'=>'Live demo','projects_source_label'=>'Source code','projects_case_label'=>'Case study','journey_label'=>'04 — JOURNEY','journey_title'=>'Learning,','journey_emphasis'=>'in progress.','journey_intro'=>'A growing timeline of education and experience.','gallery_label'=>'05 — GALLERY','contact_label'=>'06 — GET IN TOUCH','contact_title'=>'Have a good','contact_emphasis'=>'one in mind?','contact_intro'=>"I'm open to learning, collaboration, and conversations about thoughtful digital work.",'contact_student_label'=>'COMPUTER SCIENCE STUDENT','contact_portrait_hint'=>'CLICK TO EXPLORE ->','footer_note'=>'Designed and built with care.','footer_back_label'=>'Back to top',
    'nav_overview'=>'Overview','nav_about'=>'About','nav_projects'=>'Projects','nav_skills'=>'Skills','nav_journey'=>'Journey','nav_gallery'=>'Gallery','nav_contact'=>'Contact',
];
$section = $_GET['section'] ?? 'overview';
if ($section === 'hero') $section = 'overview';
if ($section === 'profile') $section = 'contact';
if (!isset($tables[$section]) && !in_array($section, ['overview', 'about', 'contact', 'skills_copy', 'gallery_copy'], true)) $section = 'overview';
$message = '';
$instagramColumnReady = false;
try {
    $hasInstagramColumn = db()->query("SHOW COLUMNS FROM profile LIKE 'instagram_url'")->fetch();
    if (!$hasInstagramColumn) db()->exec('ALTER TABLE profile ADD COLUMN instagram_url VARCHAR(500) NULL AFTER linkedin_url');
    $instagramColumnReady = true;
} catch (PDOException $exception) {
    $message = 'Instagram link setup needs the upgrade-social-links.sql migration.';
}
$contactPortraitsTableReady = true;
try { db()->query('SELECT id FROM contact_portraits LIMIT 1'); }
catch (PDOException $exception) { $contactPortraitsTableReady = false; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'content_save') {
        $group = $_POST['copy_section'] ?? '';
        if (!isset($contentGroups[$group])) $message = 'Unknown content group.';
        else {
            $saveCopy = db()->prepare('INSERT INTO site_content(content_key,content_value) VALUES(?,?) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value)');
            foreach (array_keys($contentGroups[$group]) as $key) $saveCopy->execute([$key, trim($_POST[$key] ?? '')]);
            if ($group === 'overview') {
                [$badgeLogo, $uploadError] = receive_admin_image('badge_logo_upload', 'images', 5);
                if ($uploadError) $message = $uploadError;
                elseif ($badgeLogo) $saveCopy->execute(['badge_back_image_url', $badgeLogo]);
                elseif (isset($_POST['clear_badge_logo'])) $saveCopy->execute(['badge_back_image_url', '']);
            }
            if (!$message) $message = ucfirst($group) . ' labels and links saved.';
        }
    } elseif (in_array($action, ['overview', 'about', 'contact', 'skills_copy', 'gallery_copy'], true)) {
        $fields = $action === 'overview' ? ['hero_label', 'hero_title', 'intro', 'site_name', 'name', 'age', 'university', 'programme'] : ($action === 'about' ? ['about_title', 'about', 'about_extra'] : ($action === 'contact' ? array_values(array_filter(['portrait_url', 'email', 'github_url', 'linkedin_url', $instagramColumnReady ? 'instagram_url' : null, 'resume_url'])) : ($action === 'skills_copy' ? ['skills_label', 'skills_title', 'skills_emphasis', 'skills_intro', 'skills_empty_text'] : ['gallery_title', 'gallery_intro'])));
        $imageField = $action === 'overview' ? 'portrait_upload' : ($action === 'contact' ? 'contact_portrait_upload' : null);
        if ($imageField) {
            [$imagePath, $uploadError] = receive_admin_image($imageField, 'images', 5);
            if ($uploadError) $message = $uploadError;
            elseif ($imagePath) $_POST['portrait_url'] = $imagePath;
        }
        if (!$message) {
            $set = implode(',', array_map(fn($field) => "`$field`=?", $fields));
            $stmt = db()->prepare("UPDATE profile SET $set WHERE id=1");
            $stmt->execute(array_map(fn($field) => trim($_POST[$field] ?? ''), $fields));
            $message = ucfirst(str_replace('_', ' ', $action)) . ' content saved.';
        }
    } elseif ($action === 'contact_portraits_upload') {
        if (!$contactPortraitsTableReady) $message = 'Run upgrade-contact-portraits.sql in phpMyAdmin before managing the portrait gallery.';
        else {
            $count = (int)db()->query('SELECT COUNT(*) FROM contact_portraits')->fetchColumn();
            [$images, $uploadError] = receive_contact_images(max(0, 10 - $count));
            if ($uploadError) $message = $uploadError;
            else {
                $insert = db()->prepare('INSERT INTO contact_portraits(image_url,sort_order) VALUES(?,?)');
                $order = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM contact_portraits')->fetchColumn();
                foreach ($images as $image) $insert->execute([$image, ++$order]);
                $message = count($images) . ' contact portrait(s) uploaded.';
            }
        }
    } elseif ($action === 'contact_portrait_delete') {
        if (!$contactPortraitsTableReady) $message = 'Run upgrade-contact-portraits.sql in phpMyAdmin before managing the portrait gallery.';
        else { db()->prepare('DELETE FROM contact_portraits WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]); $message = 'Contact portrait removed.'; }
    } elseif ($action === 'contact_portrait_replace') {
        [$imagePath, $uploadError] = receive_admin_image('contact_portrait_replacement', 'contact', 5);
        if ($uploadError) $message = $uploadError;
        elseif (!$imagePath) $message = 'Choose a replacement portrait first.';
        else { db()->prepare('UPDATE contact_portraits SET image_url=? WHERE id=?')->execute([$imagePath, (int)($_POST['id'] ?? 0)]); $message = 'Contact portrait replaced.'; }
    } elseif ($action === 'contact_portrait_move') {
        $portraitId = (int)($_POST['id'] ?? 0);
        $direction = ($_POST['direction'] ?? '') === 'up' ? -1 : 1;
        $orderedPortraits = db()->query('SELECT id,sort_order FROM contact_portraits ORDER BY sort_order,id')->fetchAll();
        $position = array_search($portraitId, array_column($orderedPortraits, 'id'));
        $nextPosition = $position === false ? false : $position + $direction;
        if ($position === false || !isset($orderedPortraits[$nextPosition])) $message = 'Portrait is already at the end of the list.';
        else {
            $move = db()->prepare('UPDATE contact_portraits SET sort_order=? WHERE id=?');
            $move->execute([(int)$orderedPortraits[$nextPosition]['sort_order'], $portraitId]);
            $move->execute([(int)$orderedPortraits[$position]['sort_order'], (int)$orderedPortraits[$nextPosition]['id']]);
            $message = 'Portrait order updated.';
        }
    } elseif ($action === 'category_save') {
        $categoryId = (int)($_POST['id'] ?? 0);
        $categoryName = trim($_POST['name'] ?? '');
        $previousLogos = ['logo_1_url'=>'', 'logo_2_url'=>'', 'logo_3_url'=>''];
        if ($categoryId) {
            $lookup = db()->prepare('SELECT logo_1_url,logo_2_url,logo_3_url FROM skill_categories WHERE id=?');
            $lookup->execute([$categoryId]);
            $previousLogos = $lookup->fetch() ?: $previousLogos;
        }
        $logoPaths = $previousLogos;
        for ($logoIndex = 1; $logoIndex <= 3; $logoIndex++) {
            [$uploadedLogo, $uploadError] = receive_admin_image('category_logo_' . $logoIndex, 'skill-logos', 2);
            if ($uploadError) { $message = $uploadError; break; }
            if ($uploadedLogo) $logoPaths['logo_' . $logoIndex . '_url'] = $uploadedLogo;
            elseif (isset($_POST['clear_logo_' . $logoIndex])) $logoPaths['logo_' . $logoIndex . '_url'] = null;
        }
        if ($message) {
            // Keep the existing category unchanged if any logo upload fails.
        } elseif ($categoryName === '') {
            $message = 'Enter a category name.';
        } elseif ($categoryId) {
            $old = db()->prepare('SELECT name FROM skill_categories WHERE id=?');
            $old->execute([$categoryId]);
            $oldName = $old->fetchColumn();
            db()->prepare('UPDATE skill_categories SET name=?,sort_order=?,is_published=?,logo_1_url=?,logo_2_url=?,logo_3_url=? WHERE id=?')->execute([$categoryName, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_published']) ? 1 : 0, $logoPaths['logo_1_url'], $logoPaths['logo_2_url'], $logoPaths['logo_3_url'], $categoryId]);
            if ($oldName && $oldName !== $categoryName) db()->prepare('UPDATE skills SET category=? WHERE category=?')->execute([$categoryName, $oldName]);
            $message = 'Skill category saved.';
        } else {
            db()->prepare('INSERT INTO skill_categories(name,sort_order,is_published,logo_1_url,logo_2_url,logo_3_url) VALUES(?,?,?,?,?,?)')->execute([$categoryName, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_published']) ? 1 : 0, $logoPaths['logo_1_url'], $logoPaths['logo_2_url'], $logoPaths['logo_3_url']]);
            $message = 'Skill category added.';
        }
    } elseif ($action === 'category_delete') {
        $categoryId = (int)($_POST['id'] ?? 0);
        $lookup = db()->prepare('SELECT name FROM skill_categories WHERE id=?');
        $lookup->execute([$categoryId]);
        $categoryName = $lookup->fetchColumn();
        $used = $categoryName ? db()->prepare('SELECT COUNT(*) FROM skills WHERE category=?') : null;
        if (!$categoryName) $message = 'Category not found.';
        else {
            $used->execute([$categoryName]);
            if ((int)$used->fetchColumn() > 0) $message = 'Move or delete the skills in this category before deleting it.';
            else { db()->prepare('DELETE FROM skill_categories WHERE id=?')->execute([$categoryId]); $message = 'Skill category deleted.'; }
        }
    } elseif ($action === 'delete' && isset($tables[$_POST['section'] ?? ''])) {
        $table = $_POST['section'];
        db()->prepare("DELETE FROM `$table` WHERE id=?")->execute([(int)$_POST['id']]);
        $message = 'Entry deleted.';
    } elseif ($action === 'save' && isset($tables[$_POST['section'] ?? ''])) {
        $table = $_POST['section'];
        if ($table === 'projects') {
            [$imagePath, $uploadError] = receive_project_image();
            if ($uploadError) $message = $uploadError;
            elseif ($imagePath) $_POST['image_url'] = $imagePath;
        } elseif ($table === 'gallery') {
            [$imagePath, $uploadError] = receive_gallery_image();
            if ($uploadError) $message = $uploadError;
            elseif ($imagePath) $_POST['image_url'] = $imagePath;
            elseif (empty($_POST['id'])) $message = 'Upload an image for this gallery entry.';
        } elseif ($table === 'timeline') {
            [$imagePath, $uploadError] = receive_admin_image('timeline_logo', 'journey', 3);
            if ($uploadError) $message = $uploadError;
            elseif ($imagePath) $_POST['logo_url'] = $imagePath;
            elseif (isset($_POST['clear_timeline_logo'])) $_POST['logo_url'] = '';
        }
        if (!$message) {
        $fields = $table === 'projects' ? ['name','category','description','technologies','image_url','image_alt','role','status','demo_url','source_url','case_study_url','sort_order','is_published'] : ($table === 'skills' ? ['category','name','level','sort_order','is_published'] : ($table === 'gallery' ? ['title','caption','image_url','image_alt','sort_order','is_published'] : ['type','period','title','organization','details','logo_url','sort_order','is_published']));
        $values = array_map(fn($field) => $field === 'is_published' ? (isset($_POST[$field]) ? 1 : 0) : trim($_POST[$field] ?? ''), $fields);
        if (!empty($_POST['id'])) {
            $sets = implode(',', array_map(fn($field) => "`$field`=?", $fields));
            $values[] = (int)$_POST['id'];
            db()->prepare("UPDATE `$table` SET $sets WHERE id=?")->execute($values);
        } else {
            $columns = implode(',', array_map(fn($field) => "`$field`", $fields));
            $marks = implode(',', array_fill(0, count($fields), '?'));
            db()->prepare("INSERT INTO `$table` ($columns) VALUES ($marks)")->execute($values);
        }
        $message = 'Entry saved.';
        }
    }
}

$profile = db()->query('SELECT * FROM profile LIMIT 1')->fetch() ?: [];
$siteContent = db()->query('SELECT content_key,content_value FROM site_content')->fetchAll(PDO::FETCH_KEY_PAIR);
if ($contactPortraitsTableReady) $contactPortraits = db()->query('SELECT id,image_url FROM contact_portraits ORDER BY sort_order,id')->fetchAll();
else { $contactPortraits = []; $message = $message ?: 'Run upgrade-contact-portraits.sql in phpMyAdmin to enable the Contact portrait gallery.'; }
$categories = db()->query('SELECT * FROM skill_categories ORDER BY sort_order,id')->fetchAll();
$rows = isset($tables[$section]) ? db()->query("SELECT * FROM `$section` ORDER BY sort_order,id")->fetchAll() : [];
$publishedGalleryCount = $section === 'gallery' ? count(array_filter($rows, fn($row) => !empty($row['is_published']))) : 0;
$edit = null;
if (isset($_GET['edit'], $tables[$section])) {
    $stmt = db()->prepare("SELECT * FROM `$section` WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$recordFields = $section === 'projects' ? ['name'=>'Name','category'=>'Category','description'=>'Description','technologies'=>'Technologies (comma separated)','image_url'=>'Image path / URL','image_alt'=>'Image alt text','role'=>'Role and contribution','status'=>'Status','demo_url'=>'Demo URL','source_url'=>'Source URL','case_study_url'=>'Case study URL','sort_order'=>'Display order','is_published'=>'Published'] : ($section === 'skills' ? ['category'=>'Category','name'=>'Skill','level'=>'Level / context','sort_order'=>'Display order','is_published'=>'Published'] : ($section === 'gallery' ? ['title'=>'Title','caption'=>'Caption','image_url'=>'Image path','image_alt'=>'Image alt text','sort_order'=>'Display order','is_published'=>'Published'] : ['type'=>'Type','period'=>'Period','title'=>'Title','organization'=>'Organization','details'=>'Details','logo_url'=>'Organization logo','sort_order'=>'Display order','is_published'=>'Published']));
$contentFields = $section === 'overview' ? ['hero_label'=>'Small heading','hero_title'=>'Main headline','intro'=>'Introduction','site_name'=>'Header / navbar name','name'=>'Profile name','age'=>'Age','university'=>'University','programme'=>'Programme of study'] : ($section === 'about' ? ['about_title'=>'Section heading','about'=>'Main paragraph','about_extra'=>'Supporting paragraph'] : ($section === 'contact' ? ['portrait_url'=>'Contact portrait image path (also used in Intro)','email'=>'Email','github_url'=>'GitHub URL','linkedin_url'=>'LinkedIn URL'] + ($instagramColumnReady ? ['instagram_url'=>'Instagram URL'] : []) + ['resume_url'=>'Canva Resume URL or path'] : ($section === 'skills_copy' ? ['skills_title'=>'Heading line','skills_emphasis'=>'Emphasized heading line','skills_intro'=>'Short introduction'] : ($section === 'gallery_copy' ? ['gallery_title'=>'Heading','gallery_intro'=>'Short introduction'] : []))));
$editCategory = null;
if ($section === 'skills' && isset($_GET['edit_category'])) {
    $stmt = db()->prepare('SELECT * FROM skill_categories WHERE id=?');
    $stmt->execute([(int)$_GET['edit_category']]);
    $editCategory = $stmt->fetch() ?: null;
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portfolio admin</title><link rel="stylesheet" href="admin.css?v=compact-forms-1"></head><body>
<div class="admin-shell" data-admin-shell>
  <aside class="sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="sidebar-brand"><a href="index.php"><span class="brand-mark">W</span><span class="nav-label">Portfolio Admin</span></a><button class="sidebar-collapse" type="button" data-sidebar-collapse aria-label="Collapse sidebar" title="Collapse sidebar"><span aria-hidden="true">‹</span></button></div>
    <nav class="side-nav" aria-label="Portfolio sections">
      <a class="<?= $section==='overview'?'selected':'' ?>" href="?section=overview" title="Overview"><span class="nav-mark">O</span><span class="nav-label">Overview</span></a>
      <a class="<?= $section==='about'?'selected':'' ?>" href="?section=about" title="About"><span class="nav-mark">A</span><span class="nav-label">About</span></a>
      <a class="<?= $section==='projects'?'selected':'' ?>" href="?section=projects" title="Projects"><span class="nav-mark">P</span><span class="nav-label">Projects</span></a>
      <a class="<?= $section==='skills'?'selected':'' ?>" href="?section=skills" title="Skills"><span class="nav-mark">S</span><span class="nav-label">Skills</span></a>
      <a class="<?= $section==='timeline'?'selected':'' ?>" href="?section=timeline" title="Journey"><span class="nav-mark">J</span><span class="nav-label">Journey</span></a>
      <a class="<?= $section==='gallery'?'selected':'' ?>" href="?section=gallery" title="Gallery"><span class="nav-mark">G</span><span class="nav-label">Gallery</span></a>
      <a class="<?= $section==='contact'?'selected':'' ?>" href="?section=contact" title="Contact"><span class="nav-mark">C</span><span class="nav-label">Contact</span></a>
    </nav>
    <div class="sidebar-bottom"><a class="sidebar-secondary" href="../" title="View portfolio"><span class="nav-mark">↗</span><span class="nav-label">View portfolio</span></a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="sidebar-secondary" type="submit" title="Log out"><span class="nav-mark">↗</span><span class="nav-label">Log out</span></button></form></div>
  </aside>
  <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation"></button>
  <main class="admin-main">
    <header class="admin-topbar"><button class="mobile-menu-toggle" type="button" data-sidebar-open aria-controls="admin-sidebar" aria-expanded="false"><span aria-hidden="true">☰</span><span>Menu</span></button><div class="topbar-title"><p>Portfolio content</p><h1><?= e(ucfirst($section === 'timeline' ? 'Journey' : $section)) ?></h1></div><span class="admin-user"><?= e($_SESSION['admin_email'] ?? 'Admin') ?></span></header>
    <div class="dashboard"><p>Manage the content shown on your public portfolio.</p><?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<?php if (in_array($section, ['overview','about','contact'], true)): ?>
<form class="editor" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= e($section) ?>"><h2>Edit <?= e($section) ?></h2><?php foreach ($contentFields as $field=>$label): ?><label><?= e($label) ?><?php if (in_array($field, ['intro','about','about_extra','hero_title','about_title'], true)): ?><textarea name="<?= e($field) ?>" required><?= e($profile[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($profile[$field] ?? '') ?>" <?= $field === 'email' ? 'type="email"' : '' ?>><?php if ($field === 'portrait_url' && !empty($profile[$field])): ?><div class="image-preview"><img src="../<?= e($profile[$field]) ?>" alt="Current contact portrait"></div><?php endif; ?><?php endif; ?></label><?php endforeach; ?><?php if ($section === 'overview'): ?><label>Upload profile portrait<input type="file" name="portrait_upload" accept="image/jpeg,image/png,image/webp,image/gif"><small>Replaces the portrait used by the lanyard and Contact fallback. JPG, PNG, WebP or GIF, up to 5 MB.</small></label><?php endif; ?><button>Save <?= e($section) ?></button></form>
<?php if (isset($contentGroups[$section])): ?><form class="editor section-copy-editor" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="content_save"><input type="hidden" name="copy_section" value="<?= e($section) ?>"><h2>Edit <?= e($section) ?> labels, links and supporting text</h2><?php foreach ($contentGroups[$section] as $field=>$label): ?><label><?= e($label) ?><?php if (in_array($field, ['hero_art_caption','projects_intro','journey_intro','contact_intro'], true)): ?><textarea name="<?= e($field) ?>"><?= e($siteContent[$field] ?? $contentDefaults[$field]) ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($siteContent[$field] ?? $contentDefaults[$field]) ?>"><?php endif; ?></label><?php endforeach; ?><?php if ($section === 'overview'): ?><label>Upload lanyard badge back logo<input type="file" name="badge_logo_upload" accept="image/jpeg,image/png,image/webp,image/gif"><small>Optional replacement logo image, maximum 5 MB.</small></label><?php if (!empty($siteContent['badge_back_image_url'])): ?><div class="image-preview"><img src="../<?= e($siteContent['badge_back_image_url']) ?>" alt="Current badge back logo"></div><label><input type="checkbox" name="clear_badge_logo" value="1"> Remove current logo</label><?php endif; ?><?php endif; ?><button>Save <?= e($section) ?> labels</button></form><?php endif; ?><?php if ($section === 'overview'): ?><?php foreach (['navigation','footer'] as $copyGroup): ?><form class="editor section-copy-editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="content_save"><input type="hidden" name="copy_section" value="<?= e($copyGroup) ?>"><h2>Edit <?= e($copyGroup) ?></h2><?php foreach ($contentGroups[$copyGroup] as $field=>$label): ?><label><?= e($label) ?><input name="<?= e($field) ?>" value="<?= e($siteContent[$field] ?? $contentDefaults[$field]) ?>"></label><?php endforeach; ?><button>Save <?= e($copyGroup) ?></button></form><?php endforeach; ?><?php endif; ?><?php if ($section === 'contact'): ?><form class="editor contact-upload" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portraits_upload"><h2>Portrait gallery <small><?= count($contactPortraits) ?> / 10 images</small></h2><p class="hint">Upload multiple images. The public portrait cycles to the next image each time it is clicked. JPG, PNG, WebP, or GIF, up to 5 MB each.</p><label>Add portrait images<input type="file" name="contact_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple <?= count($contactPortraits) >= 10 ? 'disabled' : '' ?>><small>Up to <?= max(0, 10 - count($contactPortraits)) ?> more image(s).</small></label><button <?= count($contactPortraits) >= 10 ? 'disabled' : '' ?>>Upload images</button></form><div class="contact-admin-grid"><?php foreach ($contactPortraits as $portrait): ?><article class="contact-admin-card"><img src="../<?= e($portrait['image_url']) ?>" alt="Contact portrait preview"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portrait_replace"><input type="hidden" name="id" value="<?= (int)$portrait['id'] ?>"><input type="file" name="contact_portrait_replacement" accept="image/jpeg,image/png,image/webp,image/gif" required><button>Replace</button></form><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portrait_move"><input type="hidden" name="id" value="<?= (int)$portrait['id'] ?>"><button name="direction" value="up">Move up</button><button name="direction" value="down">Move down</button></form><form method="post" onsubmit="return confirm('Remove this portrait?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portrait_delete"><input type="hidden" name="id" value="<?= (int)$portrait['id'] ?>"><button class="danger">Remove</button></form></article><?php endforeach; ?></div><?php endif; ?>
<?php else: ?>
<?php if ($section === 'skills'): ?><form class="editor section-copy-editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="skills_copy"><h2>Skills section text</h2><label>Small section label<input name="skills_label" value="<?= e($profile['skills_label'] ?? '03 — CAPABILITIES') ?>" required></label><label>Heading line<input name="skills_title" value="<?= e($profile['skills_title'] ?? 'Learning by') ?>" required></label><label>Emphasized line<input name="skills_emphasis" value="<?= e($profile['skills_emphasis'] ?? 'making.') ?>" required></label><label>Short introduction<textarea name="skills_intro"><?= e($profile['skills_intro'] ?? '') ?></textarea></label><label>Empty category message<input name="skills_empty_text" value="<?= e($profile['skills_empty_text'] ?? 'Skills to be added') ?>" required></label><button>Save Skills section</button></form><?php endif; ?>
<?php if ($section === 'gallery'): ?><form class="editor section-copy-editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="gallery_copy"><h2>Gallery heading and introduction</h2><label>Heading<input name="gallery_title" value="<?= e($profile['gallery_title'] ?? 'A visual journal') ?>" required></label><label>Short introduction<textarea name="gallery_intro"><?= e($profile['gallery_intro'] ?? '') ?></textarea></label><button>Save Gallery section</button></form><p class="hint">The public Gallery displays up to 4 published images, ordered by Display order. Currently <?= $publishedGalleryCount ?> image(s) are published. Edit each image below to change its title, caption, order, or visibility.</p><?php endif; ?>
<div class="layout"><section>
<?php if ($section === 'skills'): ?><div class="list-head"><h2>Skill categories</h2><a class="button" href="?section=skills&amp;new_category=1">+ Add category</a></div><?php foreach ($categories as $category): ?><article class="row"><div class="category-preview-logos"><?php foreach (['logo_1_url','logo_2_url','logo_3_url'] as $categoryLogo): if (!empty($category[$categoryLogo])): ?><img src="../<?= e($category[$categoryLogo]) ?>" alt="" loading="lazy"><?php endif; endforeach; ?><strong><?= e($category['name']) ?></strong><small>Order <?= (int)$category['sort_order'] ?> · <?= !empty($category['is_published']) ? 'Published' : 'Hidden' ?></small></div><div class="row-actions"><a href="?section=skills&amp;edit_category=<?= (int)$category['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category? It must not contain skills.')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="category_delete"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><button class="danger">Delete</button></form></div></article><?php endforeach; ?><?php endif; ?>
<div class="list-head"><h2><?= e($tables[$section]) ?></h2><a class="button" href="?section=<?= e($section) ?>&amp;new=1">+ Add new</a></div><?php foreach ($rows as $row): ?><article class="row"><?php if (in_array($section, ['projects','gallery','timeline'], true) && !empty($rowImage = ($row['image_url'] ?? $row['logo_url'] ?? ''))): ?><div class="row-media"><img src="<?= e(preg_match('~^(https?:)?//~i', $rowImage) ? $rowImage : '../' . ltrim($rowImage, '/')) ?>" alt="" loading="lazy"></div><?php endif; ?><div class="row-copy"><strong><?= e($row['name'] ?? $row['title']) ?></strong><small><?= e($row['category'] ?? $row['period'] ?? $row['type'] ?? $row['caption'] ?? '') ?> · <?= !empty($row['is_published']) ? 'Published' : 'Hidden' ?></small></div><div class="row-actions"><a href="?section=<?= e($section) ?>&amp;edit=<?= (int)$row['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this entry?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="danger">Delete</button></form></div></article><?php endforeach; ?></section>
<?php if ($section === 'skills' && ($editCategory || isset($_GET['new_category']))): ?><form class="editor" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="category_save"><input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>"><h2><?= $editCategory ? 'Edit' : 'Add' ?> skill category</h2><label>Category name<input name="name" value="<?= e($editCategory['name'] ?? '') ?>" required></label><label>Display order<input type="number" name="sort_order" value="<?= e((string)($editCategory['sort_order'] ?? 0)) ?>"></label><?php for ($logoIndex=1; $logoIndex<=3; $logoIndex++): $logoField="logo_{$logoIndex}_url"; ?><label>Logo <?= $logoIndex ?><input type="file" name="category_logo_<?= $logoIndex ?>" accept="image/jpeg,image/png,image/webp,image/gif"><small>Optional brand logo, JPG/PNG/WebP/GIF, up to 2 MB. Leave blank to keep the current logo.</small></label><?php if (!empty($editCategory[$logoField])): ?><div class="image-preview"><img src="../<?= e($editCategory[$logoField]) ?>" alt="Current category logo <?= $logoIndex ?>"></div><label><input type="checkbox" name="clear_logo_<?= $logoIndex ?>" value="1"> Remove this logo</label><?php endif; ?><?php endfor; ?><label>Published<input type="checkbox" name="is_published" value="1" <?= !$editCategory || !empty($editCategory['is_published']) ? 'checked' : '' ?>></label><button>Save category</button><a class="cancel" href="?section=skills">Cancel</a></form>
<?php elseif ($edit || isset($_GET['new'])): ?><form class="editor" method="post" <?= in_array($section, ['projects','gallery','timeline'], true) ? 'enctype="multipart/form-data"' : '' ?>><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><h2><?= $edit ? 'Edit' : 'Add' ?> entry</h2>
<?php if ($section === 'projects'): ?><label>Upload project image<input type="file" name="project_image" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF. Maximum size: 5 MB. A new upload replaces the current image.</small></label><?php if (!empty($edit['image_url'])): ?><div class="image-preview"><img src="../<?= e($edit['image_url']) ?>" alt="Current project image"></div><?php endif; ?><?php endif; ?>
<?php if ($section === 'timeline' && !empty($edit['logo_url'])): ?><div class="image-preview"><img src="../<?= e($edit['logo_url']) ?>" alt="Current organization logo"></div><?php endif; ?><?php if ($section === 'gallery'): ?><label>Upload gallery image<input type="file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/gif" <?= empty($edit['image_url']) ? 'required' : '' ?>><small>JPG, PNG, WebP, or GIF. Maximum size: 8 MB. A new upload replaces the current image.</small></label><input type="hidden" name="image_url" value="<?= e($edit['image_url'] ?? '') ?>"><?php if (!empty($edit['image_url'])): ?><div class="image-preview"><img src="../<?= e($edit['image_url']) ?>" alt="Current gallery image"></div><?php endif; ?><?php endif; ?>
<?php foreach ($recordFields as $field=>$label): ?><label><?= e($label) ?><?php if ($field === 'is_published'): ?><input type="checkbox" name="is_published" value="1" <?= !isset($edit) || !empty($edit[$field]) ? 'checked' : '' ?>><?php elseif ($section === 'skills' && $field === 'category'): ?><select name="category" required><?php foreach ($categories as $category): ?><option value="<?= e($category['name']) ?>" <?= ($edit['category'] ?? '') === $category['name'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select><?php elseif ($field === 'image_url' && $section === 'gallery'): ?><span>Image is managed by upload above.</span><?php elseif ($field === 'logo_url' && $section === 'timeline'): ?><input type="file" name="timeline_logo" accept="image/jpeg,image/png,image/webp,image/gif"><small>Organization logo, JPG/PNG/WebP/GIF, up to 3 MB. Leave blank to keep current.</small><input type="hidden" name="logo_url" value="<?= e($edit['logo_url'] ?? '') ?>"><label><input type="checkbox" name="clear_timeline_logo" value="1"> Remove current logo</label><?php elseif (in_array($field, ['description','details','caption'], true)): ?><textarea name="<?= e($field) ?>"><?= e($edit[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($edit[$field] ?? '') ?>" <?= $field === 'sort_order' ? 'type="number"' : '' ?> <?= in_array($field, ['name','title','category','image_alt'], true) ? 'required' : '' ?>><?php endif; ?></label><?php endforeach; ?><button>Save entry</button><a class="cancel" href="?section=<?= e($section) ?>">Cancel</a></form><?php endif; ?></div><?php endif; ?></div></main></div><script src="admin.js?v=sidebar-2"></script></body></html>

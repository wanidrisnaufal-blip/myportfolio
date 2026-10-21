<?php
require __DIR__ . '/../includes/bootstrap.php';

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
$section = $_GET['section'] ?? 'overview';
if ($section === 'hero') $section = 'overview';
if ($section === 'profile') $section = 'contact';
if (!isset($tables[$section]) && !in_array($section, ['overview', 'about', 'contact', 'skills_copy', 'gallery_copy'], true)) $section = 'overview';
$message = '';
$contactPortraitsTableReady = true;
try { db()->query('SELECT id FROM contact_portraits LIMIT 1'); }
catch (PDOException $exception) { $contactPortraitsTableReady = false; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['overview', 'about', 'contact', 'skills_copy', 'gallery_copy'], true)) {
        $fields = $action === 'overview' ? ['hero_label', 'hero_title', 'intro', 'site_name', 'name', 'age', 'university', 'programme'] : ($action === 'about' ? ['about_title', 'about', 'about_extra'] : ($action === 'contact' ? ['portrait_url', 'email', 'github_url', 'linkedin_url', 'resume_url'] : ($action === 'skills_copy' ? ['skills_label', 'skills_title', 'skills_emphasis', 'skills_intro', 'skills_empty_text'] : ['gallery_title', 'gallery_intro'])));
        $set = implode(',', array_map(fn($field) => "`$field`=?", $fields));
        $stmt = db()->prepare("UPDATE profile SET $set WHERE id=1");
        $stmt->execute(array_map(fn($field) => trim($_POST[$field] ?? ''), $fields));
        $message = ucfirst(str_replace('_', ' ', $action)) . ' content saved.';
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
    } elseif ($action === 'category_save') {
        $categoryId = (int)($_POST['id'] ?? 0);
        $categoryName = trim($_POST['name'] ?? '');
        if ($categoryName === '') {
            $message = 'Enter a category name.';
        } elseif ($categoryId) {
            $old = db()->prepare('SELECT name FROM skill_categories WHERE id=?');
            $old->execute([$categoryId]);
            $oldName = $old->fetchColumn();
            db()->prepare('UPDATE skill_categories SET name=?,sort_order=?,is_published=? WHERE id=?')->execute([$categoryName, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_published']) ? 1 : 0, $categoryId]);
            if ($oldName && $oldName !== $categoryName) db()->prepare('UPDATE skills SET category=? WHERE category=?')->execute([$categoryName, $oldName]);
            $message = 'Skill category saved.';
        } else {
            db()->prepare('INSERT INTO skill_categories(name,sort_order,is_published) VALUES(?,?,?)')->execute([$categoryName, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_published']) ? 1 : 0]);
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
        }
        if (!$message) {
        $fields = $table === 'projects' ? ['name','category','description','technologies','image_url','image_alt','role','status','demo_url','source_url','case_study_url','sort_order','is_published'] : ($table === 'skills' ? ['category','name','level','sort_order','is_published'] : ($table === 'gallery' ? ['title','caption','image_url','image_alt','sort_order','is_published'] : ['type','period','title','organization','details','sort_order','is_published']));
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
$recordFields = $section === 'projects' ? ['name'=>'Name','category'=>'Category','description'=>'Description','technologies'=>'Technologies (comma separated)','image_url'=>'Image path / URL','image_alt'=>'Image alt text','role'=>'Role and contribution','status'=>'Status','demo_url'=>'Demo URL','source_url'=>'Source URL','case_study_url'=>'Case study URL','sort_order'=>'Display order','is_published'=>'Published'] : ($section === 'skills' ? ['category'=>'Category','name'=>'Skill','level'=>'Level / context','sort_order'=>'Display order','is_published'=>'Published'] : ($section === 'gallery' ? ['title'=>'Title','caption'=>'Caption','image_url'=>'Image path','image_alt'=>'Image alt text','sort_order'=>'Display order','is_published'=>'Published'] : ['type'=>'Type','period'=>'Period','title'=>'Title','organization'=>'Organization','details'=>'Details','sort_order'=>'Display order','is_published'=>'Published']));
$contentFields = $section === 'overview' ? ['hero_label'=>'Small heading','hero_title'=>'Main headline','intro'=>'Introduction','site_name'=>'Header / navbar name','name'=>'Profile name','age'=>'Age','university'=>'University','programme'=>'Programme of study'] : ($section === 'about' ? ['about_title'=>'Section heading','about'=>'Main paragraph','about_extra'=>'Supporting paragraph'] : ($section === 'contact' ? ['portrait_url'=>'Contact portrait image path (also used in Intro)','email'=>'Email','github_url'=>'GitHub URL','linkedin_url'=>'LinkedIn URL','resume_url'=>'Resume path or URL'] : ($section === 'skills_copy' ? ['skills_title'=>'Heading line','skills_emphasis'=>'Emphasized heading line','skills_intro'=>'Short introduction'] : ($section === 'gallery_copy' ? ['gallery_title'=>'Heading','gallery_intro'=>'Short introduction'] : []))));
$editCategory = null;
if ($section === 'skills' && isset($_GET['edit_category'])) {
    $stmt = db()->prepare('SELECT * FROM skill_categories WHERE id=?');
    $stmt->execute([(int)$_GET['edit_category']]);
    $editCategory = $stmt->fetch() ?: null;
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portfolio admin</title><link rel="stylesheet" href="admin.css"></head><body>
<header class="top"><a href="../">← View portfolio</a><strong>Portfolio Admin</strong><a href="index.php">Refresh dashboard</a></header>
<main class="dashboard"><h1>Dashboard</h1><p>Manage the content shown on your public portfolio.</p><?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<nav class="tabs" aria-label="Portfolio pages"><a class="<?= $section==='overview'?'selected':'' ?>" href="?section=overview">Overview</a><a class="<?= $section==='about'?'selected':'' ?>" href="?section=about">About</a><a class="<?= $section==='projects'?'selected':'' ?>" href="?section=projects">Projects</a><a class="<?= $section==='skills'?'selected':'' ?>" href="?section=skills">Skills</a><a class="<?= $section==='timeline'?'selected':'' ?>" href="?section=timeline">Journey</a><a class="<?= $section==='gallery'?'selected':'' ?>" href="?section=gallery">Gallery</a><a class="<?= $section==='contact'?'selected':'' ?>" href="?section=contact">Contact</a></nav>
<?php if (in_array($section, ['overview','about','contact'], true)): ?>
<form class="editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= e($section) ?>"><h2>Edit <?= e($section) ?></h2><?php foreach ($contentFields as $field=>$label): ?><label><?= e($label) ?><?php if (in_array($field, ['intro','about','about_extra','hero_title','about_title'], true)): ?><textarea name="<?= e($field) ?>" required><?= e($profile[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($profile[$field] ?? '') ?>" <?= $field === 'email' ? 'type="email"' : '' ?>><?php if ($field === 'portrait_url' && !empty($profile[$field])): ?><div class="image-preview"><img src="../<?= e($profile[$field]) ?>" alt="Current contact portrait"></div><?php endif; ?><?php endif; ?></label><?php endforeach; ?><button>Save <?= e($section) ?></button></form>
<?php if ($section === 'contact'): ?><form class="editor contact-upload" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portraits_upload"><h2>Portrait gallery <small><?= count($contactPortraits) ?> / 10 images</small></h2><p class="hint">Upload multiple images. The public portrait cycles to the next image each time it is clicked. JPG, PNG, WebP, or GIF, up to 5 MB each.</p><label>Add portrait images<input type="file" name="contact_images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple <?= count($contactPortraits) >= 10 ? 'disabled' : '' ?>><small>Up to <?= max(0, 10 - count($contactPortraits)) ?> more image(s).</small></label><button <?= count($contactPortraits) >= 10 ? 'disabled' : '' ?>>Upload images</button></form><div class="contact-admin-grid"><?php foreach ($contactPortraits as $portrait): ?><article class="contact-admin-card"><img src="../<?= e($portrait['image_url']) ?>" alt="Contact portrait preview"><form method="post" onsubmit="return confirm('Remove this portrait?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="contact_portrait_delete"><input type="hidden" name="id" value="<?= (int)$portrait['id'] ?>"><button class="danger">Remove</button></form></article><?php endforeach; ?></div><?php endif; ?>
<?php else: ?>
<?php if ($section === 'skills'): ?><form class="editor section-copy-editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="skills_copy"><h2>Skills section text</h2><label>Small section label<input name="skills_label" value="<?= e($profile['skills_label'] ?? '03 — CAPABILITIES') ?>" required></label><label>Heading line<input name="skills_title" value="<?= e($profile['skills_title'] ?? 'Learning by') ?>" required></label><label>Emphasized line<input name="skills_emphasis" value="<?= e($profile['skills_emphasis'] ?? 'making.') ?>" required></label><label>Short introduction<textarea name="skills_intro"><?= e($profile['skills_intro'] ?? '') ?></textarea></label><label>Empty category message<input name="skills_empty_text" value="<?= e($profile['skills_empty_text'] ?? 'Skills to be added') ?>" required></label><button>Save Skills section</button></form><?php endif; ?>
<?php if ($section === 'gallery'): ?><form class="editor section-copy-editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="gallery_copy"><h2>Gallery heading and introduction</h2><label>Heading<input name="gallery_title" value="<?= e($profile['gallery_title'] ?? 'A visual journal') ?>" required></label><label>Short introduction<textarea name="gallery_intro"><?= e($profile['gallery_intro'] ?? '') ?></textarea></label><button>Save Gallery section</button></form><p class="hint">The public Gallery displays up to 4 published images, ordered by Display order. Currently <?= $publishedGalleryCount ?> image(s) are published. Edit each image below to change its title, caption, order, or visibility.</p><?php endif; ?>
<div class="layout"><section>
<?php if ($section === 'skills'): ?><div class="list-head"><h2>Skill categories</h2><a class="button" href="?section=skills&amp;new_category=1">+ Add category</a></div><?php foreach ($categories as $category): ?><article class="row"><div><strong><?= e($category['name']) ?></strong><small>Order <?= (int)$category['sort_order'] ?> · <?= !empty($category['is_published']) ? 'Published' : 'Hidden' ?></small></div><div class="row-actions"><a href="?section=skills&amp;edit_category=<?= (int)$category['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this category? It must not contain skills.')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="category_delete"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><button class="danger">Delete</button></form></div></article><?php endforeach; ?><?php endif; ?>
<div class="list-head"><h2><?= e($tables[$section]) ?></h2><a class="button" href="?section=<?= e($section) ?>&amp;new=1">+ Add new</a></div><?php foreach ($rows as $row): ?><article class="row"><?php if (in_array($section, ['projects','gallery'], true) && !empty($row['image_url'])): ?><div class="row-media"><img src="<?= e(preg_match('~^(https?:)?//~i', $row['image_url']) ? $row['image_url'] : '../' . ltrim($row['image_url'], '/')) ?>" alt="" loading="lazy"></div><?php endif; ?><div class="row-copy"><strong><?= e($row['name'] ?? $row['title']) ?></strong><small><?= e($row['category'] ?? $row['period'] ?? $row['type'] ?? $row['caption'] ?? '') ?> · <?= !empty($row['is_published']) ? 'Published' : 'Hidden' ?></small></div><div class="row-actions"><a href="?section=<?= e($section) ?>&amp;edit=<?= (int)$row['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this entry?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="danger">Delete</button></form></div></article><?php endforeach; ?></section>
<?php if ($section === 'skills' && ($editCategory || isset($_GET['new_category']))): ?><form class="editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="category_save"><input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>"><h2><?= $editCategory ? 'Edit' : 'Add' ?> skill category</h2><label>Category name<input name="name" value="<?= e($editCategory['name'] ?? '') ?>" required></label><label>Display order<input type="number" name="sort_order" value="<?= e((string)($editCategory['sort_order'] ?? 0)) ?>"></label><label>Published<input type="checkbox" name="is_published" value="1" <?= !$editCategory || !empty($editCategory['is_published']) ? 'checked' : '' ?>></label><button>Save category</button><a class="cancel" href="?section=skills">Cancel</a></form>
<?php elseif ($edit || isset($_GET['new'])): ?><form class="editor" method="post" <?= in_array($section, ['projects','gallery'], true) ? 'enctype="multipart/form-data"' : '' ?>><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><h2><?= $edit ? 'Edit' : 'Add' ?> entry</h2>
<?php if ($section === 'projects'): ?><label>Upload project image<input type="file" name="project_image" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF. Maximum size: 5 MB. A new upload replaces the current image.</small></label><?php if (!empty($edit['image_url'])): ?><div class="image-preview"><img src="../<?= e($edit['image_url']) ?>" alt="Current project image"></div><?php endif; ?><?php endif; ?>
<?php if ($section === 'gallery'): ?><label>Upload gallery image<input type="file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/gif" <?= empty($edit['image_url']) ? 'required' : '' ?>><small>JPG, PNG, WebP, or GIF. Maximum size: 8 MB. A new upload replaces the current image.</small></label><input type="hidden" name="image_url" value="<?= e($edit['image_url'] ?? '') ?>"><?php if (!empty($edit['image_url'])): ?><div class="image-preview"><img src="../<?= e($edit['image_url']) ?>" alt="Current gallery image"></div><?php endif; ?><?php endif; ?>
<?php foreach ($recordFields as $field=>$label): ?><label><?= e($label) ?><?php if ($field === 'is_published'): ?><input type="checkbox" name="is_published" value="1" <?= !isset($edit) || !empty($edit[$field]) ? 'checked' : '' ?>><?php elseif ($section === 'skills' && $field === 'category'): ?><select name="category" required><?php foreach ($categories as $category): ?><option value="<?= e($category['name']) ?>" <?= ($edit['category'] ?? '') === $category['name'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select><?php elseif ($field === 'image_url' && $section === 'gallery'): ?><span>Image is managed by upload above.</span><?php elseif (in_array($field, ['description','details','caption'], true)): ?><textarea name="<?= e($field) ?>"><?= e($edit[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($edit[$field] ?? '') ?>" <?= $field === 'sort_order' ? 'type="number"' : '' ?> <?= in_array($field, ['name','title','category','image_alt'], true) ? 'required' : '' ?>><?php endif; ?></label><?php endforeach; ?><button>Save entry</button><a class="cancel" href="?section=<?= e($section) ?>">Cancel</a></form><?php endif; ?></div><?php endif; ?></main></body></html>

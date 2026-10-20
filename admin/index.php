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

$tables = ['projects' => 'Projects', 'skills' => 'Skills', 'timeline' => 'Education & Experience'];
$section = $_GET['section'] ?? 'projects';
if (!isset($tables[$section]) && !in_array($section, ['hero', 'about', 'profile'], true)) $section = 'projects';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['hero', 'about', 'profile'], true)) {
        $fields = $action === 'hero' ? ['hero_label', 'hero_title', 'intro'] : ($action === 'about' ? ['about_title', 'about', 'about_extra'] : ['name', 'age', 'university', 'programme', 'portrait_url', 'email', 'github_url', 'linkedin_url', 'resume_url']);
        $set = implode(',', array_map(fn($field) => "`$field`=?", $fields));
        $stmt = db()->prepare("UPDATE profile SET $set WHERE id=1");
        $stmt->execute(array_map(fn($field) => trim($_POST[$field] ?? ''), $fields));
        $message = ucfirst($action) . ' content saved.';
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
        }
        if (!$message) {
        $fields = $table === 'projects' ? ['name','category','description','technologies','image_url','image_alt','role','status','demo_url','source_url','case_study_url','sort_order','is_published'] : ($table === 'skills' ? ['category','name','level','sort_order','is_published'] : ['type','period','title','organization','details','sort_order','is_published']);
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
$rows = isset($tables[$section]) ? db()->query("SELECT * FROM `$section` ORDER BY sort_order,id")->fetchAll() : [];
$edit = null;
if (isset($_GET['edit'], $tables[$section])) {
    $stmt = db()->prepare("SELECT * FROM `$section` WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$recordFields = $section === 'projects' ? ['name'=>'Name','category'=>'Category','description'=>'Description','technologies'=>'Technologies (comma separated)','image_url'=>'Image path / URL','image_alt'=>'Image alt text','role'=>'Role and contribution','status'=>'Status','demo_url'=>'Demo URL','source_url'=>'Source URL','case_study_url'=>'Case study URL','sort_order'=>'Display order','is_published'=>'Published'] : ($section === 'skills' ? ['category'=>'Category','name'=>'Skill','level'=>'Level / context','sort_order'=>'Display order','is_published'=>'Published'] : ['type'=>'Type','period'=>'Period','title'=>'Title','organization'=>'Organization','details'=>'Details','sort_order'=>'Display order','is_published'=>'Published']);
$contentFields = $section === 'hero' ? ['hero_label'=>'Small heading','hero_title'=>'Main headline','intro'=>'Introduction'] : ($section === 'about' ? ['about_title'=>'Section heading','about'=>'Main paragraph','about_extra'=>'Supporting paragraph'] : ['name'=>'Name','age'=>'Age','university'=>'University','programme'=>'Programme of study','portrait_url'=>'Portrait image path','email'=>'Email','github_url'=>'GitHub URL','linkedin_url'=>'LinkedIn URL','resume_url'=>'Resume path or URL']);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Portfolio admin</title><link rel="stylesheet" href="admin.css"></head><body>
<header class="top"><a href="../">← View portfolio</a><strong>Portfolio Admin</strong><a href="index.php">Refresh dashboard</a></header>
<main class="dashboard"><h1>Dashboard</h1><p>Manage the content shown on your public portfolio.</p><?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<nav class="tabs"><?php foreach ($tables as $key=>$label): ?><a class="<?= $section===$key?'selected':'' ?>" href="?section=<?= e($key) ?>"><?= e($label) ?></a><?php endforeach; ?><a class="<?= $section==='hero'?'selected':'' ?>" href="?section=hero">Hero</a><a class="<?= $section==='about'?'selected':'' ?>" href="?section=about">About</a><a class="<?= $section==='profile'?'selected':'' ?>" href="?section=profile">Profile &amp; links</a></nav>
<?php if (in_array($section, ['hero','about','profile'], true)): ?>
<form class="editor" method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= e($section) ?>"><h2>Edit <?= e($section === 'profile' ? 'profile and links' : $section) ?></h2><?php foreach ($contentFields as $field=>$label): ?><label><?= e($label) ?><?php if (in_array($field, ['intro','about','about_extra','hero_title','about_title'], true)): ?><textarea name="<?= e($field) ?>" required><?= e($profile[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($profile[$field] ?? '') ?>" <?= $field === 'email' ? 'type="email"' : '' ?>><?php endif; ?></label><?php endforeach; ?><button>Save <?= e($section) ?></button></form>
<?php else: ?><div class="layout"><section><div class="list-head"><h2><?= e($tables[$section]) ?></h2><a class="button" href="?section=<?= e($section) ?>&amp;new=1">+ Add new</a></div><?php foreach ($rows as $row): ?><article class="row"><div><strong><?= e($row['name'] ?? $row['title']) ?></strong><small><?= e($row['category'] ?? $row['period'] ?? $row['type'] ?? '') ?> · <?= !empty($row['is_published']) ? 'Published' : 'Hidden' ?></small></div><div class="row-actions"><a href="?section=<?= e($section) ?>&amp;edit=<?= (int)$row['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this entry?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="danger">Delete</button></form></div></article><?php endforeach; ?></section>
<?php if ($edit || isset($_GET['new'])): ?><form class="editor" method="post" <?= $section === 'projects' ? 'enctype="multipart/form-data"' : '' ?>><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="section" value="<?= e($section) ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><h2><?= $edit ? 'Edit' : 'Add' ?> entry</h2><?php if ($section === 'projects'): ?><label>Upload project image<input type="file" name="project_image" accept="image/jpeg,image/png,image/webp,image/gif"><small>JPG, PNG, WebP, or GIF. Maximum size: 5 MB. A new upload replaces the current image.</small></label><?php if (!empty($edit['image_url'])): ?><div class="image-preview"><img src="../<?= e($edit['image_url']) ?>" alt="Current project image"></div><?php endif; ?><?php endif; ?><?php foreach ($recordFields as $field=>$label): ?><label><?= e($label) ?><?php if ($field === 'is_published'): ?><input type="checkbox" name="is_published" value="1" <?= !isset($edit) || !empty($edit[$field]) ? 'checked' : '' ?>><?php elseif (in_array($field, ['description','details'], true)): ?><textarea name="<?= e($field) ?>"><?= e($edit[$field] ?? '') ?></textarea><?php else: ?><input name="<?= e($field) ?>" value="<?= e($edit[$field] ?? '') ?>" <?= $field === 'sort_order' ? 'type="number"' : '' ?> <?= in_array($field, ['name','title','category'], true) ? 'required' : '' ?>><?php endif; ?></label><?php endforeach; ?><button>Save entry</button><a class="cancel" href="?section=<?= e($section) ?>">Cancel</a></form><?php endif; ?></div><?php endif; ?></main></body></html>

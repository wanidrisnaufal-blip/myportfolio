<?php
require __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $statement = db()->prepare('SELECT id, email, password_hash FROM admins WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $admin = $statement->fetch();
    } catch (PDOException $exception) {
        $admin = false;
        $error = 'Cannot connect to the admin account database. Check the database settings.';
    }

    if (!$error && $admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        header('Location: index.php');
        exit;
    }
    if (!$error) $error = 'Email or password is incorrect.';
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin login</title>
  <link rel="stylesheet" href="admin.css?v=compact-forms-1">
</head>
<body>
  <main class="login">
    <a href="../">← View portfolio</a>
    <h1>Admin login</h1>
    <p>Sign in to manage your portfolio content.</p>
    <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Email<input type="email" name="email" autocomplete="username" required value="<?= e($_POST['email'] ?? '') ?>"></label>
      <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
      <button type="submit">Sign in</button>
    </form>
  </main>
</body>
</html>

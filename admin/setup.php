<?php
require __DIR__ . '/../includes/bootstrap.php';

$error = '';
$adminCount = null;
try {
    $adminCount = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
} catch (PDOException $exception) {
    $error = 'Cannot connect to the database or find the admins table. Check the database settings and import the portfolio database first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adminCount === 0 && !$error) {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Choose a password with at least 6 characters.';
    } elseif ($password !== $confirmation) {
        $error = 'The passwords do not match.';
    } else {
        try {
            // Recheck immediately before inserting so setup closes after the first account is created.
            if ((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() !== 0) {
                $adminCount = 1;
                $error = 'An admin account already exists. Sign in instead.';
            } else {
                $statement = db()->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)');
                $statement->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)db()->lastInsertId();
                $_SESSION['admin_email'] = $email;
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $exception) {
            $error = 'The admin account could not be created. Check that the email is not already in use.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Set up portfolio admin</title>
  <link rel="stylesheet" href="admin.css?v=compact-forms-1">
</head>
<body>
  <main class="login">
    <a href="../">← View portfolio</a>
    <h1>Set up Admin</h1>
    <?php if ($error): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($adminCount === 0): ?>
      <p>Create the first admin account. This setup page closes automatically after an account is created.</p>
      <form method="post" action="setup.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label>Email<input type="email" name="email" autocomplete="username" required value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Password<input type="password" name="password" autocomplete="new-password" minlength="6" required></label>
        <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="6" required></label>
        <button type="submit">Create admin account</button>
      </form>
    <?php elseif ($adminCount !== null && $adminCount > 0): ?>
      <p>An admin account already exists. To reset its password, use the database recovery instructions in README.md.</p>
      <a href="login.php">Go to login</a>
    <?php endif; ?>
  </main>
</body>
</html>

<?php
/**
 * First-run setup. Sets the initial admin account, then locks: once an admin
 * exists this page redirects to login and never creates another account here.
 */

require_once __DIR__ . '/layout.php';

$error = '';
$dbReady = true;
try {
    db();
} catch (Throwable $e) {
    $dbReady = false;
}

if ($dbReady && admin_exists()) {
    header('Location: login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!$dbReady) {
        $error = 'The database is not configured yet. Complete the deploy steps first.';
    } elseif (admin_exists()) {
        header('Location: login.php');
        exit;
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if ($username === '' || mb_strlen($username) > 60 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $error = 'Choose a username using letters, numbers, dots, dashes or underscores.';
        } elseif (strlen($password) < 10) {
            $error = 'Use a password of at least 10 characters.';
        } elseif ($password !== $confirm) {
            $error = 'The passwords do not match.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                db()->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :h)')
                    ->execute([':u' => $username, ':h' => $hash]);
                header('Location: login.php?setup=done');
                exit;
            } catch (Throwable $e) {
                $error = 'Could not create the account. Please try again.';
            }
        }
    }
}

admin_head('Setup');
?>
<main class="auth">
  <div class="auth__card">
    <div class="auth__brand">JSD <b>CONSTRUCTION</b></div>
    <h1>Create your admin account</h1>
    <p class="auth__lead">This is a one-time setup. Once your account is created this page locks.</p>

    <?php if (!$dbReady): ?>
      <p class="auth__error">The database is not configured yet. Finish the cPanel deploy steps, then reload.</p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="auth__error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="admin" required maxlength="60">

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required minlength="10">

      <label for="confirm">Confirm password</label>
      <input type="password" id="confirm" name="confirm" required minlength="10">

      <button type="submit" class="btn btn--gold">Create account</button>
    </form>
  </div>
</main>
<?php admin_foot(); ?>

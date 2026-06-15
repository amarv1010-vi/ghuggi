<?php
require_once __DIR__ . '/auth.php';

/** Render the admin page head. */
function admin_head(string $title, bool $withApp = false): void
{
    $recaptchaKey = trim((string) cfg('RECAPTCHA_SITE_KEY'));
    ?>
<!doctype html>
<html lang="en-AU">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> | JSD Construction Admin</title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <?php if ($withApp && $recaptchaKey !== ''): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= e($recaptchaKey) ?>" async defer></script>
  <?php endif; ?>
</head>
<body class="admin">
    <?php
}

function admin_foot(bool $withApp = false): void
{
    if ($withApp) {
        echo '<script src="../assets/js/admin.js" defer></script>';
    }
    echo "\n</body>\n</html>";
}

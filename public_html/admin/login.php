<?php
/**
 * Admin login. Session-based, CSRF-protected, rate-limited, with a reCAPTCHA v3
 * hook point (active only when keys are configured).
 */

require_once __DIR__ . '/layout.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$dbReady = true;
try {
    db();
} catch (Throwable $e) {
    $dbReady = false;
}
if ($dbReady && !admin_exists()) {
    header('Location: setup.php');
    exit;
}

$error = '';
$notice = isset($_GET['setup']) ? 'Account created. Please log in.' : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (login_rate_limited()) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } else {
        $secret = trim((string) cfg('RECAPTCHA_SECRET'));
        $captchaOk = true;
        if ($secret !== '') {
            $captchaOk = jsd_admin_verify_recaptcha($secret, (string) ($_POST['recaptcha_token'] ?? ''));
        }
        if (!$captchaOk) {
            $error = 'Verification failed. Please try again.';
        } elseif (attempt_login(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}

admin_head('Login', true);
?>
<main class="auth">
  <div class="auth__card">
    <div class="auth__brand">JSD <b>CONSTRUCTION</b></div>
    <h1>Admin login</h1>
    <?php if ($notice): ?><p class="auth__notice"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="auth__error"><?= e($error) ?></p><?php endif; ?>

    <form method="post" id="login-form" data-recaptcha-key="<?= e(trim((string) cfg('RECAPTCHA_SITE_KEY'))) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="recaptcha_token" id="recaptcha_token" value="">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autocomplete="username">

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password">

      <button type="submit" class="btn btn--gold">Log in</button>
    </form>
  </div>
</main>
<?php admin_foot(true); ?>
<?php

/** Verify reCAPTCHA for admin login. */
function jsd_admin_verify_recaptcha(string $secret, string $token): bool
{
    if ($token === '') {
        return false;
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query(['secret' => $secret, 'response' => $token]),
        'timeout' => 8,
    ]]);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($resp === false) {
        return false;
    }
    $data = json_decode($resp, true);
    return is_array($data) && !empty($data['success']) && ($data['score'] ?? 0.5) >= 0.5;
}

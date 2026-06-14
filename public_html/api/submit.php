<?php
/**
 * Contact form handler.
 *
 * Validates server-side, rejects on honeypot, CSRF failure or rate limit,
 * optionally verifies reCAPTCHA v3, inserts the enquiry via a PDO prepared
 * statement, then sends two emails over Titan SMTP (alert + thank-you).
 *
 * Responds with JSON for AJAX requests, or a redirect back to the form for the
 * no-JavaScript fallback.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

jsd_session_start();

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

/** Send a response in the right shape and stop. */
function respond(bool $ok, string $message, int $code = 200): void
{
    global $isAjax;
    http_response_code($code);
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message]);
    } else {
        $status = $ok ? 'sent' : 'error';
        $url = (string) cfg('SITE_URL') . '/#contact';
        header('Location: ' . $url . '?contact=' . $status);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Method not allowed.', 405);
}

// --- Honeypot: real users never fill this ---
if (trim((string) ($_POST['company'] ?? '')) !== '') {
    // Pretend success so bots get no signal.
    respond(true, 'Thanks. We will be in touch shortly.');
}

// --- CSRF ---
if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    respond(false, 'Your session expired. Please refresh the page and try again.', 419);
}

// --- Rate limit: max 3 submissions per 10 minutes per session ---
$now = time();
$window = 600;
$max = 3;
$hits = $_SESSION['contact_hits'] ?? [];
$hits = array_values(array_filter($hits, static fn ($t) => ($now - $t) < $window));
if (count($hits) >= $max) {
    respond(false, 'Too many attempts. Please wait a few minutes and try again.', 429);
}

// --- Validate input ---
$name  = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$dept  = strtolower(trim((string) ($_POST['department'] ?? 'contact')));
$msg   = trim((string) ($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'a valid name';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
    $errors[] = 'a valid email';
}
if (strlen(preg_replace('/[^0-9]/', '', $phone)) < 6 || mb_strlen($phone) > 40) {
    $errors[] = 'a valid phone number';
}
$mailboxes = (array) cfg('MAILBOXES');
if (!isset($mailboxes[$dept])) {
    $dept = 'contact';
}
if (mb_strlen($msg) > 3000) {
    $msg = mb_substr($msg, 0, 3000);
}

if ($errors) {
    respond(false, 'Please enter ' . implode(', ', $errors) . '.', 422);
}

// --- reCAPTCHA v3 (only if a secret is configured) ---
$secret = trim((string) cfg('RECAPTCHA_SECRET'));
if ($secret !== '') {
    $token = (string) ($_POST['recaptcha_token'] ?? '');
    if (!jsd_verify_recaptcha($secret, $token)) {
        respond(false, 'We could not verify you are human. Please try again.', 422);
    }
}

$deptEmail = $mailboxes[$dept] ?? 'contact@jsdconstruction.com.au';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// --- Persist ---
try {
    $stmt = db()->prepare(
        'INSERT INTO enquiries (name, email, phone, department, message, source_ip)
         VALUES (:name, :email, :phone, :department, :message, :ip)'
    );
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':department' => $dept,
        ':message' => $msg,
        ':ip' => $ip,
    ]);
} catch (Throwable $e) {
    error_log('JSD enquiry insert failed: ' . $e->getMessage());
    respond(false, 'We could not save your enquiry right now. Please call us on 0424 475 767.', 500);
}

// --- Email (failure here should not lose the saved enquiry) ---
$sent = send_enquiry_emails([
    'name' => $name, 'email' => $email, 'phone' => $phone,
    'department' => $dept, 'message' => $msg, 'dept_email' => $deptEmail,
]);

// Record the rate-limit hit only after a successful submission.
$hits[] = $now;
$_SESSION['contact_hits'] = $hits;

respond(true, 'Thanks ' . $name . '. We have received your enquiry and will be in touch shortly.');

/** Verify a reCAPTCHA v3 token. Returns true if score is acceptable. */
function jsd_verify_recaptcha(string $secret, string $token): bool
{
    if ($token === '') {
        return false;
    }
    $postData = http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']);
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData,
        'timeout' => 8,
    ]]);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($resp === false) {
        return false;
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['success'])) {
        return false;
    }
    $score = $data['score'] ?? 0.5;
    return $score >= 0.5;
}

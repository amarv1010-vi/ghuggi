<?php
/**
 * Admin authentication and request guards.
 *
 * Session-based login, bcrypt hashes in admin_users, login rate-limiting and
 * CSRF on every admin POST. Include at the top of every admin page.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/api/db.php';

jsd_session_start();

/** True if at least one admin account exists. */
function admin_exists(): bool
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    } catch (Throwable $e) {
        // No DB yet: treat as not set up so setup can guide the user.
        return false;
    }
}

/** True if the current session is an authenticated admin. */
function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Guard a page: redirect to login if not authenticated. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** Guard a POST endpoint: require login and a valid CSRF token, or 403. */
function require_post_auth(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method not allowed.', 405);
    }
    if (!is_logged_in()) {
        json_fail('Not authenticated.', 401);
    }
    if (!csrf_validate($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
        json_fail('Invalid or expired token. Refresh and try again.', 419);
    }
}

/** Attempt a login. Returns true on success. Applies rate limiting. */
function attempt_login(string $username, string $password): bool
{
    $now = time();
    $window = 900; // 15 min
    $max = 8;
    $hits = $_SESSION['login_hits'] ?? [];
    $hits = array_values(array_filter($hits, static fn ($t) => ($now - $t) < $window));
    if (count($hits) >= $max) {
        $_SESSION['login_locked'] = true;
        return false;
    }

    $ok = false;
    try {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            $ok = true;
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            unset($_SESSION['login_hits']);
        }
    } catch (Throwable $e) {
        $ok = false;
    }

    if (!$ok) {
        $hits[] = $now;
        $_SESSION['login_hits'] = $hits;
    }
    return $ok;
}

/** True if login is currently rate-limited. */
function login_rate_limited(): bool
{
    $now = time();
    $window = 900;
    $hits = array_values(array_filter($_SESSION['login_hits'] ?? [], static fn ($t) => ($now - $t) < $window));
    return count($hits) >= 8;
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** JSON success response and stop. */
function json_ok(array $data = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

/** JSON failure response and stop. */
function json_fail(string $message, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

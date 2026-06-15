<?php
/**
 * PDO database connection.
 *
 * Loads config from jsd_config/config.php (above public_html) via bootstrap.
 * Returns a shared PDO instance. In the build sandbox there is no database, so
 * callers should wrap db() in try/catch and degrade gracefully (the public site
 * renders empty states, never a fatal error).
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Optional full DSN override (used for testing, e.g. sqlite). Production on
    // GoDaddy leaves this empty and uses the MySQL parts below.
    $dsnOverride = (string) cfg('DB_DSN', '');

    if ($dsnOverride !== '') {
        $pdo = new PDO($dsnOverride, (string) cfg('DB_USER', ''), (string) cfg('DB_PASS', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    $host    = cfg('DB_HOST', 'localhost');
    $name    = cfg('DB_NAME', '');
    $user    = cfg('DB_USER', '');
    $pass    = cfg('DB_PASS', '');
    $charset = cfg('DB_CHARSET', 'utf8mb4');

    if ($name === '') {
        throw new RuntimeException('Database is not configured.');
    }

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

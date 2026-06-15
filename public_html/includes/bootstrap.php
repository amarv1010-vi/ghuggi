<?php
/**
 * Shared bootstrap for the public site and admin.
 *
 * Loads jsd_config/config.php from above public_html. In the build sandbox
 * that file does not exist, so we fall back to safe defaults and the site
 * still renders. Secrets are never committed.
 */

if (!defined('JSD_BOOTSTRAP')) {
    define('JSD_BOOTSTRAP', true);

    // Resolve config: /home/<acct>/jsd_config/config.php sits beside public_html.
    $configPath = dirname(__DIR__, 2) . '/jsd_config/config.php';

    $defaults = [
        'APP_ENV'        => 'dev',
        'SITE_URL'       => 'https://jsdconstruction.com.au',
        'MAILBOXES'      => [
            'contact'  => 'contact@jsdconstruction.com.au',
            'info'     => 'info@jsdconstruction.com.au',
            'projects' => 'projects@jsdconstruction.com.au',
            'quotes'   => 'quotes@jsdconstruction.com.au',
            'support'  => 'support@jsdconstruction.com.au',
        ],
        'UPLOAD_MAX_BYTES'   => 8 * 1024 * 1024,
        'UPLOAD_DIR'         => dirname(__DIR__) . '/uploads',
        'RECAPTCHA_SITE_KEY' => '',
        'RECAPTCHA_SECRET'   => '',
        'QBCC_LICENCE'       => '',
        'ABN'                => '',
        'BUSINESS_HOURS'     => '',
    ];

    if (is_file($configPath)) {
        $loaded = require $configPath;
        $GLOBALS['CFG'] = is_array($loaded) ? array_merge($defaults, $loaded) : $defaults;
    } else {
        $GLOBALS['CFG'] = $defaults;
    }
}

/** Read a config value with an optional fallback. */
function cfg(string $key, $default = '')
{
    return $GLOBALS['CFG'][$key] ?? $default;
}

/** Escape for safe HTML output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Business facts, with visible placeholders until the client supplies them. */
function fact_qbcc(): string
{
    $v = trim((string) cfg('QBCC_LICENCE'));
    return $v !== '' ? $v : 'QBCC Licence: pending';
}
function fact_abn(): string
{
    $v = trim((string) cfg('ABN'));
    return $v !== '' ? $v : 'ABN: pending';
}

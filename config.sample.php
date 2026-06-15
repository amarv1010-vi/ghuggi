<?php
/**
 * JSD Construction - configuration template.
 *
 * COPY this file to  jsd_config/config.php  ABOVE public_html on the server.
 * Never commit the real config.php. Never place it inside public_html.
 *
 * db.php resolves this file with:
 *   dirname(__DIR__, 2) . '/jsd_config/config.php'
 * so the layout on the server is:
 *   /home/<acct>/jsd_config/config.php
 *   /home/<acct>/public_html/...
 */

return [
    // dev = write emails to logs/mail.log and never send.
    // prod = send for real over Titan SMTP.
    'APP_ENV' => 'dev',

    // Public site URL, no trailing slash.
    'SITE_URL' => 'https://jsdconstruction.com.au',

    // --- Database (cPanel MySQL) ---
    'DB_HOST' => 'localhost',
    'DB_NAME' => '',
    'DB_USER' => '',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',

    // --- Titan SMTP (sending only, never touches MX) ---
    // Enable third-party app access and disable 2FA on the sending mailbox.
    'SMTP_HOST' => 'smtp.titan.email',
    'SMTP_PORT' => 465,            // 465 SSL or 587 STARTTLS
    'SMTP_SECURE' => 'ssl',        // 'ssl' for 465, 'tls' for 587
    'SMTP_USER' => 'contact@jsdconstruction.com.au',
    'SMTP_PASS' => '',
    'SMTP_FROM' => 'contact@jsdconstruction.com.au',
    'SMTP_FROM_NAME' => 'JSD Construction',

    // Department dropdown maps to these live mailboxes.
    'MAILBOXES' => [
        'contact'  => 'contact@jsdconstruction.com.au',
        'info'     => 'info@jsdconstruction.com.au',
        'projects' => 'projects@jsdconstruction.com.au',
        'quotes'   => 'quotes@jsdconstruction.com.au',
        'support'  => 'support@jsdconstruction.com.au',
    ],

    // --- Uploads ---
    'UPLOAD_MAX_BYTES' => 8 * 1024 * 1024, // 8MB
    'UPLOAD_DIR' => dirname(__DIR__) . '/public_html/uploads',

    // --- reCAPTCHA v3 (leave empty to disable, fill to enable) ---
    'RECAPTCHA_SITE_KEY' => '',
    'RECAPTCHA_SECRET'   => '',

    // --- Business facts (ASK-ME placeholders until supplied) ---
    'QBCC_LICENCE' => '',   // pending
    'ABN'          => '',   // pending
    'BUSINESS_HOURS' => '', // pending
];

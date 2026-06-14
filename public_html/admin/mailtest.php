<?php
/**
 * Temporary SMTP test. Login required. DELETE after use.
 * Usage: /admin/mailtest.php?to=you@example.com
 * Shows the full SMTP conversation so we can see exactly why mail fails.
 */

require_once __DIR__ . '/auth.php';
require_login();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once dirname(__DIR__) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/SMTP.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

echo "=== JSD MAIL TEST ===\n\n";
echo "APP_ENV     : " . cfg('APP_ENV') . "\n";
echo "SMTP_HOST   : " . cfg('SMTP_HOST') . "\n";
echo "SMTP_PORT   : " . cfg('SMTP_PORT') . "\n";
echo "SMTP_SECURE : " . cfg('SMTP_SECURE') . "\n";
echo "SMTP_USER   : " . cfg('SMTP_USER') . "\n";
echo "SMTP_PASS   : " . (cfg('SMTP_PASS') === '' ? "(EMPTY - fill it in config.php!)" : "(set, " . strlen((string)cfg('SMTP_PASS')) . " chars)") . "\n";
echo "SMTP_FROM   : " . cfg('SMTP_FROM') . "\n\n";

if (cfg('SMTP_PASS') === '') {
    echo "STOP: SMTP_PASS is empty in jsd_config/config.php. Add your mailbox password and retry.\n";
    exit;
}

$to = isset($_GET['to']) ? trim((string) $_GET['to']) : (string) cfg('SMTP_USER');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $to = (string) cfg('SMTP_USER');
}
echo "Sending a test email to: $to\n";
echo "------- SMTP conversation -------\n";

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION; // verbose
    $mail->Debugoutput = function ($str, $level) { echo trim($str) . "\n"; };
    $mail->isSMTP();
    $mail->Host = (string) cfg('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = (string) cfg('SMTP_USER');
    $mail->Password = (string) cfg('SMTP_PASS');
    $secure = strtolower((string) cfg('SMTP_SECURE', 'ssl'));
    $mail->SMTPSecure = $secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) cfg('SMTP_PORT', 465);
    $mail->Timeout = 15;
    $mail->setFrom((string) cfg('SMTP_FROM', cfg('SMTP_USER')), 'JSD Construction');
    $mail->addAddress($to);
    $mail->Subject = 'JSD test email ' . date('H:i:s');
    $mail->Body = 'If you can read this, SMTP works.';
    $mail->send();
    echo "\n------- RESULT -------\nSUCCESS: test email sent. Check the inbox for: $to\n";
} catch (Throwable $e) {
    echo "\n------- RESULT -------\nFAILED: " . $mail->ErrorInfo . "\n";
    echo "(" . $e->getMessage() . ")\n";
}
echo "\n=== END. Screenshot this, then DELETE mailtest.php ===\n";

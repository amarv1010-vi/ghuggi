<?php
/**
 * Mailer over Titan SMTP using PHPMailer.
 *
 * APP_ENV aware:
 *   - dev  : write the rendered email to logs/mail.log, never send.
 *   - prod : send for real over Titan SMTP (smtp.titan.email).
 *
 * Logs live above the web root (sibling of public_html) so they are never
 * web-accessible.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once dirname(__DIR__) . '/vendor/PHPMailer/src/Exception.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/PHPMailer.php';
require_once dirname(__DIR__) . '/vendor/PHPMailer/src/SMTP.php';

/** Resolve a writable log directory above the web root. */
function jsd_log_dir(): string
{
    $dir = dirname(__DIR__, 2) . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    return $dir;
}

/**
 * Send one email. In dev, append it to logs/mail.log instead of sending.
 *
 * @param string $to       recipient address
 * @param string $toName   recipient name
 * @param string $subject  subject line
 * @param string $html     HTML body
 * @param string $text     plain-text alternative
 * @param string $replyTo  optional reply-to address
 * @return bool true on success
 */
function send_mail(string $to, string $toName, string $subject, string $html, string $text, string $replyTo = '', string $replyToName = ''): bool
{
    $env = strtolower((string) cfg('APP_ENV', 'dev'));

    if ($env !== 'prod') {
        $log = jsd_log_dir() . '/mail.log';
        $entry = sprintf(
            "[%s] DEV MAIL\nTo: %s <%s>\nReply-To: %s\nSubject: %s\n--- text ---\n%s\n--- end ---\n\n",
            date('c'), $toName, $to, $replyTo, $subject, $text
        );
        return (bool) @file_put_contents($log, $entry, FILE_APPEND | LOCK_EX);
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = (string) cfg('SMTP_HOST', 'smtp.titan.email');
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) cfg('SMTP_USER', '');
        $mail->Password   = (string) cfg('SMTP_PASS', '');
        $secure           = strtolower((string) cfg('SMTP_SECURE', 'ssl'));
        $mail->SMTPSecure = $secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int) cfg('SMTP_PORT', 465);
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom((string) cfg('SMTP_FROM', cfg('SMTP_USER')), (string) cfg('SMTP_FROM_NAME', 'JSD Construction'));
        $mail->addAddress($to, $toName);
        if ($replyTo !== '') {
            $mail->addReplyTo($replyTo, $replyToName);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        @file_put_contents(jsd_log_dir() . '/mail-error.log', date('c') . ' ' . $mail->ErrorInfo . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

/**
 * Send the two enquiry emails: an alert to the chosen JSD mailbox and a
 * branded thank-you to the customer.
 *
 * @param array $d validated enquiry data (name, email, phone, department, message, dept_email)
 * @return array{alert:bool, customer:bool}
 */
function send_enquiry_emails(array $d): array
{
    $name  = $d['name'];
    $email = $d['email'];
    $phone = $d['phone'];
    $dept  = $d['department'];
    $msg   = $d['message'] !== '' ? $d['message'] : '(no message provided)';
    $deptEmail = $d['dept_email'];

    // --- Alert to JSD ---
    $alertSubject = "New website enquiry from {$name}";
    $alertText = "New enquiry via jsdconstruction.com.au\n\n"
        . "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nDepartment: {$dept}\n\nMessage:\n{$msg}\n";
    $alertHtml = jsd_email_shell(
        'New website enquiry',
        '<p>A new enquiry has come through the website.</p>'
        . jsd_kv_table([
            'Name' => $name,
            'Email' => '<a href="mailto:' . e($email) . '">' . e($email) . '</a>',
            'Phone' => '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $phone)) . '">' . e($phone) . '</a>',
            'Department' => e($dept),
        ])
        . '<p style="margin-top:18px"><strong>Message</strong></p><p>' . nl2br(e($msg)) . '</p>'
    );
    $alertOk = send_mail($deptEmail, 'JSD Construction', $alertSubject, $alertHtml, $alertText, $email, $name);

    // --- Thank-you to customer ---
    $thanksSubject = 'Thanks for contacting JSD Construction';
    $thanksText = "Hi {$name},\n\nThanks for getting in touch with JSD Construction. "
        . "We have received your enquiry and a member of our team will be in contact shortly.\n\n"
        . "For anything urgent call us on +61 424 475 767 or message us on WhatsApp.\n\n"
        . "JSD Construction Pty Ltd\nLuxury Built in Australia\n";
    $thanksHtml = jsd_email_shell(
        'Thanks for getting in touch',
        '<p>Hi ' . e($name) . ',</p>'
        . '<p>Thanks for contacting JSD Construction. We have received your enquiry and a member of our team will be in touch shortly.</p>'
        . '<p>For anything urgent call us on <a href="tel:+61424475767">+61 424 475 767</a> or message us on '
        . '<a href="https://wa.me/61424475767">WhatsApp</a>.</p>'
        . '<p style="margin-top:18px;color:#9F7745">JSD Construction Pty Ltd<br>Luxury Built in Australia</p>'
    );
    $customerOk = send_mail($email, $name, $thanksSubject, $thanksHtml, $thanksText, $deptEmail, 'JSD Construction');

    return ['alert' => $alertOk, 'customer' => $customerOk];
}

/** Minimal branded HTML email shell. */
function jsd_email_shell(string $heading, string $bodyHtml): string
{
    return '<!doctype html><html><body style="margin:0;background:#16130F;font-family:Arial,Helvetica,sans-serif;color:#F7F3EC">'
        . '<div style="max-width:600px;margin:0 auto;padding:28px">'
        . '<div style="font-size:22px;letter-spacing:1px;color:#CFA168;font-weight:bold">JSD CONSTRUCTION</div>'
        . '<div style="height:3px;width:60px;background:#CFA168;margin:14px 0 22px"></div>'
        . '<h1 style="font-size:20px;color:#E0B171;margin:0 0 14px">' . e($heading) . '</h1>'
        . '<div style="font-size:15px;line-height:1.6;color:#F7F3EC">' . $bodyHtml . '</div>'
        . '<div style="margin-top:26px;padding-top:16px;border-top:1px solid #3a3128;font-size:12px;color:#9F7745">'
        . 'JSD Construction Pty Ltd, Brisbane, Australia</div>'
        . '</div></body></html>';
}

/** Render a simple key/value table for emails. */
function jsd_kv_table(array $rows): string
{
    $html = '<table style="width:100%;border-collapse:collapse;font-size:15px">';
    foreach ($rows as $k => $v) {
        $html .= '<tr>'
            . '<td style="padding:6px 10px 6px 0;color:#9F7745;white-space:nowrap;vertical-align:top">' . e($k) . '</td>'
            . '<td style="padding:6px 0;color:#F7F3EC">' . $v . '</td>'
            . '</tr>';
    }
    return $html . '</table>';
}

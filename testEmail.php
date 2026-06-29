<?php
define('ADMIN_HASH',   '63b38ded3ce608f47342f48fe9ac1639');
define('COOKIE_TOKEN', hash('sha256', ADMIN_HASH . 'uru_admin_salt'));
if (!isset($_COOKIE['uru_admin']) || $_COOKIE['uru_admin'] !== COOKIE_TOKEN) {
    http_response_code(403); die('Unauthorized');
}

$to      = 'tloftus@gmail.com';
$subject = 'URU Soccer — Email Test ' . date('Y-m-d H:i:s');
$body    = "This is a test email from URU Soccer.\n\nSent: " . date('Y-m-d H:i:s') . "\nServer: " . ($_SERVER['SERVER_NAME'] ?? 'unknown');
$headers = "From: URU Soccer <noreply@uru.soccer>\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($to, $subject, $body, $headers);

echo '<pre>';
echo "mail() returned: " . ($sent ? 'TRUE' : 'FALSE') . "\n\n";
echo "PHP mail config:\n";
echo "  sendmail_path = " . ini_get('sendmail_path') . "\n";
echo "  SMTP          = " . ini_get('SMTP') . "\n";
echo "  smtp_port     = " . ini_get('smtp_port') . "\n";
echo "  sendmail_from = " . ini_get('sendmail_from') . "\n\n";
echo "error_get_last():\n";
print_r(error_get_last());
echo '</pre>';

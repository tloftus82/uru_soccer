<?php
define('ADMIN_HASH', '63b38ded3ce608f47342f48fe9ac1639');
$token   = hash('sha256', ADMIN_HASH . 'uru_admin_salt');
$isAdmin = ($_COOKIE['uru_admin'] ?? '') === $token;

if (!$isAdmin) {
    http_response_code(403);
    exit('403 Forbidden');
}

phpinfo();

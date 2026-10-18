<?php
/**
 * Administrator Logout
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

AuthService::adminLogout();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;

<?php
require_once __DIR__ . '/backend/admin_auth.php';

storage_admin_logout();

header('Location: admin-login.php');
exit;

<?php
require_once __DIR__ . '/includes/auth.php';
header('Location: ' . auth_url('signup.php'));
exit;

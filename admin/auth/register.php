<?php
require_once dirname(__DIR__) . '/includes/auth.php';
header('Location: ' . auth_url('signup.php'));
exit;

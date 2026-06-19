<?php
require_once 'includes/session.php';
startSession();
session_unset();
session_destroy();
header('Location: /login.php?logout=1');
exit;

<?php
require_once 'db.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
echo "<script>window.location.href = 'login.php';</script>";
exit;
?>

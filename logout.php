<?php
require_once 'config.php';

if (isLoggedIn()) {
    // Log activity
    logSystem('INFO', "User '{$_SESSION['username']}' logged out.", $_SESSION['user_id'], $_SESSION['username']);
    
    // Destroy session
    session_destroy();
}

// Redirect to login page
redirectTo('login.php');
?>
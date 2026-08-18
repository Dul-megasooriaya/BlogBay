<?php
include "config.php";
include "includes/session_manager.php";

// Check session
checkRememberMeCookie($conn);

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
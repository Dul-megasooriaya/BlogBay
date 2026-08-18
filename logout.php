<?php

include "config.php";
include "includes/session_manager.php";

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
clearRememberMeCookie($conn, $userId);

session_unset();
session_destroy();

header("Location: login.php");
exit();

?>
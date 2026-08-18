<?php
include "config.php";
include "includes/session_manager.php";

// Check session
checkRememberMeCookie($conn);

if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int) $_GET['id'];
$user = (int) $_SESSION['user_id'];

// Remove image file
$res = mysqli_query($conn, "SELECT image FROM blogpost WHERE id = $id AND user_id = $user LIMIT 1");
if ($res && $row = mysqli_fetch_assoc($res)) {
    if (!empty($row['image']) && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
        unlink(__DIR__ . '/uploads/' . $row['image']);
    }
}

// Delete post
mysqli_query($conn, "DELETE FROM blogpost WHERE id = $id AND user_id = $user");

header("Location: dashboard.php");
exit();
?>
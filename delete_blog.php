<?php

session_start();

include "config.php";

$id=$_GET['id'];

$user=$_SESSION['user_id'];

mysqli_query($conn,

"DELETE FROM blogPost
WHERE id='$id'
AND user_id='$user'");

header("Location:dashboard.php");

?>
<?php
include "config.php";

$userRes = mysqli_query($conn, "SELECT id FROM user ORDER BY id ASC LIMIT 1");
$userData = mysqli_fetch_assoc($userRes);
$userID = $userData ? (int)$userData['id'] : 1;

$blog1_title = "Mastering Daily Productivity & Mindful Habits";
$blog1_content = "Finding balance in a busy day isn't about cramming more tasks into your calendar—it's about focusing on what truly matters. Small, consistent steps like taking brief morning walks, prioritizing your top three goals, and taking structured breaks can transform your daily energy and focus. Start small, stay consistent, and give yourself space to recharge.";
$blog1_img = "blog_6a5787070e2a39.74148651.jpg";

$blog2_title = "The Art of Crafting Clean & Maintainable Code";
$blog2_content = "Writing code is easy, but writing clean code that your future self and teammates will appreciate is an art. Focus on naming variables clearly, keeping functions short and focused, and leaving meaningful documentation. Good architecture saves countless hours of debugging down the road and makes building new features a joyful process.";
$blog2_img = "blog_6a57874f26b939.61926669.jpg";

$t1 = mysqli_real_escape_string($conn, $blog1_title);
$c1 = mysqli_real_escape_string($conn, $blog1_content);
$i1 = mysqli_real_escape_string($conn, $blog1_img);

$t2 = mysqli_real_escape_string($conn, $blog2_title);
$c2 = mysqli_real_escape_string($conn, $blog2_content);
$i2 = mysqli_real_escape_string($conn, $blog2_img);

$sql1 = "INSERT INTO blogpost (user_id, title, content, image) VALUES ($userID, '$t1', '$c1', '$i1')";
$sql2 = "INSERT INTO blogpost (user_id, title, content, image) VALUES ($userID, '$t2', '$c2', '$i2')";

if (mysqli_query($conn, $sql1) && mysqli_query($conn, $sql2)) {
    echo "SUCCESS: 2 blogs inserted successfully!";
} else {
    echo "ERROR: " . mysqli_error($conn);
}
?>

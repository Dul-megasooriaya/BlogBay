<?php
session_start();

if(isset($_SESSION['user_id']))
{
    header("Location: dashboard.php");
    exit();
}

include "config.php";
include "site_config.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo htmlspecialchars($siteName); ?></title>

<link rel="stylesheet" href="css/landing.css">

</head>

<body>

<!-- Navigation -->

<nav>

    <a href="index.php" class="logo">
        <img src="images/logo.png" alt="BlogBay logo">
        <?php echo renderSiteName(); ?>
    </a>

    <div class="nav-links">

        <a href="#latest">Blogs</a>

        <a href="login.php">Login</a>

        <a href="register.php" class="register">Register</a>

    </div>

</nav>

<!-- Hero Section -->

<section class="hero">

    <h1>Share Your Stories With The World</h1>

    <p>
        A modern blogging platform built using PHP, MySQL, HTML, CSS and JavaScript.
    </p>

    <a href="register.php">Get Started</a>

</section>

<!-- Latest Blogs -->

<section class="latest" id="latest">

<h2>Latest Blogs</h2>

<div class="blog-grid">

<?php

$sql = "SELECT blogpost.*, user.username
        FROM blogpost
        JOIN user
        ON blogpost.user_id = user.id
        ORDER BY created_at DESC";

$result = mysqli_query($conn,$sql);

if(mysqli_num_rows($result)>0)
{

while($row=mysqli_fetch_assoc($result))
{

?>

<div class="blog-card">

<h3><?php echo htmlspecialchars($row['title']); ?></h3>

<p>

<?php

echo substr(strip_tags($row['content']),0,120);

?>

...

</p>

<div class="author">

By <b><?php echo htmlspecialchars($row['username']); ?></b>

</div>

<a href="view_blog.php?id=<?php echo $row['id']; ?>">

Read More →

</a>

</div>

<?php

}

}
else
{

?>

<div class="blog-card">

<h3>No Blogs Yet</h3>

<p>

No blogs have been published yet.

Be the first one to write!

</p>

</div>

<?php

}

?>

</div>

</section>

<footer>

<h2><?php echo renderSiteName(); ?></h2>

<p>

Share Ideas. Inspire People.

</p>

<hr>

<p>

© 2026 <?php echo htmlspecialchars($siteName); ?> | University of Moratuwa

</p>

</footer>

</body>

</html>
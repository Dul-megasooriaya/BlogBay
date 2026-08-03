<?php

session_start();

include "config.php";
include "site_config.php";

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: login.php");
    exit();
}

$blogID = (int) $_GET['id'];

$sql = "SELECT blogpost.*, user.username, p.profile_pic
        FROM blogpost
        JOIN user ON blogpost.user_id = user.id
        LEFT JOIN user_profiles p ON user.id = p.user_id
        WHERE blogpost.id = $blogID
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) !== 1)
{
    header("Location: login.php");
    exit();
}

$blog = mysqli_fetch_assoc($result);

$isOwner =
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] === (int) $blog['user_id'];

$backPage =
    isset($_SESSION['user_id'])
        ? "dashboard.php"
        : "login.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    <?php echo htmlspecialchars($blog['title']); ?>
    | <?php echo htmlspecialchars($siteName); ?>
</title>

<link rel="stylesheet"
href="css/view_blog.css?v=10">

<link rel="stylesheet"
href="css/footer.css?v=3">

</head>

<body>

<header class="hero-navbar">
    <div class="hero-navbar-inner">
        <a href="dashboard.php" class="hero-brand">
            <img src="images/logo.png" alt="Logo">
            <?php echo renderSiteName(); ?>
        </a>
        <nav class="hero-nav-links">
            <a href="dashboard.php" class="hero-nav-item">Dashboard</a>
            <a href="dashboard.php#blogGrid" class="hero-nav-item">Blogs</a>
            <a href="reviews.php" class="hero-nav-item">Review</a>
            <a href="profile.php" class="hero-nav-item">Profile</a>
        </nav>
        <div class="hero-search-box" style="position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; border-radius: 999px !important; padding: 6px 16px !important; width: 210px !important;">
            <i class="fa-solid fa-magnifying-glass" style="color: rgba(255, 255, 255, 0.8) !important; margin-right: 8px !important; font-size: 13px !important;"></i>
            <input id="blogSearch" type="text" placeholder="Search..." style="background: transparent !important; border: none !important; outline: none !important; color: #ffffff !important; font-size: 13px !important; width: 100% !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; height: auto !important;" onkeydown="if(event.key==='Enter'){ window.location.href='dashboard.php?search='+encodeURIComponent(this.value); }">
        </div>
    </div>
</header>

<main class="article-wrapper">

    <a href="<?php echo $backPage; ?>"
       class="back-link">

        ← Back to blogs

    </a>

    <article class="article-card">

        <header class="article-header">

            <h1>
                <?php echo htmlspecialchars($blog['title']); ?>
            </h1>

            <div class="author-row">

                <?php echo renderUserAvatar($blog['username'], $blog['profile_pic'] ?? null, 'author-avatar'); ?>

                <div class="author-details">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $blog['username']
                        );
                        ?>
                    </strong>

                    <span>
                        Published on
                        <?php
                        echo date(
                            "F d, Y",
                            strtotime($blog['created_at'])
                        );
                        ?>
                    </span>

                    <?php
                    if(
                        !empty($blog['updated_at']) &&
                        $blog['updated_at'] !== $blog['created_at']
                    )
                    {
                    ?>

                        <span>
                            Updated on
                            <?php
                            echo date(
                                "F d, Y",
                                strtotime($blog['updated_at'])
                            );
                            ?>
                        </span>

                    <?php } ?>

                </div>

            </div>

        </header>

        <?php if(!empty($blog['image'])) { ?>

            <div class="cover-wrapper">

                <img
                    src="uploads/<?php
                    echo htmlspecialchars($blog['image']);
                    ?>"
                    alt="<?php
                    echo htmlspecialchars($blog['title']);
                    ?>"
                    class="cover-image"
                >

            </div>

        <?php } ?>

        <section class="article-content">

            <?php
            echo nl2br(
                htmlspecialchars($blog['content'])
            );
            ?>

        </section>

        <?php if($isOwner) { ?>

            <div class="article-actions">

                <a
                    href="edit_blog.php?id=<?php
                    echo (int) $blog['id'];
                    ?>"
                    class="edit-btn">

                    Edit Blog

                </a>

                <a
                    href="delete_blog.php?id=<?php
                    echo (int) $blog['id'];
                    ?>"
                    class="delete-btn"
                    onclick="return confirm('Are you sure you want to delete this blog?')">

                    Delete Blog

                </a>

            </div>

        <?php } ?>

    </article>

    <section class="more-section">

        <div>

            <p>KEEP READING</p>

            <h2>
                Discover more stories
            </h2>

            <span>
                Return to the blog list and explore other articles.
            </span>

        </div>

        <a href="<?php echo $backPage; ?>">
            Browse Blogs
        </a>

    </section>

</main>

<?php include "includes/footer.php"; ?>

</body>

</html>
<?php

session_start();

include "config.php";
include "site_config.php";

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: index.php");
    exit();
}

$blogID = (int) $_GET['id'];

$sql = "SELECT blogpost.*, user.username
        FROM blogpost
        JOIN user
        ON blogpost.user_id = user.id
        WHERE blogpost.id = $blogID
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) !== 1)
{
    header("Location: index.php");
    exit();
}

$blog = mysqli_fetch_assoc($result);

$isOwner =
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] === (int) $blog['user_id'];

$backPage =
    isset($_SESSION['user_id'])
        ? "dashboard.php"
        : "index.php";

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

<header class="topbar">

    <a href="<?php echo $backPage; ?>"
       class="brand">

        <img src="images/logo.png" alt="BlogBay logo">
        <?php echo renderSiteName(); ?>

    </a>

    <nav class="top-navigation">

        <a href="<?php echo $backPage; ?>">
            Back to Blogs
        </a>

        <?php if(isset($_SESSION['user_id'])) { ?>

            <a href="profile.php">
                My Profile
            </a>

        <?php } else { ?>

            <a href="login.php">
                Sign In
            </a>

        <?php } ?>

    </nav>

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

                <div class="author-avatar">

                    <?php
                    echo strtoupper(
                        substr($blog['username'], 0, 1)
                    );
                    ?>

                </div>

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
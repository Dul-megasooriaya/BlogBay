<?php

session_start();

include "config.php";
include "site_config.php";

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

$userID = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];

$totalResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM blogpost"
);

$totalData = mysqli_fetch_assoc($totalResult);
$totalBlogs = (int) $totalData['total'];

$myResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM blogpost
     WHERE user_id = $userID"
);

$myData = mysqli_fetch_assoc($myResult);
$myBlogs = (int) $myData['total'];

$blogsPerPage = 6;

$page = isset($_GET['page']) && is_numeric($_GET['page'])
    ? (int) $_GET['page']
    : 1;

if($page < 1)
{
    $page = 1;
}

$totalPages = (int) ceil(
    $totalBlogs / $blogsPerPage
);

if($totalPages > 0 && $page > $totalPages)
{
    $page = $totalPages;
}

$offset = ($page - 1) * $blogsPerPage;

$sql = "SELECT blogpost.*, user.username
        FROM blogpost
        JOIN user
        ON blogpost.user_id = user.id
        ORDER BY blogpost.created_at DESC
        LIMIT $blogsPerPage OFFSET $offset";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Dashboard | <?php echo htmlspecialchars($siteName); ?>
</title>

<link rel="stylesheet"
href="css/dashboard_v2.css?v=25">

<link rel="stylesheet"
href="css/footer.css?v=4">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<header class="navbar">

    <a href="dashboard.php"
       class="logo-link">

        <img
            src="images/logo.png"
            alt="<?php echo htmlspecialchars($siteName); ?> logo"
        >

        <?php echo renderSiteName(); ?>

    </a>

    <div class="search-wrapper">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            id="blogSearch"
            type="text"
            placeholder="Search blogs..."
        >

    </div>

    <a href="profile.php"
       class="top-profile">

        <div class="avatar">

            <?php
            echo strtoupper(
                substr($username, 0, 1)
            );
            ?>

        </div>

        <div class="profile-text">

            <span>Signed in as</span>

            <strong>
                <?php echo htmlspecialchars($username); ?>
            </strong>

        </div>

    </a>

</header>

<aside class="sidebar">

    <nav>

        <a href="dashboard.php"
           class="active">

            Dashboard

        </a>

        <a href="profile.php">

            My Profile

        </a>

    </nav>

</aside>

<main class="main">

    <?php if($page === 1) { ?>

        <section class="dashboard-slider">

            <article class="dashboard-slide slide-one active">

                <div class="slide-overlay"></div>

                <div class="slide-content">

                    <h1>
                        Publish your thoughts,
                        your way.
                    </h1>

                    <p>
                        Create personal stories, useful articles
                        and ideas that reflect your voice.
                    </p>

                    <a href="create_blog.php"
                       class="slide-button">

                        Start writing

                    </a>

                </div>

            </article>

            <article class="dashboard-slide slide-two">

                <div class="slide-overlay"></div>

                <div class="slide-content">

                    <h1>
                        Every story becomes part
                        of your journey.
                    </h1>

                    <p>
                        Keep your writing organized and manage
                        your published stories from one place.
                    </p>

                    <a href="profile.php"
                       class="slide-button">

                        View profile

                    </a>

                </div>

            </article>

            <article class="dashboard-slide slide-three">

                <div class="slide-overlay"></div>

                <div class="slide-content">

                    <h1>
                        Give your ideas a place
                        to be discovered.
                    </h1>

                    <p>
                        Readers can open any story by selecting
                        its blog card.
                    </p>

                    <a href="#blogGrid"
                       class="slide-button">

                        Browse stories

                    </a>

                </div>

            </article>

            <div class="slider-controls">

                <button
                    class="slider-dot active"
                    type="button"
                    data-index="0">
                </button>

                <button
                    class="slider-dot"
                    type="button"
                    data-index="1">
                </button>

                <button
                    class="slider-dot"
                    type="button"
                    data-index="2">
                </button>

            </div>

        </section>

        <section class="summary-row">

            <article class="summary-card summary-cyan">

                <p>Total published</p>

                <strong>
                    <?php echo $totalBlogs; ?>
                </strong>

                <span>
                    Stories on the platform
                </span>

            </article>

            <article class="summary-card summary-purple">

                <p>Your stories</p>

                <strong>
                    <?php echo $myBlogs; ?>
                </strong>

                <span>
                    Blogs written by you
                </span>

            </article>

            <article class="summary-card summary-magenta">

                <p>Current page</p>

                <strong>
                    <?php echo $page; ?>
                </strong>

                <span>
                    Six stories per page
                </span>

            </article>

        </section>

    <?php } ?>

    <section class="blog-heading">

        <div>

            <h2>
                <?php
                echo $page === 1
                    ? "Explore recent blogs"
                    : "More blogs";
                ?>
            </h2>

        </div>

        <span>
            Click a card to read
        </span>

    </section>

    <section class="blogs"
             id="blogGrid">

        <?php

        if($result && mysqli_num_rows($result) > 0)
        {
            while($row = mysqli_fetch_assoc($result))
            {
                $blogURL =
                    "view_blog.php?id=" . (int) $row['id'];

                $preview = trim(
                    strip_tags($row['content'])
                );
        ?>

            <article
                class="blog-card"
                tabindex="0"
                data-title="<?php
                echo htmlspecialchars(
                    strtolower($row['title'])
                );
                ?>"
                onclick="openBlog('<?php echo $blogURL; ?>')"
                onkeydown="openBlogKeyboard(event, '<?php echo $blogURL; ?>')"
            >

                <div class="blog-image">

                    <?php if(!empty($row['image'])) { ?>

                        <img
                            src="uploads/<?php
                            echo htmlspecialchars(
                                $row['image']
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $row['title']
                            );
                            ?>"
                        >

                    <?php } else { ?>

                        <div class="image-placeholder">

                            <?php
                            echo strtoupper(
                                substr($row['title'], 0, 1)
                            );
                            ?>

                        </div>

                    <?php } ?>

                </div>

                <div class="blog-card-content">

                    <div class="blog-meta">

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $row['username']
                            );
                            ?>
                        </span>

                        <span>
                            <?php
                            echo date(
                                "M d, Y",
                                strtotime($row['created_at'])
                            );
                            ?>
                        </span>

                    </div>

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $row['title']
                        );
                        ?>
                    </h3>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            substr($preview, 0, 95)
                        );

                        if(strlen($preview) > 95)
                        {
                            echo "...";
                        }
                        ?>

                    </p>

                    <?php
                    if((int) $row['user_id'] === $userID)
                    {
                    ?>

                        <div class="blog-actions">

                            <a
                                href="edit_blog.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                class="edit-button"
                                onclick="event.stopPropagation();">

                                Edit

                            </a>

                            <a
                                href="delete_blog.php?id=<?php
                                echo (int) $row['id'];
                                ?>"
                                class="delete-button"
                                onclick="
                                    event.stopPropagation();
                                    return confirm(
                                        'Are you sure you want to delete this blog?'
                                    );
                                ">

                                Delete

                            </a>

                        </div>

                    <?php } ?>

                </div>

            </article>

        <?php
            }
        }
        else
        {
        ?>

            <div class="empty-state">

                <h2>No blogs found</h2>

            </div>

        <?php } ?>

    </section>

    <nav class="pagination">

        <?php if($page > 1) { ?>

            <a href="dashboard.php?page=<?php echo $page - 1; ?>">
                ‹
            </a>

        <?php } ?>

        <?php for($i = 1; $i <= max(1, $totalPages); $i++) { ?>

            <a
                href="dashboard.php?page=<?php echo $i; ?>"
                class="<?php
                echo $i === $page
                    ? 'active-page'
                    : '';
                ?>">

                <?php echo $i; ?>

            </a>

        <?php } ?>

        <?php if($page < $totalPages) { ?>

            <a href="dashboard.php?page=<?php echo $page + 1; ?>">
                ›
            </a>

        <?php } ?>

    </nav>

</main>

<a href="create_blog.php"
   class="floating-add"
   aria-label="Create blog">

    +

</a>

<?php include "includes/footer.php"; ?>

<script>

function openBlog(url)
{
    window.location.href = url;
}

function openBlogKeyboard(event, url)
{
    if(event.key === "Enter" || event.key === " ")
    {
        event.preventDefault();
        window.location.href = url;
    }
}

const searchInput =
    document.getElementById("blogSearch");

const blogCards =
    document.querySelectorAll(".blog-card");

searchInput.addEventListener(
    "input",
    function()
    {
        const value =
            this.value.toLowerCase().trim();

        blogCards.forEach(
            function(card)
            {
                const title = card.dataset.title;

                card.style.display =
                    title.includes(value)
                        ? ""
                        : "none";
            }
        );
    }
);

const slides =
    document.querySelectorAll(".dashboard-slide");

const dots =
    document.querySelectorAll(".slider-dot");

if(slides.length > 0)
{
    let currentSlide = 0;

    function showSlide(index)
    {
        slides.forEach(function(slide)
        {
            slide.classList.remove("active");
        });

        dots.forEach(function(dot)
        {
            dot.classList.remove("active");
        });

        slides[index].classList.add("active");
        dots[index].classList.add("active");

        currentSlide = index;
    }

    dots.forEach(function(dot)
    {
        dot.addEventListener(
            "click",
            function()
            {
                showSlide(
                    Number(this.dataset.index)
                );
            }
        );
    });

    setInterval(function()
    {
        const next =
            (currentSlide + 1) % slides.length;

        showSlide(next);

    }, 5000);
}

</script>

</body>

</html>
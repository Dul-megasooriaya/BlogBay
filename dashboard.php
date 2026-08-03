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

// Search Query Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
if ($search !== "") {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $whereClause = " WHERE (blogpost.title LIKE '%$safeSearch%' OR blogpost.content LIKE '%$safeSearch%' OR user.username LIKE '%$safeSearch%') ";
}

$countSql = "SELECT COUNT(*) AS total FROM blogpost JOIN user ON blogpost.user_id = user.id $whereClause";
$totalResult = mysqli_query($conn, $countSql);

$totalData = mysqli_fetch_assoc($totalResult);
$totalBlogs = (int) ($totalData['total'] ?? 0);

$myResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM blogpost
     WHERE user_id = $userID"
);

$myData = mysqli_fetch_assoc($myResult);
$myBlogs = (int) ($myData['total'] ?? 0);

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
        JOIN user ON blogpost.user_id = user.id
        $whereClause
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
href="css/dashboard_v2.css?v=99999">

<link rel="stylesheet"
href="css/footer.css?v=4">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<header class="hero-navbar <?php echo ($page > 1 || !empty($search)) ? 'hero-navbar-solid' : ''; ?>">

    <div class="hero-navbar-inner">

        <!-- Left: Logo & Brand Name -->
        <a href="dashboard.php" class="hero-brand">
            <img src="images/logo.png" alt="<?php echo htmlspecialchars($siteName); ?> logo">
            <?php echo renderSiteName(); ?>
        </a>

        <!-- Center: Navigation Items -->
        <nav class="hero-nav-links">
            <a href="dashboard.php" class="hero-nav-item <?php echo ($page === 1 && empty($search)) ? 'active' : ''; ?>">
                Dashboard
            </a>
            <a href="dashboard.php#blogGrid" class="hero-nav-item <?php echo ($page > 1 || !empty($search)) ? 'active' : ''; ?>">
                Blogs
            </a>
            <a href="reviews.php" class="hero-nav-item">
                Review
            </a>
            <a href="profile.php" class="hero-nav-item">
                Profile
            </a>
        </nav>

        <!-- Right: Search Box -->
        <div class="hero-search-box" style="position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; border-radius: 999px !important; padding: 6px 16px !important; width: 210px !important;">
            <i class="fa-solid fa-magnifying-glass" style="color: rgba(255, 255, 255, 0.8) !important; margin-right: 8px !important; font-size: 13px !important;"></i>
            <input id="blogSearch" type="text" placeholder="Search..." style="background: transparent !important; border: none !important; outline: none !important; color: #ffffff !important; font-size: 13px !important; width: 100% !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; height: auto !important;" value="<?php echo htmlspecialchars($search); ?>" onkeydown="if(event.key==='Enter'){ window.location.href='dashboard.php?search='+encodeURIComponent(this.value); }">
        </div>

    </div>

</header>

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
                if (!empty($search)) {
                    echo "Search results for: \"" . htmlspecialchars($search) . "\"";
                } else {
                    echo $page === 1 ? "Explore recent blogs" : "More blogs";
                }
                ?>
            </h2>

        </div>

        <span>
            Click a card to read
        </span>

    </section>

    <div class="dashboard-content-layout">

        <!-- Left Sidebar Column: Blogger Video Guides -->
        <aside class="video-guides-sidebar">
            <div class="sidebar-header">
                <h3>Blogger Guides</h3>
                <span>How-to Tutorials</span>
            </div>

            <div class="sidebar-guide-cards">

                <!-- Card 1 -->
                <article class="guide-video-card">
                    <div class="guide-video-wrapper">
                        <span class="guide-badge-pill">GUIDE</span>
                        <?php if (file_exists(__DIR__ . "/videos/guide_1.mp4")) { ?>
                            <video controls playsinline poster="images/guide1_cover.jpg">
                                <source src="videos/guide_1.mp4" type="video/mp4">
                            </video>
                        <?php } else { ?>
                            <div class="guide-video-placeholder" onclick="openTutorialVideo('How to Write Compelling Blog Articles', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4')">
                                <i class="fa-solid fa-circle-play video-play-btn"></i>
                                <span style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.9);">How to Write Articles</span>
                                <small style="font-size:10px; color:rgba(255,255,255,0.6); margin-top:4px;">Click to watch tutorial</small>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="guide-card-body">
                        <h4 class="guide-card-title">How to Write Compelling Blog Articles</h4>
                        <p class="guide-card-desc">Master essential techniques for structuring your posts, crafting catchy headlines, and engaging readers from the first sentence.</p>
                        <div class="guide-card-footer">
                            <i class="fa-regular fa-clock"></i> Aug 02, 2026 • 5 min watch
                        </div>
                    </div>
                </article>

                <!-- Card 2 -->
                <article class="guide-video-card">
                    <div class="guide-video-wrapper">
                        <span class="guide-badge-pill">TUTORIAL</span>
                        <?php if (file_exists(__DIR__ . "/videos/guide_2.mp4")) { ?>
                            <video controls playsinline poster="images/guide2_cover.jpg">
                                <source src="videos/guide_2.mp4" type="video/mp4">
                            </video>
                        <?php } else { ?>
                            <div class="guide-video-placeholder" onclick="openTutorialVideo('Formatting & Media Best Practices', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4')">
                                <i class="fa-solid fa-circle-play video-play-btn" style="color:var(--purple);"></i>
                                <span style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.9);">Formatting & Media</span>
                                <small style="font-size:10px; color:rgba(255,255,255,0.6); margin-top:4px;">Click to watch tutorial</small>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="guide-card-body">
                        <h4 class="guide-card-title">Formatting & Media Best Practices</h4>
                        <p class="guide-card-desc">Learn how to effectively use headings, vibrant images, and video embeds to make your content visually appealing.</p>
                        <div class="guide-card-footer">
                            <i class="fa-regular fa-clock"></i> Aug 02, 2026 • 7 min watch
                        </div>
                    </div>
                </article>

                <!-- Card 3 -->
                <article class="guide-video-card">
                    <div class="guide-video-wrapper">
                        <span class="guide-badge-pill">SEO TIPS</span>
                        <?php if (file_exists(__DIR__ . "/videos/guide_3.mp4")) { ?>
                            <video controls playsinline poster="images/guide3_cover.jpg">
                                <source src="videos/guide_3.mp4" type="video/mp4">
                            </video>
                        <?php } else { ?>
                            <div class="guide-video-placeholder" onclick="openTutorialVideo('SEO Basics for Content Authors', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4')">
                                <i class="fa-solid fa-circle-play video-play-btn"></i>
                                <span style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.9);">SEO Basics for Authors</span>
                                <small style="font-size:10px; color:rgba(255,255,255,0.6); margin-top:4px;">Click to watch tutorial</small>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="guide-card-body">
                        <h4 class="guide-card-title">SEO Basics for Content Authors</h4>
                        <p class="guide-card-desc">Understand keyword optimization, meta descriptions, and clean link structures to rank your blogs higher on Google.</p>
                        <div class="guide-card-footer">
                            <i class="fa-regular fa-clock"></i> Aug 02, 2026 • 6 min watch
                        </div>
                    </div>
                </article>

                <!-- Card 4 -->
                <article class="guide-video-card">
                    <div class="guide-video-wrapper">
                        <span class="guide-badge-pill">STRATEGY</span>
                        <?php if (file_exists(__DIR__ . "/videos/guide_4.mp4")) { ?>
                            <video controls playsinline poster="images/guide4_cover.jpg">
                                <source src="videos/guide_4.mp4" type="video/mp4">
                            </video>
                        <?php } else { ?>
                            <div class="guide-video-placeholder" onclick="openTutorialVideo('Building & Engaging Your Audience', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4')">
                                <i class="fa-solid fa-circle-play video-play-btn" style="color:var(--magenta);"></i>
                                <span style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.9);">Audience Engagement</span>
                                <small style="font-size:10px; color:rgba(255,255,255,0.6); margin-top:4px;">Click to watch tutorial</small>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="guide-card-body">
                        <h4 class="guide-card-title">Building & Engaging Your Audience</h4>
                        <p class="guide-card-desc">Discover proven strategies to encourage comments, reactions, and social shares to grow a loyal readership.</p>
                        <div class="guide-card-footer">
                            <i class="fa-regular fa-clock"></i> Aug 02, 2026 • 8 min watch
                        </div>
                    </div>
                </article>

            </div>
        </aside>

        <!-- Right Main Column: Primary Blog Cards Grid -->
        <div class="main-blogs-column">
            <section class="blogs" id="blogGrid">
                <?php
                if($result && mysqli_num_rows($result) > 0)
                {
                    while($row = mysqli_fetch_assoc($result))
                    {
                        $blogURL = "view_blog.php?id=" . (int) $row['id'];
                        $preview = trim(strip_tags($row['content']));
                ?>

                    <article
                        class="blog-card"
                        tabindex="0"
                        data-title="<?php echo htmlspecialchars(strtolower($row['title'])); ?>"
                        onclick="openBlog('<?php echo $blogURL; ?>')"
                        onkeydown="openBlogKeyboard(event, '<?php echo $blogURL; ?>')"
                    >
                        <div class="blog-image">
                            <?php if(!empty($row['image'])) { ?>
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                            <?php } else { ?>
                                <div class="image-placeholder">
                                    <?php echo strtoupper(substr($row['title'], 0, 1)); ?>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="blog-card-content">
                            <div class="blog-meta">
                                <span><?php echo htmlspecialchars($row['username']); ?></span>
                                <span><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span>
                            </div>

                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>

                            <p>
                                <?php
                                echo htmlspecialchars(substr($preview, 0, 95));
                                if(strlen($preview) > 95) { echo "..."; }
                                ?>
                            </p>

                            <?php if((int) $row['user_id'] === $userID) { ?>
                                <div class="blog-actions">
                                    <a href="edit_blog.php?id=<?php echo (int) $row['id']; ?>" class="edit-button" onclick="event.stopPropagation();">Edit</a>
                                    <a href="delete_blog.php?id=<?php echo (int) $row['id']; ?>" class="delete-button" onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this blog?');">Delete</a>
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
        </div>

    </div>

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

const heroNavItems =
    document.querySelectorAll(".hero-nav-item");

heroNavItems.forEach(function(item) {
    item.addEventListener("click", function() {
        heroNavItems.forEach(function(el) {
            el.classList.remove("active", "clicked");
        });
        this.classList.add("clicked");
    });
});

const blogCards =
    document.querySelectorAll(".blog-card");

if (searchInput) {
    searchInput.addEventListener("input", function() {
        const value = this.value.toLowerCase().trim();

        blogCards.forEach(function(card) {
            const title = card.dataset.title || "";
            card.style.display = title.includes(value) ? "" : "none";
        });

        if (value !== "") {
            const grid = document.getElementById("blogGrid");
            if (grid) {
                grid.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }
    });
}

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

<!-- TUTORIAL VIDEO MODAL -->
<div class="video-modal-overlay" id="tutorialVideoModal" onclick="closeVideoModal(event)">
    <div class="video-modal-card" onclick="event.stopPropagation()">
        <div class="video-modal-header">
            <h3 id="videoModalTitle">Blogger Tutorial Video</h3>
            <button class="video-modal-close" onclick="closeVideoModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="video-modal-body">
            <video id="tutorialVideoPlayer" controls playsinline style="width: 100%; border-radius: 14px; max-height: 440px; background: #000;">
                <source id="tutorialVideoSource" src="" type="video/mp4">
                Your browser does not support HTML5 video playback.
            </video>
        </div>
    </div>
</div>

<script>

function openTutorialVideo(title, videoUrl) {
    const modal = document.getElementById('tutorialVideoModal');
    const player = document.getElementById('tutorialVideoPlayer');
    const source = document.getElementById('tutorialVideoSource');
    const titleEl = document.getElementById('videoModalTitle');

    if (titleEl) titleEl.textContent = title;
    if (source) source.src = videoUrl;
    if (player) {
        player.load();
        player.play().catch(() => {});
    }
    if (modal) modal.classList.add('active');
}

function closeVideoModal(e) {
    if (e && e.target !== e.currentTarget && !e.target.classList.contains('video-modal-close') && !e.target.closest('.video-modal-close')) return;
    const modal = document.getElementById('tutorialVideoModal');
    const player = document.getElementById('tutorialVideoPlayer');
    if (player) player.pause();
    if (modal) modal.classList.remove('active');
}

</script>

</body>

</html>
<?php

session_start();

include "config.php";
include "site_config.php";

$userID = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Auto database migration for reviews and reactions
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `blog_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `review_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `blog_reactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `blog_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `reaction_type` VARCHAR(20) DEFAULT 'like',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_blog_unique` (`user_id`, `blog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$flashMsg = "";

// Handle Form Submission for new review
if (isset($_POST['submit_review'])) {
    if (!$userID) {
        header("Location: login.php");
        exit();
    }
    $blogID = (int) $_POST['blog_id'];
    $rating = (int) $_POST['rating'];
    $reviewText = trim($_POST['review_text']);

    if ($blogID > 0 && $rating >= 1 && $rating <= 5 && $reviewText !== "") {
        $reviewSafe = mysqli_real_escape_string($conn, $reviewText);
        $insertSQL = "INSERT INTO reviews (blog_id, user_id, rating, review_text) VALUES ($blogID, $userID, $rating, '$reviewSafe')";
        if (mysqli_query($conn, $insertSQL)) {
            $flashMsg = "Review published successfully!";
        }
    }
}

// Handle Reaction Toggle via AJAX or POST
if (isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    if (!$userID) {
        echo json_encode(['success' => false, 'message' => 'login_required']);
        exit();
    }
    $blogID = (int) $_POST['blog_id'];
    $check = mysqli_query($conn, "SELECT id FROM blog_reactions WHERE blog_id = $blogID AND user_id = $userID");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM blog_reactions WHERE blog_id = $blogID AND user_id = $userID");
        $liked = false;
    } else {
        mysqli_query($conn, "INSERT INTO blog_reactions (blog_id, user_id) VALUES ($blogID, $userID)");
        $liked = true;
    }
    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS total FROM blog_reactions WHERE blog_id = $blogID");
    $countData = mysqli_fetch_assoc($countRes);
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => (int)$countData['total']]);
    exit();
}

// Fetch all blogs to allow selecting in review modal
$allBlogsRes = mysqli_query($conn, "SELECT id, title FROM blogpost ORDER BY created_at DESC");

// Fetch reviews combined with blogpost and user data
$querySQL = "SELECT 
                r.id AS review_id,
                r.rating,
                r.review_text,
                r.created_at AS review_date,
                b.id AS blog_id,
                b.title AS blog_title,
                b.image AS blog_image,
                b.content AS blog_content,
                u.username AS author_name,
                ru.username AS reviewer_name,
                ap.profile_pic AS author_pic,
                rp.profile_pic AS reviewer_pic,
                (SELECT COUNT(*) FROM blog_reactions WHERE blog_id = b.id) AS like_count,
                (SELECT COUNT(*) FROM blog_reactions WHERE blog_id = b.id AND user_id = $userID) AS user_liked
             FROM reviews r
             JOIN blogpost b ON r.blog_id = b.id
             JOIN user u ON b.user_id = u.id
             JOIN user ru ON r.user_id = ru.id
             LEFT JOIN user_profiles ap ON u.id = ap.user_id
             LEFT JOIN user_profiles rp ON ru.id = rp.user_id
             ORDER BY r.created_at DESC";

$reviewsRes = mysqli_query($conn, $querySQL);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Writers' Reviews & Community | <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="css/reviews.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
            <a href="reviews.php" class="hero-nav-item active">Review</a>
            <a href="profile.php" class="hero-nav-item">Profile</a>
        </nav>
        <div class="hero-search-box" style="position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; border-radius: 999px !important; padding: 6px 16px !important; width: 210px !important;">
            <i class="fa-solid fa-magnifying-glass" style="color: rgba(255, 255, 255, 0.8) !important; margin-right: 8px !important; font-size: 13px !important;"></i>
            <input id="blogSearch" type="text" placeholder="Search..." style="background: transparent !important; border: none !important; outline: none !important; color: #ffffff !important; font-size: 13px !important; width: 100% !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; height: auto !important;" onkeydown="if(event.key==='Enter'){ window.location.href='dashboard.php?search='+encodeURIComponent(this.value); }">
        </div>
    </div>
</header>

<section class="reviews-hero-header">
    <video class="calm-video-bg" id="heroVideoBg" autoplay loop muted playsinline <?php if (!file_exists(__DIR__ . "/videos/calm_bg.mp4")) { echo 'style="display:none;"'; } ?>>
        <source src="videos/calm_bg.mp4?v=<?php echo file_exists(__DIR__ . "/videos/calm_bg.mp4") ? filemtime(__DIR__ . "/videos/calm_bg.mp4") : time(); ?>" type="video/mp4">
    </video>
    <div class="calm-ambient-animation" id="heroAmbientBg" <?php if (file_exists(__DIR__ . "/videos/calm_bg.mp4")) { echo 'style="display:none;"'; } ?>></div>

    <div class="reviews-hero-content">
        <h1>Writers' Reviews & Reactions</h1>
        <p>Discover top-rated articles, read authentic reviews from fellow authors, and react to stories that inspire you.</p>

        <?php if ($userID) { ?>
            <button class="write-review-btn" onclick="openModal()">
                <i class="fa-solid fa-pen-to-square"></i> Write a Review
            </button>
        <?php } else { ?>
            <a href="login.php" class="write-review-btn">
                <i class="fa-solid fa-right-to-bracket"></i> Sign in to Review
            </a>
        <?php } ?>
    </div>
</section>

<div class="reviews-container">

    <?php if ($flashMsg) { ?>
        <div style="max-width:1240px; margin: 0 auto 24px; padding: 14px 20px; border-radius:12px; font-weight:600; background:var(--purple); color:#ffffff; box-shadow: 0 4px 15px rgba(147, 104, 184, 0.3);">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flashMsg); ?>
        </div>
    <?php } ?>

    <!-- Reviews Grid Layout -->
    <div class="reviews-grid">

        <?php 
        if ($reviewsRes && mysqli_num_rows($reviewsRes) > 0) {
            while ($row = mysqli_fetch_assoc($reviewsRes)) {
                $rating = (int) $row['rating'];
                $likeCount = (int) $row['like_count'];
                $userLiked = (int) $row['user_liked'] > 0;
                $coverImg = !empty($row['blog_image']) ? 'uploads/' . $row['blog_image'] : 'images/logo.png';
        ?>
            <article class="review-card" data-genre="all">

                <!-- Left: Cover Image -->
                <div class="review-cover-wrapper">
                    <img src="<?php echo htmlspecialchars($coverImg); ?>" alt="<?php echo htmlspecialchars($row['blog_title']); ?>">
                </div>

                <!-- Right: Details -->
                <div class="review-details">

                    <div class="review-header-info">
                        <a href="view_blog.php?id=<?php echo (int)$row['blog_id']; ?>" class="review-blog-title">
                            <?php echo htmlspecialchars($row['blog_title']); ?>
                        </a>
                        <div class="review-author">
                            by <?php echo htmlspecialchars($row['author_name']); ?>
                        </div>

                        <!-- Star Rating -->
                        <div class="star-rating-row">
                            <div class="stars">
                                <?php for ($s = 1; $s <= 5; $s++) { ?>
                                    <i class="<?php echo $s <= $rating ? 'fa-solid fa-star' : 'fa-regular fa-star'; ?>"></i>
                                <?php } ?>
                            </div>
                            <span class="rating-score"><?php echo number_format($rating, 1); ?></span>
                            <span class="voters-count">(Reviewed by <?php echo htmlspecialchars($row['reviewer_name']); ?>)</span>
                        </div>

                        <p class="review-text">
                            "<?php echo htmlspecialchars($row['review_text']); ?>"
                        </p>
                    </div>

                    <!-- Footer: Avatar Stack & Like Reaction Button -->
                    <div class="review-card-footer">
                        <div class="social-proof">
                            <div class="avatar-stack">
                                <?php echo renderUserAvatar($row['author_name'], $row['author_pic'] ?? null, 'mini-avatar'); ?>
                                <?php echo renderUserAvatar($row['reviewer_name'], $row['reviewer_pic'] ?? null, 'mini-avatar'); ?>
                            </div>
                            <span class="social-text">
                                <?php echo htmlspecialchars($row['reviewer_name']); ?> and <?php echo $likeCount; ?> others liked this
                            </span>
                        </div>

                        <button 
                            class="reaction-btn <?php echo $userLiked ? 'liked' : ''; ?>" 
                            onclick="toggleLike(<?php echo (int)$row['blog_id']; ?>, this)"
                            title="React to this blog">
                            <i class="<?php echo $userLiked ? 'fa-solid fa-heart' : 'fa-regular fa-heart'; ?>"></i>
                            <span class="like-counter"><?php echo $likeCount; ?></span>
                        </button>
                    </div>

                </div>

            </article>
        <?php 
            }
        } else {
        ?>
            <div style="grid-column: 1 / -1; text-align:center; padding: 60px 20px; background:white; border-radius:20px;">
                <h3 style="font-size:20px; color:var(--text); margin-bottom:10px;">No reviews yet</h3>
                <p style="color:var(--muted);">Be the first writer to review a blog post!</p>
            </div>
        <?php } ?>

    </div>

</div>

<!-- WRITE REVIEW MODAL -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal-card">
        <div class="modal-header">
            <h2>Write a Review</h2>
            <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="reviews.php" method="POST">
            <div class="form-group">
                <label for="blog_id">Select Blog Post</label>
                <select name="blog_id" id="blog_id" required>
                    <?php if ($allBlogsRes) {
                        while ($b = mysqli_fetch_assoc($allBlogsRes)) { ?>
                            <option value="<?php echo (int)$b['id']; ?>">
                                <?php echo htmlspecialchars($b['title']); ?>
                            </option>
                    <?php } } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Your Star Rating</label>
                <input type="hidden" name="rating" id="ratingInput" value="5">
                <div class="star-picker" id="starPicker">
                    <i class="fa-solid fa-star selected" data-value="1"></i>
                    <i class="fa-solid fa-star selected" data-value="2"></i>
                    <i class="fa-solid fa-star selected" data-value="3"></i>
                    <i class="fa-solid fa-star selected" data-value="4"></i>
                    <i class="fa-solid fa-star selected" data-value="5"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="review_text">Your Review</label>
                <textarea name="review_text" id="review_text" rows="4" placeholder="Share your thoughts about this blog post..." required></textarea>
            </div>

            <button type="submit" name="submit_review" class="submit-btn">
                Publish Review
            </button>
        </form>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
function openModal() {
    document.getElementById("reviewModal").classList.add("open");
}

function closeModal() {
    document.getElementById("reviewModal").classList.remove("open");
}

// Interactive Star Selector
const stars = document.querySelectorAll("#starPicker i");
const ratingInput = document.getElementById("ratingInput");

stars.forEach((star, idx) => {
    star.addEventListener("click", () => {
        const val = idx + 1;
        ratingInput.value = val;
        stars.forEach((s, i) => {
            if (i < val) {
                s.classList.add("selected");
            } else {
                s.classList.remove("selected");
            }
        });
    });
});

// Interactive Real-Time Like Reaction
function toggleLike(blogId, btn) {
    const formData = new FormData();
    formData.append("action", "toggle_like");
    formData.append("blog_id", blogId);

    fetch("reviews.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector("i");
            const counter = btn.querySelector(".like-counter");
            counter.textContent = data.count;

            if (data.liked) {
                btn.classList.add("liked");
                icon.className = "fa-solid fa-heart";
            } else {
                btn.classList.remove("liked");
                icon.className = "fa-regular fa-heart";
            }
        } else if (data.message === "login_required") {
            window.location.href = "login.php";
        }
    })
    .catch(err => console.error(err));
}
</script>

</body>
</html>

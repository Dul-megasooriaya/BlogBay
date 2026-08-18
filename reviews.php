<?php
include "config.php";
include "includes/session_manager.php";
include "site_config.php";

checkRememberMeCookie($conn);

$userID = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

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
if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
    $flashMsg = "Review published successfully!";
}

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
            header("Location: reviews.php?msg=success");
            exit();
        }
    }
}

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
    $totalLikes = (int)$countData['total'];

    $likersRes = mysqli_query($conn, "
        SELECT u.username, p.profile_pic 
        FROM blog_reactions br 
        JOIN user u ON br.user_id = u.id 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE br.blog_id = $blogID 
        ORDER BY br.created_at DESC 
        LIMIT 2
    ");
    $likers = [];
    if ($likersRes) {
        while ($l = mysqli_fetch_assoc($likersRes)) {
            $likers[] = $l;
        }
    }

    $socialText = "";
    if ($totalLikes === 0) {
        $socialText = "Be the first to like this";
    } elseif ($totalLikes === 1) {
        $socialText = htmlspecialchars($likers[0]['username']) . " liked this";
    } elseif ($totalLikes === 2) {
        $socialText = htmlspecialchars($likers[0]['username']) . " and " . htmlspecialchars($likers[1]['username']) . " liked this";
    } else {
        $others = $totalLikes - 1;
        $socialText = htmlspecialchars($likers[0]['username']) . " and " . $others . " others liked this";
    }

    $avatarsHTML = "";
    if ($totalLikes > 0) {
        foreach ($likers as $l) {
            $avatarsHTML .= renderUserAvatar($l['username'], $l['profile_pic'] ?? null, 'mini-avatar');
        }
    }

    echo json_encode([
        'success' => true, 
        'liked' => $liked, 
        'count' => $totalLikes,
        'socialText' => $socialText,
        'avatarsHTML' => $avatarsHTML
    ]);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'edit_review') {
    header('Content-Type: application/json');
    if (!$userID) {
        echo json_encode(['success' => false, 'message' => 'Please log in to edit your review.']);
        exit();
    }
    $reviewID = (int)($_POST['review_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($reviewID > 0 && $rating >= 1 && $rating <= 5 && $reviewText !== "") {
        $reviewSafe = mysqli_real_escape_string($conn, $reviewText);
        $check = mysqli_query($conn, "SELECT id, user_id FROM reviews WHERE id = $reviewID");
        if ($rRow = mysqli_fetch_assoc($check)) {
            if ((int)$rRow['user_id'] === (int)$userID) {
                mysqli_query($conn, "UPDATE reviews SET rating = $rating, review_text = '$reviewSafe' WHERE id = $reviewID");
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'You can only edit your own review.']);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid review details.']);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    header('Content-Type: application/json');
    if (!$userID) {
        echo json_encode(['success' => false, 'message' => 'Please log in to delete your review.']);
        exit();
    }
    $reviewID = (int)($_POST['review_id'] ?? 0);
    if ($reviewID > 0) {
        $check = mysqli_query($conn, "SELECT id, user_id FROM reviews WHERE id = $reviewID");
        if ($rRow = mysqli_fetch_assoc($check)) {
            if ((int)$rRow['user_id'] === (int)$userID) {
                mysqli_query($conn, "DELETE FROM reviews WHERE id = $reviewID");
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'You can only delete your own review.']);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Review not found.']);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'get_blog_reviews') {
    header('Content-Type: application/json');
    $bId = (int)($_GET['blog_id'] ?? 0);
    if (!$bId) {
        echo json_encode(['success' => false, 'reviews' => []]);
        exit();
    }
    
    $bRes = mysqli_query($conn, "SELECT title FROM blogpost WHERE id = $bId");
    $blogInfo = mysqli_fetch_assoc($bRes);

    $rRes = mysqli_query($conn, "
        SELECT 
            r.id,
            r.user_id,
            r.rating,
            r.review_text,
            r.created_at,
            u.username,
            p.profile_pic
        FROM reviews r
        JOIN user u ON r.user_id = u.id
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE r.blog_id = $bId
        ORDER BY r.created_at DESC
    ");
    
    $reviewsList = [];
    $totalStars = 0;
    while ($r = mysqli_fetch_assoc($rRes)) {
        $reviewsList[] = [
            'id' => (int)$r['id'],
            'rating' => (int)$r['rating'],
            'review_text' => $r['review_text'],
            'created_at' => date("M d, Y", strtotime($r['created_at'])),
            'username' => $r['username'],
            'is_owner' => ((int)$r['user_id'] === $userID),
            'avatar_html' => renderUserAvatar($r['username'], $r['profile_pic'] ?? null, 'mini-avatar')
        ];
        $totalStars += (int)$r['rating'];
    }
    
    $count = count($reviewsList);
    $avgRating = $count > 0 ? round($totalStars / $count, 1) : 0;

    echo json_encode([
        'success' => true,
        'blog_title' => $blogInfo['title'] ?? 'Blog Reviews',
        'avg_rating' => number_format($avgRating, 1),
        'total_count' => $count,
        'reviews' => $reviewsList
    ]);
    exit();
}

$allBlogsRes = mysqli_query($conn, "SELECT id, title FROM blogpost ORDER BY created_at DESC");
$querySQL = "SELECT 
                b.id AS blog_id,
                b.title AS blog_title,
                b.image AS blog_image,
                b.content AS blog_content,
                u.username AS author_name,
                ap.profile_pic AS author_pic,
                COUNT(r.id) AS total_reviews,
                COALESCE(ROUND(AVG(r.rating), 1), 5.0) AS avg_rating,
                (SELECT review_text FROM reviews WHERE blog_id = b.id ORDER BY created_at DESC LIMIT 1) AS latest_review_text,
                (SELECT ru.username FROM reviews lr JOIN user ru ON lr.user_id = ru.id WHERE lr.blog_id = b.id ORDER BY lr.created_at DESC LIMIT 1) AS latest_reviewer_name,
                (SELECT COUNT(*) FROM blog_reactions WHERE blog_id = b.id) AS like_count,
                (SELECT COUNT(*) FROM blog_reactions WHERE blog_id = b.id AND user_id = $userID) AS user_liked
             FROM reviews r
             JOIN blogpost b ON r.blog_id = b.id
             JOIN user u ON b.user_id = u.id
             LEFT JOIN user_profiles ap ON u.id = ap.user_id
             GROUP BY b.id
             ORDER BY MAX(r.created_at) DESC";

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
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="hero-navbar">
    <div class="hero-navbar-inner">
        <a href="dashboard.php" class="hero-brand">
            <?php echo renderSiteLogo(); ?>
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
        <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(this)" title="Toggle navigation menu">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    <div class="mobile-dropdown-menu" id="mobileDropdownMenu">
        <a href="dashboard.php" class="mobile-nav-item">Dashboard</a>
        <a href="dashboard.php#blogGrid" class="mobile-nav-item">Blogs</a>
        <a href="reviews.php" class="mobile-nav-item active">Review</a>
        <a href="profile.php" class="mobile-nav-item">Profile</a>
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

    <div class="reviews-grid">

        <?php 
        if ($reviewsRes && mysqli_num_rows($reviewsRes) > 0) {
            while ($row = mysqli_fetch_assoc($reviewsRes)) {
                $avgRating = (float) $row['avg_rating'];
                $roundedStars = round($avgRating);
                $totalReviews = (int) $row['total_reviews'];
                $likeCount = (int) $row['like_count'];
                $userLiked = (int) $row['user_liked'] > 0;
                $coverImg = (!empty($row['blog_image']) && file_exists(__DIR__ . '/uploads/' . $row['blog_image'])) ? 'uploads/' . $row['blog_image'] : 'images/logo.png';
        ?>
            <article class="review-card" data-genre="all">

                <div class="review-cover-wrapper">
                    <img src="<?php echo htmlspecialchars($coverImg); ?>" alt="<?php echo htmlspecialchars($row['blog_title']); ?>">
                </div>

                <div class="review-details">

                    <div class="review-header-info">
                        <a href="view_blog.php?id=<?php echo (int)$row['blog_id']; ?>" class="review-blog-title">
                            <?php echo htmlspecialchars($row['blog_title']); ?>
                        </a>
                        <div class="review-author">
                            by <?php echo htmlspecialchars($row['author_name']); ?>
                        </div>

                        <div class="star-rating-row" onclick="openAllReviewsModal(<?php echo (int)$row['blog_id']; ?>)" style="cursor:pointer;" title="Click to view all reviews">
                            <div class="stars">
                                <?php for ($s = 1; $s <= 5; $s++) { ?>
                                    <i class="<?php echo $s <= $roundedStars ? 'fa-solid fa-star' : 'fa-regular fa-star'; ?>"></i>
                                <?php } ?>
                            </div>
                            <span class="rating-score"><?php echo number_format($avgRating, 1); ?></span>
                            
                            <button type="button" class="see-reviews-btn" onclick="event.stopPropagation(); openAllReviewsModal(<?php echo (int)$row['blog_id']; ?>)">
                                <i class="fa-solid fa-comments"></i> 
                                See Reviews (<?php echo $totalReviews; ?>)
                            </button>
                        </div>

                        <p class="review-text">
                            "<?php echo htmlspecialchars($row['latest_review_text']); ?>"
                            <?php if(!empty($row['latest_reviewer_name'])) { ?>
                                <small style="display:block; margin-top:4px; font-size:11px; color:var(--muted); font-style:italic;">
                                    — Latest review by <?php echo htmlspecialchars($row['latest_reviewer_name']); ?>
                                </small>
                            <?php } ?>
                        </p>
                    </div>

                    <div class="review-card-footer">
                        <div class="social-proof">
                            <?php 
                            $likersRes = mysqli_query($conn, "
                                SELECT u.username, p.profile_pic 
                                FROM blog_reactions br 
                                JOIN user u ON br.user_id = u.id 
                                LEFT JOIN user_profiles p ON u.id = p.user_id 
                                WHERE br.blog_id = " . (int)$row['blog_id'] . " 
                                ORDER BY br.created_at DESC 
                                LIMIT 2
                            ");
                            $likers = [];
                            if ($likersRes) {
                                while ($l = mysqli_fetch_assoc($likersRes)) {
                                    $likers[] = $l;
                                }
                            }
                            ?>

                            <div class="avatar-stack">
                                <?php 
                                if ($likeCount > 0) {
                                    foreach ($likers as $l) {
                                        echo renderUserAvatar($l['username'], $l['profile_pic'] ?? null, 'mini-avatar');
                                    }
                                }
                                ?>
                            </div>

                            <span class="social-text">
                                <?php 
                                if ($likeCount === 0) {
                                    echo "Be the first to like this";
                                } elseif ($likeCount === 1) {
                                    echo htmlspecialchars($likers[0]['username']) . " liked this";
                                } elseif ($likeCount === 2) {
                                    echo htmlspecialchars($likers[0]['username']) . " and " . htmlspecialchars($likers[1]['username']) . " liked this";
                                } else {
                                    $others = $likeCount - 1;
                                    echo htmlspecialchars($likers[0]['username']) . " and " . $others . " others liked this";
                                }
                                ?>
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

<div class="modal-overlay" id="allReviewsModal">
    <div class="modal-card reviews-list-modal">
        <div class="modal-header">
            <div>
                <h2 id="modalBlogTitle" style="font-size:20px; margin-bottom:4px;">Blog Reviews</h2>
                <div id="modalRatingSummary" style="font-size:13px; color:var(--muted); display:flex; align-items:center; gap:8px;">
                </div>
            </div>
            <button class="modal-close" onclick="closeAllReviewsModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-body reviews-scroll-container" id="allReviewsList">
        </div>

        <div class="modal-footer" style="margin-top:20px; padding-top:16px; border-top:1px solid #ebe8f2; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:12px; color:var(--muted);">Want to add your thoughts?</span>
            <button class="write-review-btn" onclick="closeAllReviewsModal(); openModal();" style="padding:8px 18px; font-size:13px;">
                <i class="fa-solid fa-pen-to-square"></i> Write a Review
            </button>
        </div>
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

            const footer = btn.closest(".review-card-footer");
            if (footer) {
                const socialTextEl = footer.querySelector(".social-text");
                const avatarStackEl = footer.querySelector(".avatar-stack");
                if (socialTextEl && data.socialText !== undefined) {
                    socialTextEl.textContent = data.socialText;
                }
                if (avatarStackEl && data.avatarsHTML !== undefined) {
                    avatarStackEl.innerHTML = data.avatarsHTML;
                }
            }
        } else if (data.message === "login_required") {
            window.location.href = "login.php";
        }
    })
    .catch(err => console.error(err));
}

function openAllReviewsModal(blogId) {
    const modal = document.getElementById("allReviewsModal");
    const titleEl = document.getElementById("modalBlogTitle");
    const summaryEl = document.getElementById("modalRatingSummary");
    const listEl = document.getElementById("allReviewsList");

    if (!modal) return;

    listEl.innerHTML = '<div style="text-align:center; padding:30px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px; color:var(--purple);"></i><p style="margin-top:10px; font-size:13px; color:var(--muted);">Loading reviews...</p></div>';
    modal.classList.add("open");

    const selectEl = document.getElementById("blog_id");
    if (selectEl) {
        selectEl.value = blogId;
    }

    fetch(`reviews.php?action=get_blog_reviews&blog_id=${blogId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            titleEl.textContent = `Reviews for "${data.blog_title}"`;
            
            let starsHTML = '';
            const roundedAvg = Math.round(parseFloat(data.avg_rating));
            for (let s = 1; s <= 5; s++) {
                starsHTML += `<i class="${s <= roundedAvg ? 'fa-solid fa-star' : 'fa-regular fa-star'}" style="color:var(--star-gold);"></i>`;
            }

            summaryEl.innerHTML = `
                <div class="stars" style="color:var(--star-gold); display:flex; gap:2px;">${starsHTML}</div>
                <strong style="color:var(--text); font-weight:700;">${data.avg_rating}</strong>
                <span>(${data.total_count} ${data.total_count === 1 ? 'review' : 'reviews'})</span>
            `;

            if (data.reviews && data.reviews.length > 0) {
                listEl.innerHTML = data.reviews.map(r => {
                    let rStars = '';
                    for (let s = 1; s <= 5; s++) {
                        rStars += `<i class="${s <= r.rating ? 'fa-solid fa-star' : 'fa-regular fa-star'}" style="color:var(--star-gold); font-size:12px;"></i>`;
                    }

                    const ownerActionsHTML = r.is_owner ? `
                        <div class="review-owner-actions">
                            <button type="button" class="review-action-btn edit-review-btn" onclick="startEditReview(${r.id}, ${r.rating}, '${escapeQuotes(r.review_text)}', ${blogId})" title="Edit your review">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button type="button" class="review-action-btn delete-review-btn" onclick="confirmDeleteReview(${r.id}, ${blogId})" title="Delete your review">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    ` : '';

                    return `
                        <div class="individual-review-item" id="review-item-${r.id}">
                            <div class="review-item-header">
                                <div class="review-user-info">
                                    ${r.avatar_html}
                                    <div>
                                        <div class="review-user-name">${escapeHTML(r.username)} ${r.is_owner ? '<span style="font-size:10px; color:var(--purple); background:rgba(147, 104, 184, 0.12); padding:2px 6px; border-radius:999px; margin-left:4px;">You</span>' : ''}</div>
                                        <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                                            <div class="stars" style="display:flex; gap:2px;">${rStars}</div>
                                            <span style="font-size:11px; font-weight:700; color:var(--text);">${r.rating}.0</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    ${ownerActionsHTML}
                                    <span class="review-item-date">${r.created_at}</span>
                                </div>
                            </div>
                            <p class="review-item-text" id="review-text-display-${r.id}">"${escapeHTML(r.review_text)}"</p>
                            <div id="review-edit-box-${r.id}" style="display:none; margin-top:10px;"></div>
                        </div>
                    `;
                }).join('');
            } else {
                listEl.innerHTML = '<div style="text-align:center; padding:30px; color:var(--muted); font-size:13px;">No reviews yet for this story. Be the first to write one!</div>';
            }
        }
    })
    .catch(err => {
        console.error(err);
        listEl.innerHTML = '<div style="text-align:center; padding:20px; color:red;">Failed to load reviews. Please try again.</div>';
    });
}

function startEditReview(reviewId, currentRating, currentText, blogId) {
    const textDisplay = document.getElementById(`review-text-display-${reviewId}`);
    const editBox = document.getElementById(`review-edit-box-${reviewId}`);
    if (!editBox) return;

    if (textDisplay) textDisplay.style.display = "none";
    editBox.style.display = "block";

    editBox.innerHTML = `
        <div style="background:#ffffff; padding:14px; border-radius:12px; border:1.5px solid var(--purple);">
            <div style="margin-bottom:8px;">
                <label style="font-size:12px; font-weight:700; color:var(--text);">Update Rating:</label>
                <div class="star-picker" id="editStarPicker-${reviewId}" style="font-size:20px; margin-top:4px;">
                    ${[1,2,3,4,5].map(val => `<i class="fa-solid fa-star ${val <= currentRating ? 'selected' : ''}" data-val="${val}" style="color:${val <= currentRating ? 'var(--star-gold)' : '#dedce7'}; cursor:pointer;"></i>`).join(' ')}
                </div>
                <input type="hidden" id="editRatingInput-${reviewId}" value="${currentRating}">
            </div>
            <div style="margin-bottom:10px;">
                <textarea id="editTextarea-${reviewId}" rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #dedce7; font-size:13px; font-family:inherit;">${escapeHTML(currentText)}</textarea>
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="cancelEditReview(${reviewId})" style="padding:6px 14px; border-radius:999px; border:1px solid #ccc; background:#fff; font-size:12px; cursor:pointer;">Cancel</button>
                <button type="button" onclick="saveEditReview(${reviewId}, ${blogId})" style="padding:6px 16px; border-radius:999px; border:none; background:var(--purple); color:#fff; font-size:12px; font-weight:700; cursor:pointer;">Save Changes</button>
            </div>
        </div>
    `;

    const editStars = editBox.querySelectorAll(`#editStarPicker-${reviewId} i`);
    const ratingInput = document.getElementById(`editRatingInput-${reviewId}`);
    editStars.forEach((star, idx) => {
        star.addEventListener("click", () => {
            const val = idx + 1;
            ratingInput.value = val;
            editStars.forEach((s, i) => {
                s.style.color = i < val ? 'var(--star-gold)' : '#dedce7';
            });
        });
    });
}

function cancelEditReview(reviewId) {
    const textDisplay = document.getElementById(`review-text-display-${reviewId}`);
    const editBox = document.getElementById(`review-edit-box-${reviewId}`);
    if (textDisplay) textDisplay.style.display = "block";
    if (editBox) editBox.style.display = "none";
}

function saveEditReview(reviewId, blogId) {
    const ratingInput = document.getElementById(`editRatingInput-${reviewId}`);
    const textarea = document.getElementById(`editTextarea-${reviewId}`);
    if (!ratingInput || !textarea) return;

    const rating = ratingInput.value;
    const reviewText = textarea.value.trim();

    if (!reviewText) {
        alert("Please enter your review text.");
        return;
    }

    const formData = new FormData();
    formData.append("action", "edit_review");
    formData.append("review_id", reviewId);
    formData.append("rating", rating);
    formData.append("review_text", reviewText);

    fetch("reviews.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = "reviews.php";
        } else {
            alert(data.message || "Failed to update review.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("An error occurred while updating the review.");
    });
}

function confirmDeleteReview(reviewId, blogId) {
    if (!confirm("Are you sure you want to delete your review?")) return;

    const formData = new FormData();
    formData.append("action", "delete_review");
    formData.append("review_id", reviewId);

    fetch("reviews.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = "reviews.php";
        } else {
            alert(data.message || "Failed to delete review.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("An error occurred while deleting the review.");
    });
}

function closeAllReviewsModal() {
    const modal = document.getElementById("allReviewsModal");
    if (modal) modal.classList.remove("open");
}

function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

function escapeQuotes(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, "&quot;");
}
</script>

</body>
</html>

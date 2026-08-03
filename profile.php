<?php

session_start();

include "config.php";
include "site_config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = (int) $_SESSION['user_id'];
$message = "";
$messageType = "";

// Auto Database Migration for dedicated user_profiles Table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `designation` VARCHAR(100) DEFAULT 'Writer & Content Author',
    `phone` VARCHAR(50) DEFAULT NULL,
    `location` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `facebook` VARCHAR(255) DEFAULT NULL,
    `linkedin` VARCHAR(255) DEFAULT NULL,
    `twitter` VARCHAR(255) DEFAULT NULL,
    `github` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure default profile row exists for current user
mysqli_query($conn, "INSERT IGNORE INTO `user_profiles` (`user_id`) VALUES ($userID)");

// Handle Profile Form Updates
if (isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $designation = trim($_POST['designation']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    $facebook = trim($_POST['facebook']);
    $linkedin = trim($_POST['linkedin']);
    $twitter = trim($_POST['twitter']);
    $github = trim($_POST['github']);

    if ($username === "" || $email === "") {
        $message = "Username and email are required.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
        $messageType = "error";
    } else {
        $usernameSafe = mysqli_real_escape_string($conn, $username);
        $emailSafe = mysqli_real_escape_string($conn, $email);
        $designationSafe = mysqli_real_escape_string($conn, $designation);
        $phoneSafe = mysqli_real_escape_string($conn, $phone);
        $locationSafe = mysqli_real_escape_string($conn, $location);
        $bioSafe = mysqli_real_escape_string($conn, $bio);
        $fbSafe = mysqli_real_escape_string($conn, $facebook);
        $liSafe = mysqli_real_escape_string($conn, $linkedin);
        $twSafe = mysqli_real_escape_string($conn, $twitter);
        $ghSafe = mysqli_real_escape_string($conn, $github);

        $checkSQL = "SELECT id FROM user WHERE email = '$emailSafe' AND id != $userID LIMIT 1";
        $checkResult = mysqli_query($conn, $checkSQL);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $message = "This email is already in use by another account.";
            $messageType = "error";
        } else {
            // Update Base User Account Table
            mysqli_query($conn, "UPDATE user SET username = '$usernameSafe', email = '$emailSafe' WHERE id = $userID");
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Handle Profile Picture File Upload
            $picSQL = "";
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['profile_pic']['tmp_name'];
                $fileName = $_FILES['profile_pic']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (in_array($fileExt, $allowed)) {
                    $newPicName = "avatar_" . $userID . "_" . time() . "." . $fileExt;
                    $destPath = __DIR__ . "/uploads/" . $newPicName;
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $picSafe = mysqli_real_escape_string($conn, $newPicName);
                        $picSQL = ", profile_pic = '$picSafe'";
                        $_SESSION['profile_pic'] = $newPicName;
                    }
                }
            }

            // Upsert Dedicated user_profiles Table
            $profileUpdateSQL = "INSERT INTO user_profiles 
                (user_id, designation, phone, location, bio, facebook, linkedin, twitter, github)
                VALUES 
                ($userID, '$designationSafe', '$phoneSafe', '$locationSafe', '$bioSafe', '$fbSafe', '$liSafe', '$twSafe', '$ghSafe')
                ON DUPLICATE KEY UPDATE
                designation = '$designationSafe',
                phone = '$phoneSafe',
                location = '$locationSafe',
                bio = '$bioSafe',
                facebook = '$fbSafe',
                linkedin = '$liSafe',
                twitter = '$twSafe',
                github = '$ghSafe'
                $picSQL";

            if (mysqli_query($conn, $profileUpdateSQL)) {
                $message = "Profile updated successfully!";
                $messageType = "success";
            } else {
                $message = "Profile update failed.";
                $messageType = "error";
            }
        }
    }
}

// Fetch Fresh Joined User & Profile Data
$sql = "SELECT u.id, u.username, u.email, u.role,
               p.designation, p.phone, p.location, p.bio, p.profile_pic,
               p.facebook, p.linkedin, p.twitter, p.github
        FROM user u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = $userID LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);
$_SESSION['profile_pic'] = $user['profile_pic'];

// Fetch User's Published Blogs
$blogsSQL = "SELECT * FROM blogpost WHERE user_id = $userID ORDER BY created_at DESC";
$myBlogsRes = mysqli_query($conn, $blogsSQL);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?>'s Profile | <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="css/profile_v2.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer.css?v=5">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="hero-navbar hero-navbar-solid">
    <div class="hero-navbar-inner">
        <a href="dashboard.php" class="hero-brand">
            <?php echo renderSiteLogo(); ?>
            <?php echo renderSiteName(); ?>
        </a>
        <nav class="hero-nav-links">
            <a href="dashboard.php" class="hero-nav-item">Dashboard</a>
            <a href="dashboard.php#blogGrid" class="hero-nav-item">Blogs</a>
            <a href="reviews.php" class="hero-nav-item">Review</a>
            <a href="profile.php" class="hero-nav-item active">Profile</a>
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
        <a href="reviews.php" class="mobile-nav-item">Review</a>
        <a href="profile.php" class="mobile-nav-item active">Profile</a>
    </div>
</header>

<main class="profile-wrapper">

    <?php if ($message) { ?>
        <div style="max-width:1240px; margin: 0 auto 24px; padding: 14px 20px; border-radius:12px; font-weight:600; background:var(--purple); color:#ffffff; box-shadow: 0 4px 15px rgba(147, 104, 184, 0.3);">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- Two-Column Layout Grid -->
    <div class="profile-layout-grid">

        <!-- LEFT COLUMN: AUTHOR CARD & CONTACT DETAILS -->
        <aside class="author-profile-card">

            <?php echo renderUserAvatar($user['username'], $user['profile_pic'], 'author-avatar-badge'); ?>

            <div class="author-name-title">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="author-designation">
                    <?php echo htmlspecialchars(!empty($user['designation']) ? $user['designation'] : 'Writer & Content Author'); ?>
                </div>
            </div>

            <!-- SOCIAL LINKS ROW -->
            <div class="social-icons-row">
                <a href="<?php echo !empty($user['facebook']) ? htmlspecialchars($user['facebook']) : '#'; ?>" target="_blank" class="social-icon-btn" title="Facebook">
                    <i class="fa-brands fa-facebook-f" style="color:#1877f2;"></i>
                </a>
                <a href="<?php echo !empty($user['linkedin']) ? htmlspecialchars($user['linkedin']) : '#'; ?>" target="_blank" class="social-icon-btn" title="LinkedIn">
                    <i class="fa-brands fa-linkedin-in" style="color:#0a66c2;"></i>
                </a>
                <a href="<?php echo !empty($user['twitter']) ? htmlspecialchars($user['twitter']) : '#'; ?>" target="_blank" class="social-icon-btn" title="Twitter / X">
                    <i class="fa-brands fa-x-twitter" style="color:#1da1f2;"></i>
                </a>
                <a href="<?php echo !empty($user['github']) ? htmlspecialchars($user['github']) : '#'; ?>" target="_blank" class="social-icon-btn" title="GitHub">
                    <i class="fa-brands fa-github" style="color:#333;"></i>
                </a>
            </div>

            <!-- CONTACT DETAILS CARD -->
            <div class="contact-info-card">
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <div class="info-text">
                        <span>Phone</span>
                        <strong><?php echo htmlspecialchars(!empty($user['phone']) ? $user['phone'] : '+94 77 123 4567'); ?></strong>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon" style="color:#ab3b9d;"><i class="fa-regular fa-envelope"></i></div>
                    <div class="info-text">
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon" style="color:#9368b8;"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text">
                        <span>Location</span>
                        <strong><?php echo htmlspecialchars(!empty($user['location']) ? $user['location'] : 'Sri Lanka'); ?></strong>
                    </div>
                </div>
            </div>

            <button class="toggle-edit-btn" onclick="toggleEditForm()">
                <i class="fa-solid fa-user-pen"></i> Edit Profile Details
            </button>

            <a href="logout.php" style="display:inline-block; margin-top:16px; color:var(--purple); font-size:13px; font-weight:600; text-decoration:none;">
                <i class="fa-solid fa-right-from-bracket"></i> Sign out
            </a>

        </aside>

        <!-- RIGHT COLUMN: ABOUT ME & EDIT FORM & PUBLISHED STORIES -->
        <section class="profile-content-card">

            <h2 class="section-header-title">ABOUT ME</h2>

            <p class="about-text-content">
                <?php 
                if (!empty($user['bio'])) {
                    echo nl2br(htmlspecialchars($user['bio']));
                } else {
                    echo "Hello there! I am a passionate writer and author on BlogBay. I enjoy exploring new ideas, sharing insights on technology, nature, and lifestyle, and connecting with readers around the world.";
                }
                ?>
            </p>

            <!-- EDIT FORM SECTION (Togglable) -->
            <div class="profile-edit-section" id="editFormSection">
                <h3 class="sub-section-title">Update Profile Details</h3>

                <form action="profile.php" method="POST" enctype="multipart/form-data">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="profile_pic">Profile Picture (Avatar)</label>
                        <input type="file" name="profile_pic" id="profile_pic" accept="image/*">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="designation">Designation / Role</label>
                            <input type="text" name="designation" id="designation" value="<?php echo htmlspecialchars($user['designation'] ?? ''); ?>" placeholder="e.g. Frontend Developer / Writer">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +94 77 123 4567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location / City</label>
                        <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="e.g. Colombo, Sri Lanka">
                    </div>

                    <div class="form-group">
                        <label for="bio">About Me (Bio)</label>
                        <textarea name="bio" id="bio" rows="4" placeholder="Write a short summary about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <h4 style="font-size:14px; font-weight:700; color:var(--text); margin:20px 0 12px;">Social Profile Links</h4>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="facebook">Facebook URL</label>
                            <input type="url" name="facebook" id="facebook" value="<?php echo htmlspecialchars($user['facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourprofile">
                        </div>
                        <div class="form-group">
                            <label for="linkedin">LinkedIn URL</label>
                            <input type="url" name="linkedin" id="linkedin" value="<?php echo htmlspecialchars($user['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourprofile">
                        </div>
                        <div class="form-group">
                            <label for="twitter">Twitter / X URL</label>
                            <input type="url" name="twitter" id="twitter" value="<?php echo htmlspecialchars($user['twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourhandle">
                        </div>
                        <div class="form-group">
                            <label for="github">GitHub URL</label>
                            <input type="url" name="github" id="github" value="<?php echo htmlspecialchars($user['github'] ?? ''); ?>" placeholder="https://github.com/yourusername">
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="save-profile-btn">
                        Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- PUBLISHED BLOGS GRID -->
            <h3 class="sub-section-title">My Published Stories</h3>

            <div class="my-blogs-grid">
                <?php 
                if ($myBlogsRes && mysqli_num_rows($myBlogsRes) > 0) {
                    while ($blog = mysqli_fetch_assoc($myBlogsRes)) {
                ?>
                    <article class="my-blog-card">
                        <div>
                            <a href="view_blog.php?id=<?php echo (int)$blog['id']; ?>" class="my-blog-title">
                                <?php echo htmlspecialchars($blog['title']); ?>
                            </a>
                            <div class="my-blog-date">
                                Published on <?php echo date("M d, Y", strtotime($blog['created_at'])); ?>
                            </div>
                        </div>

                        <div class="my-blog-actions">
                            <a href="edit_blog.php?id=<?php echo (int)$blog['id']; ?>" class="btn-sm-edit">Edit</a>
                            <a href="delete_blog.php?id=<?php echo (int)$blog['id']; ?>" class="btn-sm-delete" onclick="return confirm('Delete this blog?');">Delete</a>
                        </div>
                    </article>
                <?php 
                    }
                } else {
                ?>
                    <div style="grid-column: 1 / -1; padding:30px; text-align:center; background:#faf9fc; border-radius:16px; color:var(--muted);">
                        You haven't written any stories yet. <a href="create_blog.php" style="color:var(--purple); font-weight:700;">Start writing now!</a>
                    </div>
                <?php } ?>
            </div>

        </section>

    </div>

</main>

<?php include "includes/footer.php"; ?>

<script>
function toggleEditForm() {
    const sec = document.getElementById("editFormSection");
    sec.classList.toggle("show");
    if (sec.classList.contains("show")) {
        sec.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}
</script>

</body>
</html>
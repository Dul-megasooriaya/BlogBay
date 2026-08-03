<?php

session_start();
include "config.php";
include "site_config.php";

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['profile_pic']))
{
    $_SESSION['profile_pic'] = getUserProfilePic($conn, (int)$_SESSION['user_id']);
}

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: dashboard.php");
    exit();
}

$blogID = (int) $_GET['id'];
$userID = $_SESSION['user_id'];
$message = "";

$sql = "SELECT * FROM blogpost
        WHERE id = $blogID
        AND user_id = $userID";

$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) !== 1)
{
    die("Access denied or blog not found.");
}

$blog = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if(empty($title) || empty($content))
    {
        $message = "Please complete all required fields.";
    }
    else
    {
        $titleSafe = mysqli_real_escape_string($conn, $title);
        $contentSafe = mysqli_real_escape_string($conn, $content);

        $imageName = $blog['image'];

        if(
            isset($_FILES['image']) &&
            $_FILES['image']['error'] === 0
        )
        {
            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];

            if(!in_array($fileType, $allowedTypes))
            {
                $message = "Only JPG, PNG and WEBP images are allowed.";
            }
            elseif($fileSize > 5 * 1024 * 1024)
            {
                $message = "The image must be smaller than 5MB.";
            }
            else
            {
                $extension = strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $newImageName =
                    uniqid("blog_", true) . "." . $extension;

                $uploadPath = "uploads/" . $newImageName;

                if(
                    move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $uploadPath
                    )
                )
                {
                    if(
                        !empty($blog['image']) &&
                        file_exists("uploads/" . $blog['image'])
                    )
                    {
                        unlink("uploads/" . $blog['image']);
                    }

                    $imageName = $newImageName;
                }
                else
                {
                    $message = "The new image could not be uploaded.";
                }
            }
        }

        if($message === "")
        {
            $imageSafe = mysqli_real_escape_string(
                $conn,
                $imageName
            );

            $updateSQL = "UPDATE blogpost
                          SET title = '$titleSafe',
                              content = '$contentSafe',
                              image = '$imageSafe'
                          WHERE id = $blogID
                          AND user_id = $userID";

            if(mysqli_query($conn, $updateSQL))
            {
                header(
                    "Location: view_blog.php?id=" . $blogID
                );
                exit();
            }
            else
            {
                $message = "Failed to update the blog.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Blog | <?php echo htmlspecialchars($siteName); ?></title>

<link rel="stylesheet" href="css/create_blog.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/edit_blog.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<script>
    window.CURRENT_USER_ID = "<?php echo (int)$_SESSION['user_id']; ?>";
</script>

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
        <a href="reviews.php" class="mobile-nav-item">Review</a>
        <a href="profile.php" class="mobile-nav-item">Profile</a>
    </div>
</header>

<main class="page-wrapper">

    <div class="editor-header-plain">

        <h1>
            Edit your story
        </h1>

        <div class="author-card">

            <?php echo renderUserAvatar($_SESSION['username'], $_SESSION['profile_pic'] ?? null, 'author-avatar'); ?>

            <div>

                <span>Editing as</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION['username']
                    );
                    ?>
                </strong>

            </div>

        </div>

    </div>

    <?php if($message !== "") { ?>

        <div class="alert">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>

    <!-- Single Column Full-Width Edit Form -->
    <form
        method="POST"
        enctype="multipart/form-data"
        class="editor-form">

        <!-- Unified Purple Section Card -->
        <section class="editor-card unified-editor-card">

            <div class="section-heading">

                <h2>Blog Details & Content</h2>

                <p>
                    Update your title, replace your cover image, and revise your story below.
                </p>

            </div>

            <div class="form-group">

                <label for="title">
                    Blog title
                    <span>*</span>
                </label>

                <input
                    id="title"
                    type="text"
                    name="title"
                    maxlength="255"
                    value="<?php
                    echo htmlspecialchars($blog['title']);
                    ?>"
                    required
                >

                <div class="field-info">
                    <span>Use a short and descriptive title.</span>
                    <span id="titleCount">0 / 255</span>
                </div>

            </div>

            <div class="form-group">

                <label>
                    Featured image
                </label>

                <?php if(!empty($blog['image'])) { ?>

                    <div class="current-image-wrapper" style="margin-bottom:14px;">

                        <img
                            src="uploads/<?php
                            echo htmlspecialchars(
                                $blog['image']
                            );
                            ?>"
                            alt="Current featured image"
                            class="current-image"
                            style="max-height:220px; width:100%; border-radius:16px; object-fit:cover; border:1px solid rgba(255,255,255,0.3);"
                        >

                    </div>

                <?php } ?>

                <label for="image" class="upload-box">

                    <input
                        id="image"
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <div id="uploadPrompt" class="upload-prompt">

                        <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>

                        <h3>Choose a new cover image</h3>

                        <p>
                            Leave this empty to keep the current image.
                        </p>

                        <small>
                            JPG, PNG or WEBP · Maximum 5MB
                        </small>

                    </div>

                    <img
                        id="imagePreview"
                        class="image-preview"
                        alt="New featured image preview"
                    >

                </label>

            </div>

            <div class="form-group">

                <label for="content">
                    Your story
                    <span>*</span>
                </label>

                <textarea
                    id="content"
                    name="content"
                    rows="16"
                    required
                ><?php
                echo htmlspecialchars($blog['content']);
                ?></textarea>

                <div class="field-info">
                    <span>Your line breaks will be preserved.</span>
                    <span id="contentCount">0 characters</span>
                </div>

            </div>

        </section>

        <div class="form-actions">

            <a
                href="view_blog.php?id=<?php echo $blogID; ?>"
                class="cancel-btn">

                Cancel

            </a>

            <button
                type="submit"
                name="update"
                class="update-btn">

                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes

            </button>

        </div>

    </form>

</main>

<script>

const titleInput = document.getElementById("title");
const titleCount = document.getElementById("titleCount");

const contentInput = document.getElementById("content");
const contentCount = document.getElementById("contentCount");

const imageInput = document.getElementById("image");
const imagePreview = document.getElementById("imagePreview");
const uploadPrompt = document.getElementById("uploadPrompt");

function updateTitleCount()
{
    if (titleInput && titleCount) {
        titleCount.textContent =
            titleInput.value.length + " / 255";
    }
}

function updateContentCount()
{
    if (contentInput && contentCount) {
        contentCount.textContent =
            contentInput.value.length + " characters";
    }
}

if (titleInput) {
    titleInput.addEventListener("input", updateTitleCount);
    updateTitleCount();
}

if (contentInput) {
    contentInput.addEventListener("input", updateContentCount);
    updateContentCount();
}

if (imageInput) {
    imageInput.addEventListener("change", function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                if (imagePreview) {
                    imagePreview.src = event.target.result;
                    imagePreview.style.display = "block";
                }
                if (uploadPrompt) {
                    uploadPrompt.style.display = "none";
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

</script>

<?php include "includes/footer.php"; ?>

</body>

</html>
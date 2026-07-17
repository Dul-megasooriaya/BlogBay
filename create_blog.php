<?php

session_start();

include "config.php";
include "site_config.php";

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

$message = "";

if(isset($_POST['publish']))
{
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $userID = (int) $_SESSION['user_id'];

    if($title === "" || $content === "")
    {
        $message = "Please complete all required fields.";
    }
    elseif(
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] !== UPLOAD_ERR_OK
    )
    {
        $message = "Please select a featured image.";
    }
    else
    {
        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        $fileType = mime_content_type(
            $_FILES['image']['tmp_name']
        );

        $fileSize = $_FILES['image']['size'];

        if(!in_array($fileType, $allowedTypes, true))
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

            $imageName =
                uniqid("blog_", true) . "." . $extension;

            $uploadPath =
                __DIR__ . "/uploads/" . $imageName;

            if(
                move_uploaded_file(
                    $_FILES['image']['tmp_name'],
                    $uploadPath
                )
            )
            {
                $titleSafe =
                    mysqli_real_escape_string(
                        $conn,
                        $title
                    );

                $contentSafe =
                    mysqli_real_escape_string(
                        $conn,
                        $content
                    );

                $imageSafe =
                    mysqli_real_escape_string(
                        $conn,
                        $imageName
                    );

                $sql = "INSERT INTO blogpost
                        (user_id, title, content, image)
                        VALUES
                        (
                            $userID,
                            '$titleSafe',
                            '$contentSafe',
                            '$imageSafe'
                        )";

                if(mysqli_query($conn, $sql))
                {
                    header("Location: dashboard.php");
                    exit();
                }

                $message = "The blog could not be published.";
            }
            else
            {
                $message = "The image could not be uploaded.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Create Blog | <?php echo htmlspecialchars($siteName); ?>
    </title>

    <link rel="stylesheet"
          href="css/create_blog.css?v=31">

</head>

<body>

<header class="topbar">

    <a href="dashboard.php"
       class="brand">

        <img class="brand-logo-image" src="images/logo.png" alt="BlogBay logo" width="42" height="42">
        <?php echo renderSiteName(); ?>

    </a>

    <a href="dashboard.php"
       class="back-link">

        Back to Dashboard

    </a>

</header>

<main class="page-wrapper">

    <section class="editor-header">

        <div>

            <h1>
                Write and publish
            </h1>

            <p class="subtitle">
                Add a title, choose a cover image and share
                your story with readers.
            </p>

        </div>

        <div class="author-card">

            <div class="author-avatar">

                <?php
                echo strtoupper(
                    substr($_SESSION['username'], 0, 1)
                );
                ?>

            </div>

            <div>

                <span>Publishing as</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION['username']
                    );
                    ?>
                </strong>

            </div>

        </div>

    </section>

    <?php if($message !== "") { ?>

        <div class="alert">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>

    <form method="POST"
          enctype="multipart/form-data"
          class="editor-form">

        <section class="editor-card details-card">

            <div class="section-heading">

                <h2>Blog details</h2>

                <p>
                    Add a short title and a clear featured image.
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
                    placeholder="Enter an engaging blog title"
                    value="<?php
                    echo isset($_POST['title'])
                        ? htmlspecialchars($_POST['title'])
                        : '';
                    ?>"
                    required
                >

                <div class="field-info">

                    <span>
                        Keep your title clear and descriptive.
                    </span>

                    <span id="titleCount">
                        0 / 255
                    </span>

                </div>

            </div>

            <div class="form-group">

                <label>

                    Featured image
                    <span>*</span>

                </label>

                <label for="image"
                       class="upload-box">

                    <input
                        id="image"
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                        required
                    >

                    <div id="uploadPrompt"
                         class="upload-prompt">

                        <h3>
                            Choose a cover image
                        </h3>

                        <p>
                            Click this area to select an image.
                        </p>

                        <small>
                            JPG, PNG or WEBP · Maximum 5MB
                        </small>

                    </div>

                    <img
                        id="imagePreview"
                        class="image-preview"
                        alt="Featured image preview"
                    >

                </label>

            </div>

        </section>

        <section class="editor-card content-card">

            <div class="section-heading">

                <h2>Blog content</h2>

                <p>
                    Write your article using clear paragraphs.
                </p>

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
                    placeholder="Start writing your story..."
                    required
                ><?php
                echo isset($_POST['content'])
                    ? htmlspecialchars($_POST['content'])
                    : '';
                ?></textarea>

                <div class="field-info">

                    <span>
                        Your line breaks will be preserved.
                    </span>

                    <span id="contentCount">
                        0 characters
                    </span>

                </div>

            </div>

        </section>

        <div class="form-actions">

            <a href="dashboard.php"
               class="cancel-btn">

                Cancel

            </a>

            <button
                type="submit"
                name="publish"
                class="publish-btn">

                Publish Blog

            </button>

        </div>

    </form>

</main>

<script>

const titleInput =
    document.getElementById("title");

const titleCount =
    document.getElementById("titleCount");

const contentInput =
    document.getElementById("content");

const contentCount =
    document.getElementById("contentCount");

const imageInput =
    document.getElementById("image");

const imagePreview =
    document.getElementById("imagePreview");

const uploadPrompt =
    document.getElementById("uploadPrompt");

function updateTitleCount()
{
    titleCount.textContent =
        titleInput.value.length + " / 255";
}

function updateContentCount()
{
    contentCount.textContent =
        contentInput.value.length + " characters";
}

titleInput.addEventListener(
    "input",
    updateTitleCount
);

contentInput.addEventListener(
    "input",
    updateContentCount
);

updateTitleCount();
updateContentCount();

imageInput.addEventListener(
    "change",
    function()
    {
        const file = this.files[0];

        if(!file)
        {
            return;
        }

        const reader = new FileReader();

        reader.onload = function(event)
        {
            imagePreview.src = event.target.result;
            imagePreview.style.display = "block";
            uploadPrompt.style.display = "none";
        };

        reader.readAsDataURL(file);
    }
);

</script>

</body>

</html>
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

$message = "";
$messageType = "";

$sql = "SELECT id, username, email, role
        FROM user
        WHERE id = $userID
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) !== 1)
{
    session_destroy();

    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

if(isset($_POST['update_profile']))
{
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if($username === "" || $email === "")
    {
        $message = "Username and email are required.";
        $messageType = "error";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $message = "Enter a valid email address.";
        $messageType = "error";
    }
    else
    {
        $usernameSafe =
            mysqli_real_escape_string(
                $conn,
                $username
            );

        $emailSafe =
            mysqli_real_escape_string(
                $conn,
                $email
            );

        $checkSQL =
            "SELECT id
             FROM user
             WHERE email = '$emailSafe'
             AND id != $userID
             LIMIT 1";

        $checkResult =
            mysqli_query(
                $conn,
                $checkSQL
            );

        if($checkResult && mysqli_num_rows($checkResult) > 0)
        {
            $message = "This email is already in use.";
            $messageType = "error";
        }
        else
        {
            $updateSQL =
                "UPDATE user
                 SET username = '$usernameSafe',
                     email = '$emailSafe'
                 WHERE id = $userID";

            if(mysqli_query($conn, $updateSQL))
            {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;

                $user['username'] = $username;
                $user['email'] = $email;

                $message = "Profile updated successfully.";
                $messageType = "success";
            }
            else
            {
                $message = "Profile update failed.";
                $messageType = "error";
            }
        }
    }
}

$countResult =
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM blogpost
         WHERE user_id = $userID"
    );

$countData = mysqli_fetch_assoc($countResult);
$myBlogCount = (int) $countData['total'];

$latestResult =
    mysqli_query(
        $conn,
        "SELECT title, created_at
         FROM blogpost
         WHERE user_id = $userID
         ORDER BY created_at DESC
         LIMIT 1"
    );

$latestBlog =
    $latestResult && mysqli_num_rows($latestResult) === 1
        ? mysqli_fetch_assoc($latestResult)
        : null;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Profile | <?php echo htmlspecialchars($siteName); ?>
</title>

<link rel="stylesheet"
href="css/profile.css?v=10">

<link rel="stylesheet"
href="css/footer.css?v=4">

</head>

<body>

<header class="topbar">

    <a href="dashboard.php"
       class="brand">

        <img
            src="images/logo.png"
            alt="<?php echo htmlspecialchars($siteName); ?> logo"
        >

        <?php echo renderSiteName(); ?>

    </a>

    <a href="dashboard.php"
       class="back-btn">

        Back to Dashboard

    </a>

</header>

<main class="profile-wrapper">

    <section class="profile-banner">

        <div class="large-avatar">

            <?php
            echo strtoupper(
                substr($user['username'], 0, 1)
            );
            ?>

        </div>

        <div>

            <h1>
                <?php
                echo htmlspecialchars(
                    $user['username']
                );
                ?>
            </h1>

            <p>
                <?php
                echo htmlspecialchars(
                    $user['email']
                );
                ?>
            </p>

        </div>

    </section>

    <section class="profile-grid">

        <div class="profile-card">

            <h2>Edit Profile</h2>

            <p class="card-description">
                Update your personal account information.
            </p>

            <?php if($message !== "") { ?>

                <div class="profile-message <?php echo $messageType; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php } ?>

            <form method="POST">

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        id="username"
                        type="text"
                        name="username"
                        maxlength="50"
                        value="<?php
                        echo htmlspecialchars(
                            $user['username']
                        );
                        ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        maxlength="100"
                        value="<?php
                        echo htmlspecialchars(
                            $user['email']
                        );
                        ?>"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="update_profile"
                    class="save-btn">

                    Save Changes

                </button>

            </form>

        </div>

        <aside class="account-column">

            <div class="info-card cyan-card">

                <span>Published blogs</span>

                <strong>
                    <?php echo $myBlogCount; ?>
                </strong>

            </div>

            <div class="info-card periwinkle-card">

                <span>Account role</span>

                <strong>
                    <?php
                    echo ucfirst(
                        htmlspecialchars(
                            $user['role']
                        )
                    );
                    ?>
                </strong>

            </div>

            <div class="info-card purple-card">

                <span>Latest story</span>

                <strong class="latest-title">

                    <?php
                    echo $latestBlog
                        ? htmlspecialchars($latestBlog['title'])
                        : "No blogs yet";
                    ?>

                </strong>

                <?php if($latestBlog) { ?>

                    <small>

                        <?php
                        echo date(
                            "M d, Y",
                            strtotime($latestBlog['created_at'])
                        );
                        ?>

                    </small>

                <?php } ?>

            </div>

            <a href="create_blog.php"
               class="create-btn">

                Create New Blog

            </a>

            <a href="logout.php"
               class="logout-btn"
               onclick="return confirm('Are you sure you want to log out?')">

                Log Out

            </a>

        </aside>

    </section>

</main>

<?php include "includes/footer.php"; ?>

</body>

</html>
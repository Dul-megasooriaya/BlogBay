<?php
include "config.php";
include "includes/session_manager.php";
include "site_config.php";

// Check session
checkRememberMeCookie($conn);

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
if (isset($_SESSION['session_timeout_msg'])) {
    $message = $_SESSION['session_timeout_msg'];
    unset($_SESSION['session_timeout_msg']);
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $emailSafe = mysqli_real_escape_string($conn, $email);

    $sql = "SELECT * FROM user WHERE email = '$emailSafe' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_pic'] = getUserProfilePic($conn, (int)$user['id']);
            $_SESSION['last_activity'] = time();

            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                setRememberMeCookie($conn, (int)$user['id']);
            }

            header("Location: dashboard.php");
            exit();
        }
    }

    $message = "Incorrect email or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo htmlspecialchars($siteName); ?></title>

    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<div class="login-layout">

    <section class="login-image-panel">
        <div class="login-image-overlay"></div>

        <div class="image-brand">
            <?php echo renderSiteLogo(); ?>
            <?php echo renderSiteName(); ?>
        </div>

        <div class="image-text">
            <h1>Write freely.<br>Share beautifully.</h1>
            <span>Turn your thoughts and experiences into stories worth sharing.</span>
        </div>
    </section>

    <section class="login-form-panel">
        <div class="login-card">
            <h2>Welcome Back</h2>
            <p class="login-description">Sign in to continue to your writing dashboard.</p>

            <?php if ($message !== "") { ?>
                <div class="login-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-group remember-group" style="display:flex; align-items:center; gap:8px; margin:16px 0 20px 0;">
                    <input id="remember_me" type="checkbox" name="remember_me" value="1" style="width:16px; height:16px; accent-color:var(--purple); cursor:pointer;">
                    <label for="remember_me" style="font-size:12px; color:#514b63; cursor:pointer; user-select:none; margin:0; font-weight:600;">
                        Remember me on this device (30 days)
                    </label>
                </div>

                <button type="submit" name="login" class="login-button">
                    Sign In
                </button>
            </form>

            <p class="register-text">
                New to <?php echo htmlspecialchars($siteName); ?>?
                <a href="register.php">Create an account</a>
            </p>
        </div>
    </section>

</div>

<?php include "includes/footer.php"; ?>

</body>
</html>
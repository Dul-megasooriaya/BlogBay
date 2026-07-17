<?php

session_start();

include "config.php";
include "site_config.php";

if(isset($_SESSION['user_id']))
{
    header("Location: dashboard.php");
    exit();
}

$message = "";

if(isset($_POST['register']))
{
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if($username === "" || $email === "" || $password === "")
    {
        $message = "Please complete all required fields.";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $message = "Please enter a valid email address.";
    }
    elseif(strlen($password) < 6)
    {
        $message = "Password must contain at least 6 characters.";
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
             LIMIT 1";

        $checkResult =
            mysqli_query(
                $conn,
                $checkSQL
            );

        if($checkResult && mysqli_num_rows($checkResult) > 0)
        {
            $message = "That email address is already registered.";
        }
        else
        {
            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $passwordSafe =
                mysqli_real_escape_string(
                    $conn,
                    $hashedPassword
                );

            $sql =
                "INSERT INTO user
                (username, email, password, role)
                VALUES
                (
                    '$usernameSafe',
                    '$emailSafe',
                    '$passwordSafe',
                    'user'
                )";

            if(mysqli_query($conn, $sql))
            {
                header("Location: login.php");
                exit();
            }
            else
            {
                $message = "Registration failed. Please try again.";
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
    Register | <?php echo htmlspecialchars($siteName); ?>
</title>

<link rel="stylesheet"
href="css/register.css?v=1">

</head>

<body>

<div class="register-layout">

    <section class="register-image-panel">

        <div class="register-image-overlay"></div>

        <div class="image-brand">

            <img
                src="images/logo.png"
                alt="BlogBay logo"
                class="brand-image"
            >

            <?php echo renderSiteName(); ?>

        </div>

        <div class="image-text">

            <p>BEGIN YOUR WRITING JOURNEY</p>

            <h1>
                Create stories.<br>
                Share your voice.
            </h1>

            <span>
                Join the community and start publishing
                meaningful ideas and experiences.
            </span>

        </div>

    </section>

    <section class="register-form-panel">

        <div class="register-card">

            <p class="register-label">
                CREATE ACCOUNT
            </p>

            <h2>
                Join <?php echo htmlspecialchars($siteName); ?>
            </h2>

            <p class="register-description">
                Enter your details to create your writer account.
            </p>

            <?php if($message !== "") { ?>

                <div class="register-message">

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
                        placeholder="Choose a username"
                        value="<?php
                        echo isset($_POST['username'])
                            ? htmlspecialchars($_POST['username'])
                            : '';
                        ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="email">
                        Email address
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        maxlength="100"
                        placeholder="Enter your email"
                        value="<?php
                        echo isset($_POST['email'])
                            ? htmlspecialchars($_POST['email'])
                            : '';
                        ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    name="register"
                    class="register-button">

                    Create Account

                </button>

            </form>

            <p class="login-text">

                Already have an account?

                <a href="login.php">
                    Sign in
                </a>

            </p>

        </div>

    </section>

</div>

</body>

</html>
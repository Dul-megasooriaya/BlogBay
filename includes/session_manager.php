<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function checkSessionTimeout(): void
{
    $maxIdleTime = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxIdleTime)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['session_timeout_msg'] = "Your session expired due to inactivity. Please log in again.";
    }
    $_SESSION['last_activity'] = time();
}

checkSessionTimeout();

if (!function_exists('getUserProfilePic')) {
    function getUserProfilePic($conn, int $userId): ?string
    {
        if ($userId <= 0 || !$conn) return null;
        $res = mysqli_query($conn, "SELECT profile_pic FROM user_profiles WHERE user_id = $userId LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return $row['profile_pic'];
        }
        return null;
    }
}

function checkRememberMeCookie($conn): void
{
    if (isset($_SESSION['user_id'])) {
        return;
    }

    if (!empty($_COOKIE['blogbay_remember']) && $conn) {
        $cookieToken = $_COOKIE['blogbay_remember'];
        $tokenParts = explode(':', $cookieToken);
        if (count($tokenParts) === 2) {
            $userId = (int) $tokenParts[0];
            $tokenRaw = $tokenParts[1];

            $res = mysqli_query($conn, "SELECT id, username, email, remember_token FROM user WHERE id = $userId LIMIT 1");
            if ($res && $user = mysqli_fetch_assoc($res)) {
                if (!empty($user['remember_token']) && password_verify($tokenRaw, $user['remember_token'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['profile_pic'] = getUserProfilePic($conn, (int)$user['id']);
                    $_SESSION['last_activity'] = time();
                } else {
                    setcookie('blogbay_remember', '', time() - 3600, '/');
                }
            }
        }
    }
}

function setRememberMeCookie($conn, int $userId): void
{
    if ($userId <= 0 || !$conn) return;

    $rawToken = bin2hex(random_bytes(32));
    $hashedToken = password_hash($rawToken, PASSWORD_DEFAULT);
    $hashedTokenSafe = mysqli_real_escape_string($conn, $hashedToken);

    mysqli_query($conn, "UPDATE user SET remember_token = '$hashedTokenSafe' WHERE id = $userId");

    $cookieValue = $userId . ':' . $rawToken;
    $expireTime = time() + (30 * 24 * 60 * 60);

    setcookie('blogbay_remember', $cookieValue, [
        'expires' => $expireTime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clearRememberMeCookie($conn, int $userId = 0): void
{
    if ($userId > 0 && $conn) {
        mysqli_query($conn, "UPDATE user SET remember_token = NULL WHERE id = $userId");
    }
    setcookie('blogbay_remember', '', time() - 3600, '/');
}


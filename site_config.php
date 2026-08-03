<?php

$siteName = "BlogBay";

function renderSiteName(): string
{
    return '<span class="site-name"><span class="site-name-blog">Blog</span><span class="site-name-bay">Bay</span></span>';
}

function renderSiteLogo(): string
{
    $possibleFiles = [
        'images/logo.png',
        'images/logo.jpg',
        'images/logo.jpeg',
        'images/logo.webp',
        'images/logo.svg',
        'images/blog.jpg'
    ];

    foreach ($possibleFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $mtime = filemtime($fullPath);
            return '<img src="' . $file . '?v=' . $mtime . '" alt="BlogBay logo" class="brand-logo-img" style="width:38px; height:38px; object-fit:cover; border-radius:10px; flex-shrink:0;">';
        }
    }

    return '<span class="logo-icon-badge" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, var(--purple), var(--magenta)); display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:18px; flex-shrink:0;"><i class="fa-solid fa-feather-pointed"></i></span>';
}

function getUserProfilePic($conn, int $userId): ?string
{
    if ($userId <= 0 || !$conn) return null;
    $res = mysqli_query($conn, "SELECT profile_pic FROM user_profiles WHERE user_id = $userId LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row['profile_pic'];
    }
    return null;
}

function renderUserAvatar(?string $username, ?string $profilePic, string $containerClass = 'author-avatar'): string
{
    $name = !empty($username) ? $username : 'U';
    $letter = htmlspecialchars(strtoupper(substr($name, 0, 1)));
    $classAttr = htmlspecialchars($containerClass);
    
    if (!empty($profilePic)) {
        $uploadFile = __DIR__ . '/uploads/' . $profilePic;
        if (file_exists($uploadFile)) {
            $imgSrc = 'uploads/' . htmlspecialchars($profilePic);
            $altAttr = htmlspecialchars($name);
            return '<div class="' . $classAttr . '"><img src="' . $imgSrc . '" alt="' . $altAttr . '"></div>';
        }
    }
    
    return '<div class="' . $classAttr . '">' . $letter . '</div>';
}

?>

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

function renderHeroHeader(string $activeNav = '', string $searchVal = '', bool $isSolid = false): string
{
    $dashActive = ($activeNav === 'dashboard') ? 'active' : '';
    $blogsActive = ($activeNav === 'blogs') ? 'active' : '';
    $reviewActive = ($activeNav === 'review') ? 'active' : '';
    $profileActive = ($activeNav === 'profile') ? 'active' : '';
    $solidClass = $isSolid ? 'hero-navbar-solid' : '';

    return '
    <header class="hero-navbar ' . $solidClass . '">
        <div class="hero-navbar-inner">
            <a href="dashboard.php" class="hero-brand">
                ' . renderSiteLogo() . '
                ' . renderSiteName() . '
            </a>
            <nav class="hero-nav-links">
                <a href="dashboard.php" class="hero-nav-item ' . $dashActive . '">Dashboard</a>
                <a href="dashboard.php#blogGrid" class="hero-nav-item ' . $blogsActive . '">Blogs</a>
                <a href="reviews.php" class="hero-nav-item ' . $reviewActive . '">Review</a>
                <a href="profile.php" class="hero-nav-item ' . $profileActive . '">Profile</a>
            </nav>
            <div class="hero-search-box" style="position: relative; display: flex; align-items: center; background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; border-radius: 999px !important; padding: 6px 16px !important; width: 210px !important;">
                <i class="fa-solid fa-magnifying-glass" style="color: rgba(255, 255, 255, 0.8) !important; margin-right: 8px !important; font-size: 13px !important;"></i>
                <input id="blogSearch" type="text" placeholder="Search..." style="background: transparent !important; border: none !important; outline: none !important; color: #ffffff !important; font-size: 13px !important; width: 100% !important; box-shadow: none !important; padding: 0 !important; margin: 0 !important; height: auto !important;" value="' . htmlspecialchars($searchVal) . '" onkeydown="if(event.key===\'Enter\'){ window.location.href=\'dashboard.php?search=\'+encodeURIComponent(this.value); }">
            </div>
            <button type="button" class="mobile-menu-toggle" onclick="toggleMobileMenu(this)" title="Toggle navigation menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="mobile-dropdown-menu" id="mobileDropdownMenu">
            <a href="dashboard.php" class="mobile-nav-item ' . $dashActive . '">Dashboard</a>
            <a href="dashboard.php#blogGrid" class="mobile-nav-item ' . $blogsActive . '">Blogs</a>
            <a href="reviews.php" class="mobile-nav-item ' . $reviewActive . '">Review</a>
            <a href="profile.php" class="mobile-nav-item ' . $profileActive . '">Profile</a>
        </div>
    </header>';
}

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

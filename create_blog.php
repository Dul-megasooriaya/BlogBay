<?php
include "config.php";
include "includes/session_manager.php";
include "site_config.php";

// Verify persistent remember cookie & session authentication
checkRememberMeCookie($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['profile_pic'])) {
    $_SESSION['profile_pic'] = getUserProfilePic($conn, (int)$_SESSION['user_id']);
}

$message = "";
$createdSuccess = false;
$createdBlogID = 0;
$createdBlogTitle = "";

// Process blog creation submission
if (isset($_POST['publish'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $userID = (int) $_SESSION['user_id'];

    if ($title === "" || $content === "") {
        $message = "Please complete all required fields.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please select a featured image.";
    } else {
        $allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        $fileSize = $_FILES['image']['size'];

        if (!in_array($fileType, $allowedTypes, true)) {
            $message = "Only JPG, PNG and WEBP images are allowed.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $message = "The image must be smaller than 5MB.";
        } else {
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $imageName = uniqid("blog_", true) . "." . $extension;
            $uploadPath = __DIR__ . "/uploads/" . $imageName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $titleSafe = mysqli_real_escape_string($conn, $title);
                $contentSafe = mysqli_real_escape_string($conn, $content);
                $imageSafe = mysqli_real_escape_string($conn, $imageName);

                $sql = "INSERT INTO blogpost (user_id, title, content, image) VALUES ($userID, '$titleSafe', '$contentSafe', '$imageSafe')";

                if (mysqli_query($conn, $sql)) {
                    $createdBlogID = mysqli_insert_id($conn);
                    $createdBlogTitle = $title;
                    $createdSuccess = true;
                } else {
                    $message = "The blog could not be published.";
                }
            } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Blog | <?php echo htmlspecialchars($siteName); ?></title>

    <link rel="stylesheet" href="css/create_blog.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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
        <h1>Write something new</h1>
        <div class="author-card">
            <?php echo renderUserAvatar($_SESSION['username'], $_SESSION['profile_pic'] ?? null, 'author-avatar'); ?>
            <div>
                <span>Publishing as</span>
                <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </div>
        </div>
    </div>

    <?php if ($message !== "") { ?>
        <div class="alert">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div class="create-layout-grid">

        <div class="create-main-col">
            <form method="POST" enctype="multipart/form-data" class="editor-form">
                <section class="editor-card unified-editor-card">
                    <div class="section-heading">
                        <h2>Blog Details & Content</h2>
                        <p>Add a title, upload a featured cover image, and write your story below.</p>
                    </div>

                    <div class="form-group">
                        <label for="title">Blog title <span>*</span></label>
                        <input id="title" type="text" name="title" maxlength="255" placeholder="Enter an engaging blog title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                        <div class="field-info">
                            <span>Keep your title clear and descriptive.</span>
                            <span id="titleCount">0 / 255</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Featured image <span>*</span></label>
                        <label for="image" class="upload-box">
                            <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
                            <div id="uploadPrompt" class="upload-prompt">
                                <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                                <h3>Choose a cover image</h3>
                                <p>Click this area to select an image.</p>
                                <small>JPG, PNG or WEBP · Maximum 5MB</small>
                            </div>
                            <img id="imagePreview" class="image-preview" alt="Featured image preview">
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="content">Your story <span>*</span></label>
                        <textarea id="content" name="content" rows="16" placeholder="Start writing your story..." required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <div class="field-info">
                            <span>Your line breaks will be preserved.</span>
                            <span id="contentCount">0 characters</span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <a href="dashboard.php" class="cancel-btn">Cancel</a>
                    <button type="submit" name="publish" class="publish-btn">
                        <i class="fa-solid fa-paper-plane"></i> Publish Blog
                    </button>
                </div>
            </form>
        </div>

        <aside class="create-sidebar-col">

            <div class="widget-card clock-widget">
                <div class="clock-display-box">
                    <span id="clockTime" class="clock-time">00:00</span>
                </div>
                <div class="clock-date-box">
                    <div id="clockDay" class="clock-day-name">Monday</div>
                    <div id="clockDate" class="clock-full-date">2026/08/03</div>
                </div>
            </div>

            <div class="widget-card calendar-widget">
                <div class="calendar-header">
                    <span id="calMonthNum" class="cal-month-num">8</span>
                    <span class="cal-title">CALENDAR</span>
                </div>
                <div class="calendar-days-bar">
                    <span>Sun</span><span>Mon</span><span>Tues</span><span>Wed</span><span>Thur</span><span>Fri</span><span>Sat</span>
                </div>
                <div id="calendarGrid" class="calendar-grid">
                </div>
            </div>

            <div class="widget-card notes-widget">
                <div class="notes-header">
                    <div class="notes-header-left">
                        <i class="fa-solid fa-note-sticky notes-icon"></i>
                        <h3>Sticky Notes</h3>
                    </div>
                    <button type="button" id="addStickyBtn" class="add-note-btn" title="Add Sticky Note">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>

                <p class="sticky-hint">Click any note to type. Changes save automatically.</p>

                <div class="sticky-color-palette" id="stickyColorPalette" style="display:flex; align-items:center; gap:8px; margin-bottom:14px; padding:8px 12px; background:rgba(248, 246, 252, 0.7); border-radius:12px;">
                    <span class="palette-label" style="font-size:10px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px;">Pack:</span>
                    <button type="button" class="color-pill active" data-color="theme-yellow" style="width:22px; height:22px; border-radius:50%; background:#ffef75; border:1px solid #e8cb28; cursor:pointer;" onclick="selectStickyTheme('theme-yellow', this)" title="Classic Yellow"></button>
                    <button type="button" class="color-pill" data-color="theme-purple" style="width:22px; height:22px; border-radius:50%; background:#d8b4fe; border:1px solid #be8cf7; cursor:pointer;" onclick="selectStickyTheme('theme-purple', this)" title="Rich Lavender"></button>
                    <button type="button" class="color-pill" data-color="theme-green" style="width:22px; height:22px; border-radius:50%; background:#7afcff; border:1px solid #48e5e8; cursor:pointer;" onclick="selectStickyTheme('theme-green', this)" title="Electric Mint"></button>
                    <button type="button" class="color-pill" data-color="theme-blue" style="width:22px; height:22px; border-radius:50%; background:#70d6ff; border:1px solid #3cbbe8; cursor:pointer;" onclick="selectStickyTheme('theme-blue', this)" title="Sky Blue"></button>
                    <button type="button" class="color-pill" data-color="theme-orange" style="width:22px; height:22px; border-radius:50%; background:#ff9e9e; border:1px solid #f57878; cursor:pointer;" onclick="selectStickyTheme('theme-orange', this)" title="Coral Orange"></button>
                    <button type="button" class="color-pill" data-color="theme-pink" style="width:22px; height:22px; border-radius:50%; background:#ff7eb9; border:1px solid #f4569c; cursor:pointer;" onclick="selectStickyTheme('theme-pink', this)" title="Vibrant Pink"></button>
                </div>

                <div id="stickyNotesGrid" class="sticky-notes-grid">
                </div>
            </div>

        </aside>

    </div>

</main>

<?php if ($createdSuccess) { ?>
<div class="congratulations-modal-overlay active" id="successModal">
    <div class="congratulations-card">
        <div class="confetti-container">
            <div class="confetti-piece p1"></div>
            <div class="confetti-piece p2"></div>
            <div class="confetti-piece p3"></div>
            <div class="confetti-piece p4"></div>
            <div class="confetti-piece p5"></div>
            <div class="confetti-piece p6"></div>
            <div class="confetti-piece p7"></div>
            <div class="confetti-piece p8"></div>
        </div>

        <div class="success-icon-badge">
            <i class="fa-solid fa-trophy"></i>
        </div>

        <h2>Congratulations! 🎉</h2>
        <p class="success-subtitle">You have successfully created and published your blog post!</p>
        
        <div class="success-blog-title">
            "<?php echo htmlspecialchars($createdBlogTitle); ?>"
        </div>

        <div class="success-modal-actions">
            <a href="view_blog.php?id=<?php echo $createdBlogID; ?>" class="btn-success-primary">
                <i class="fa-solid fa-eye"></i> View Created Blog
            </a>
            <a href="dashboard.php" class="btn-success-secondary">
                <i class="fa-solid fa-house"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
<?php } ?>

<?php include "includes/footer.php"; ?>

<script>
const titleInput = document.getElementById("title");
const titleCount = document.getElementById("titleCount");
const contentInput = document.getElementById("content");
const contentCount = document.getElementById("contentCount");
const imageInput = document.getElementById("image");
const uploadPrompt = document.getElementById("uploadPrompt");
const imagePreview = document.getElementById("imagePreview");

function updateTitleCount() {
    if (titleInput && titleCount) {
        titleCount.textContent = titleInput.value.length + " / 255";
    }
}

function updateContentCount() {
    if (contentInput && contentCount) {
        contentCount.textContent = contentInput.value.length + " characters";
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

            reader.onload = function(e) {
                if (imagePreview) {
                    imagePreview.src = e.target.result;
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

// REAL-TIME CLOCK WIDGET LOGIC
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const timeEl = document.getElementById('clockTime');
    if (timeEl) timeEl.textContent = `${hours}:${minutes}`;

    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayName = days[now.getDay()];
    const dayEl = document.getElementById('clockDay');
    if (dayEl) dayEl.textContent = dayName;

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');
    const dateEl = document.getElementById('clockDate');
    if (dateEl) dateEl.textContent = `${year}/${month}/${date}`;
}

setInterval(updateClock, 1000);
updateClock();

// MONTHLY CALENDAR WIDGET LOGIC
function generateCalendar() {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    const currentDate = now.getDate();

    const monthNumEl = document.getElementById('calMonthNum');
    if (monthNumEl) monthNumEl.textContent = currentMonth + 1;

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    const gridEl = document.getElementById('calendarGrid');
    if (!gridEl) return;

    gridEl.innerHTML = '';

    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'cal-day empty';
        gridEl.appendChild(emptyCell);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'cal-day';
        if (day === currentDate) dayCell.classList.add('today');
        dayCell.textContent = day;
        gridEl.appendChild(dayCell);
    }
}

generateCalendar();

// INTERACTIVE STICKY NOTES LOGIC
const STICKY_STORAGE_KEY = 'blogbay_sticky_notes_' + (window.CURRENT_USER_ID || '0');
let currentSelectedTheme = 'theme-yellow';

function selectStickyTheme(theme, btnEl) {
    currentSelectedTheme = theme;
    document.querySelectorAll('#stickyColorPalette .color-pill').forEach(btn => {
        btn.style.boxShadow = 'none';
        btn.classList.remove('active');
    });
    if (btnEl) {
        btnEl.classList.add('active');
        btnEl.style.boxShadow = '0 0 0 2px var(--purple)';
    }
}

function getStickyNotes() {
    try {
        const stored = localStorage.getItem(STICKY_STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) return parsed;
        }
    } catch(e) {}
    return [{ id: 1, text: "", theme: "theme-yellow" }];
}

function saveStickyNotes(notes) {
    try {
        localStorage.setItem(STICKY_STORAGE_KEY, JSON.stringify(notes));
    } catch(e) {}
}

function renderStickyNotes() {
    const gridEl = document.getElementById('stickyNotesGrid');
    if (!gridEl) return;

    const notes = getStickyNotes();
    gridEl.innerHTML = '';

    if (notes.length === 0) {
        gridEl.innerHTML = '<div class="empty-sticky-notes" style="border: 1px dashed rgba(147, 104, 184, 0.4); background: rgba(248, 246, 252, 0.6); border-radius: 16px; padding: 24px 16px; text-align: center; font-size: 12px; font-weight: 500; color: var(--muted); line-height: 1.5;">No sticky notes yet. Click <strong style="color:var(--purple); font-size:14px; cursor:pointer;" onclick="addStickyNote()">+</strong> above to create one!</div>';
        return;
    }

    notes.forEach(note => {
        const card = document.createElement('div');
        card.className = `sticky-note-card ${note.theme || 'theme-yellow'}`;
        card.dataset.id = note.id;

        const header = document.createElement('div');
        header.className = 'sticky-card-header';
        
        const pinIcon = document.createElement('i');
        pinIcon.className = 'fa-solid fa-thumbtack sticky-pin';

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'delete-sticky-btn';
        delBtn.title = 'Delete note';
        delBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        delBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            deleteStickyNote(note.id);
        });

        header.appendChild(pinIcon);
        header.appendChild(delBtn);

        const textarea = document.createElement('textarea');
        textarea.className = 'sticky-textarea';
        textarea.placeholder = 'Click here to write note...';
        textarea.value = note.text || '';
        
        textarea.addEventListener('input', function() {
            updateStickyNoteText(note.id, this.value);
        });

        const footer = document.createElement('div');
        footer.className = 'sticky-card-footer';
        footer.textContent = 'Auto-saved';

        card.appendChild(header);
        card.appendChild(textarea);
        card.appendChild(footer);
        gridEl.appendChild(card);
    });
}

function updateStickyNoteText(id, text) {
    let notes = getStickyNotes();
    const idx = notes.findIndex(n => n.id === id);
    if (idx !== -1) {
        notes[idx].text = text;
        saveStickyNotes(notes);
    }
}

function addStickyNote() {
    let notes = getStickyNotes();
    const newId = Date.now();
    
    notes.unshift({
        id: newId,
        text: "",
        theme: currentSelectedTheme || 'theme-yellow'
    });
    
    saveStickyNotes(notes);
    renderStickyNotes();

    setTimeout(() => {
        const card = document.querySelector(`.sticky-note-card[data-id="${newId}"]`);
        if (card) {
            const ta = card.querySelector('.sticky-textarea');
            if (ta) ta.focus();
        }
    }, 50);
}

function deleteStickyNote(id) {
    let notes = getStickyNotes();
    notes = notes.filter(n => n.id !== id);
    saveStickyNotes(notes);
    renderStickyNotes();
}

document.addEventListener('DOMContentLoaded', function() {
    renderStickyNotes();

    const addStickyBtn = document.getElementById('addStickyBtn');
    if (addStickyBtn) {
        addStickyBtn.addEventListener('click', addStickyNote);
    }
});
</script>

</body>
</html>
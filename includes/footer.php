<footer class="site-footer">

    <div class="footer-grid">

        <div class="footer-brand">

            <h2 class="footer-brand-name">
                <?php echo renderSiteName(); ?>
            </h2>

            <p>
                A simple platform for sharing stories,
                ideas and experiences with readers.
            </p>

            <small>
                © <?php echo date("Y"); ?>
                <?php echo htmlspecialchars($siteName); ?>
            </small>

        </div>

        <div class="footer-column">

            <h3>Explore</h3>

            <a href="dashboard.php">
                Latest Blogs
            </a>

            <a href="create_blog.php">
                Create Blog
            </a>

            <a href="profile.php">
                My Profile
            </a>

        </div>

        <div class="footer-column">

            <h3>Account</h3>

            <?php if(isset($_SESSION['user_id'])) { ?>

                <a href="profile.php">
                    Manage Profile
                </a>

                <a href="logout.php">
                    Log Out
                </a>

            <?php } else { ?>

                <a href="login.php">
                    Sign In
                </a>

                <a href="register.php">
                    Register
                </a>

            <?php } ?>

        </div>

        <div class="footer-column">

            <h3>Connect With Us</h3>

            <div class="footer-social-icons" style="display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center !important; gap: 10px !important; margin-top: 12px !important;">
                <a href="https://wa.me/0722325117" target="_blank" rel="noopener noreferrer" title="WhatsApp" class="social-icon-link whatsapp-icon" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; margin: 0 !important;">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>

                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" title="Instagram" class="social-icon-link instagram-icon" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; margin: 0 !important;">
                    <i class="fa-brands fa-instagram"></i>
                </a>

                <a href="https://www.linkedin.com/in/dulmini-megasooriya-a026a7320?utm_source=share_via&utm_content=profile&utm_medium=member_ios" target="_blank" rel="noopener noreferrer" title="LinkedIn" class="social-icon-link linkedin-icon" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; margin: 0 !important;">
                    <i class="fa-brands fa-linkedin-in"></i>
                </a>
            </div>

        </div>

    </div>

</footer>

<script src="js/search_highlight.js?v=<?php echo time(); ?>"></script>
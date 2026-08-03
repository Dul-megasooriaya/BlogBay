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

            <h3>Contact</h3>

            <p>
                University of Moratuwa
            </p>

            <p>
                IN2120 Web Programming
            </p>

            <p>
                Sri Lanka
            </p>

        </div>

    </div>

</footer>

<script src="js/search_highlight.js?v=<?php echo time(); ?>"></script>
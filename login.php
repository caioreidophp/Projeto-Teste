<?php
// Assuming the rest of your login.php content is above

// Remove all emojis and add the following bottom navigation bar
echo '<div class="bottom-nav">';
echo '  <a href="home.php" class="nav-item">Home</a>';
echo '  <a href="learn.php" class="nav-item">Learn</a>';
echo '  <a href="missions.php" class="nav-item">Missions</a>';
echo '  <a href="progress.php" class="nav-item">Progress</a>';
echo '  <a href="profile.php" class="nav-item">Profile</a>';
echo '</div>';

// CSS for the bottom navigation bar
echo '<style>
.bottom-nav {
    display: flex;
    justify-content: space-around;
    background-color: #fff;
    padding: 10px 0;
    position: fixed;
    bottom: 0;
    width: 100%;
}
.nav-item {
    text-decoration: none;
    color: black;
    padding: 10px;
}
.nav-item:hover {
    color: green; /* Green hover effect */
}
</style>';
?>
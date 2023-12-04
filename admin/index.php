<?php
session_start(); // Start the session
// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    // Get the selected theme from the form
    $selectedTheme = $_POST['theme'];

    // Validate the selected theme (ensure it exists to prevent vulnerabilities)
    $allowedThemes = ['style.css', 'darkmode.style.css', 'lightmode.style.css', 'ltgreen.style.css', 'academi.style.css', 'gator.style.css','packers.style.css']; 

// List of allowed themes
    if (in_array($selectedTheme, $allowedThemes)) {
        // Set a session variable to remember the chosen theme
        $_SESSION['selected_theme'] = $selectedTheme;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
<link rel="stylesheet" type="text/css" href="theme.php">    <?php
    // Load the selected theme CSS dynamically
    if (isset($_SESSION['selected_theme'])) {
        $selectedTheme = $_SESSION['selected_theme'];
        
	echo '<link rel="stylesheet" type="text/css" href="css/' . $selectedTheme . '.css">';

    } else {
        // Fallback to a default theme if no theme is selected
        echo '<link rel="stylesheet" type="text/css" href="css/style.css">';
    }
    ?>
   <script>
        // JavaScript to pre-select the theme based on stored value
        document.addEventListener("DOMContentLoaded", function () {
            const themeSelect = document.getElementById("theme-select");
            const storedTheme = localStorage.getItem("selected_theme");

            if (storedTheme) {
                themeSelect.value = storedTheme;
            }

            themeSelect.addEventListener("change", function () {
                localStorage.setItem("selected_theme", this.value);
            });
        });
    </script>

</head>
<body>
    <center>
        <img src="../img/dnd-project-sm-logo.png">
    </center>

    <div class="container">
        <h1>Admin Dashboard</h1>
        <a href="reports.php">Print Records</a>
        <a href="edit.php">Edit Records</a>
        <a href="admin.php">Add or Edit Person/Reason</a>
    </div>

    <form method="post" action="">
        <label for="theme-select">Select a theme:</label>
        <select id="theme-select" name="theme">
            <option value="style.css">Default</option>
            <option value="darkmode.style.css">Dark Mode</option>
            <option value="lightmode.style.css">Light Mode</option>
            <option value="ltgreen.style.css">Light Green Mode</option>
            <option value="academi.style.css">Academi Mode</option>
            <option value="gator.style.css">Gator Mode</option>
            <option value="packers.style.css">Green Bay Mode</option>
            <!-- Add more options for additional themes -->
        </select>
        <input type="submit" value="Apply Theme">
    </form>
</body>
</html>


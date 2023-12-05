<?php
session_start(); // Start the session

// Default theme if no theme is selected
$defaultTheme = 'style'; // Change this to your desired default theme

// Get the selected theme from the session or use the default theme
$selectedTheme = isset($_SESSION['selected_theme']) ? $_SESSION['selected_theme'] : $defaultTheme;

// Define an array of allowed themes to prevent vulnerabilities
$allowedThemes = [
    'style' => 'style.css',
    'darkmode' => 'darkmode.style.css',
    'lightmode' => 'lightmode.style.css',
    'ltgreen' => 'ltgreen.style.css',
    'academi' => 'academi.style.css',
    'gator' => 'gator.style.css',
    'packers' => 'packers.style.css'
];

// Ensure the selected theme is valid; otherwise, fallback to default
if (!array_key_exists($selectedTheme, $allowedThemes)) {
    $selectedTheme = $defaultTheme;
}

// Generate the CSS file path based on the selected theme
$cssFileName = $allowedThemes[$selectedTheme];
$cssFilePath = '../css/' . $cssFileName;

// Set the Content-Type header to indicate CSS
header('Content-Type: text/css');

// Output the CSS file content
readfile($cssFilePath);
?>
	
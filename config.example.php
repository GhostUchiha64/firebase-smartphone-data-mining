<?php
/**
 * config.example.php
 *
 * SETUP INSTRUCTIONS:
 * 1. Copy this file and rename it to config.php
 * 2. Fill in your Firebase project URL below
 * 3. Set your own session password
 * 4. Never commit config.php to GitHub (it is in .gitignore)
 */

// -----------------------------------------------
// Firebase Configuration
// -----------------------------------------------
// Your Firebase Realtime Database URL
// Found in: Firebase Console > Project Settings > Your Apps
define('FIREBASE_URL', 'https://YOUR-PROJECT-ID-default-rtdb.firebaseio.com/');

// The node/path where smartphone data is stored
define('FIREBASE_NODE', 'smartphones');

// -----------------------------------------------
// Session Authentication
// -----------------------------------------------
// Password to access the web dashboard
// Change this to something strong and unique
define('SITE_PASSWORD', 'your_password_here');

// -----------------------------------------------
// Session Setup
// -----------------------------------------------
session_start();

// Check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === SITE_PASSWORD) {
        $_SESSION['authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Incorrect password. Please try again.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

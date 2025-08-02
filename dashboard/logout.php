<?php
// Start the session if it's not already started
session_start();

// Unset the specific session variable
unset($_SESSION['userId']);

// Optionally, destroy the entire session
// session_destroy(); // Uncomment this line if you want to destroy all session data

// Redirect to the index page
header('Location: index.php');
exit;
?>

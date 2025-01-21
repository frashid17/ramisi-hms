
<?php
session_start();

// Destroy all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Clear any other cookies if needed
// setcookie('user_id', '', time()-3600, '/');
// setcookie('username', '', time()-3600, '/');

// Redirect to login page
header("Location: login.php");
exit();
?>
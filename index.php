<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect based on user type
if (isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
    if ($user_type == 'doctor') {
        header("Location: doctor/index.php");
    } elseif ($user_type == 'frontdesk') {
        header("Location: frontdesk/index.php");
    } elseif ($user_type == 'nurse') {
        header("Location: medical-staff/index.php");
    } elseif ($user_type == 'admin') {
        header("Location: admin/index.php");
    }
    // Add other roles as needed
    else {
        // Default redirect for other roles or if role not specified
        header("Location: login.php");
    }
} else {
    // If user type is not in session, redirect to login
    header("Location: login.php");
}
exit();
?>

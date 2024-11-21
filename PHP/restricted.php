<?php
session_start();

$userRole = $_SESSION['user_role'] ?? null;

// Redirect if user role is not admin
if ($userRole !== 1) {
    header("Location: user.php"); // Redirect to a non-admin page
    exit();
}
?>
<!-- Admin content here -->
<h1>Welcome to the Admin Panel</h1>

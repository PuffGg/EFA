<?php
// Database connection (adjust with your credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Response initialization
$message = "";

// Check if user_id is provided via GET
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Verify that the user exists
    $checkUser = $conn->query("SELECT * FROM users WHERE user_id = $user_id");

    if ($checkUser->num_rows > 0) {
        // Start the transaction
        $conn->begin_transaction();

        try {
            // Delete user metadata from related tables (adjust these queries as per your schema)
            $conn->query("DELETE FROM user_role WHERE user_id = $user_id");
            $conn->query("DELETE FROM user_department WHERE user_id = $user_id");

            // Now delete the user from the users table
            $conn->query("DELETE FROM users WHERE user_id = $user_id");

            // Commit the transaction
            $conn->commit();
            $message = "User deleted successfully!";
        } catch (Exception $e) {
            // Rollback the transaction if something goes wrong
            $conn->rollback();
            $message = "Error deleting user: " . $e->getMessage();
        }
    } else {
        $message = "User ID does not exist.";  // Message when user doesn't exist
    }

    // Close connection
    $conn->close();

    // Redirect back to deleteuser.php with the message
    header("Location: deleteuser.php?message=" . urlencode($message));
    exit;
} else {
    $message = "User ID not provided!";
    header("Location: deleteuser.php?message=" . urlencode($message));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <script>
// Close the popup without deleting and redirect to the main page
function closePopup() {
    document.getElementById('popup-container').style.display = 'none'; // Hide the popup
    window.location.replace("admin.php"); // Use replace to prevent going back
}
   </script>
</body>
</html>

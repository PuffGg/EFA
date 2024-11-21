<?php

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // past date to ensure no caching

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session to store messages
session_start();

// Check if the user is logged in and has a valid user_id
if (!isset($_SESSION['user_id'])) {
    // If no user_id is found, redirect to login page
    header('Location: login.php');
    exit();
}

// Get the user_id from the session (logged-in admin)
$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the rate name and value from the form
    $rate_name = $_POST['rate_name'];
    $rate_value = $_POST['rate_value'];

    // Prepare SQL query to update the rate value based on rate name
    $stmt = $conn->prepare("UPDATE interest_rates SET rate_value = ?, user_id = ? WHERE rate_name = ?");

    // Bind parameters
    $stmt->bind_param("dis", $rate_value, $user_id, $rate_name);  // "d" for decimal, "i" for integer, "s" for string
    
    // Execute the statement
    if ($stmt->execute()) {
        // Set success message
        $_SESSION['success'] = "Interest rate for $rate_name has been updated successfully!";
    } else {
        // Set error message
        $_SESSION['error'] = "Error updating the interest rate. Please try again.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

       // Redirect to the form page to display the message
       header('Location: loaninterest.php');

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Change Interest Rates</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }
        
        .form-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-container button:hover {
            background-color: #1d4ed8;
        }

        /* Success and error message styles */
        .message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    position: relative;
    width: 100%;  /* Optional: if you want to ensure messages span the width */
    max-width: 1000px;  /* Optional: limit the width for larger screens */
    box-sizing: border-box; /* Ensures padding doesn't affect the width */
}

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .close-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: transparent;
            border: none;
            font-size: 16px;
            color: inherit;
            cursor: pointer;
        }

        .container {
    display: flex;
    flex-direction: column;     /* Align messages in a column */
    justify-content: center;    /* Vertically center content */
    align-items: center;        /* Horizontally center content */
    padding: 20px;
    text-align: center;         /* Ensure text is centered inside the messages */
}

    </style>
</head>
<body>
    <div class="container">
            <!-- Display Success or Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="message success" id="success-message">
            <?php echo $_SESSION['success']; ?>
            <button class="close-btn" onclick="closeMessage('success-message')">x</button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="message error" id="error-message">
            <?php echo $_SESSION['error']; ?>
            <button class="close-btn" onclick="closeMessage('error-message')">x</button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    </div>

<div class="form-container">


    <h2>Change Interest Rates</h2>
    <form id="rateForm" action="loaninterest.php" method="POST">
        <label for="rate_name">Rate Name</label>
        <select id="rate_name" name="rate_name" required>
            <option value="Loans">Loans</option>
            <option value="Savings">Savings</option>
        </select>

        <label for="rate_value">New Interest Rate (%)</label>
        <input type="number" id="rate_value" name="rate_value" step="0.01" min="0" required>

        <button type="submit">Update Rate</button>
    </form>
</div>

<script>

    // Function to close messages
    function closeMessage(messageId) {
        const messageElement = document.getElementById(messageId);
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }
</script>

</body>
</html>

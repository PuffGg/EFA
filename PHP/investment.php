<?php



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

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // past date to ensure no caching

// Check if the user is logged in and has a valid user_id
if (!isset($_SESSION['user_id'])) {
    // If no user_id is found, redirect to login page
    header('Location: login.php');
    exit();
}

// Get the user_id from the session (logged-in admin)
$user_id = $_SESSION['user_id'];

// Initialize messages
$transaction_message = "";
$message_type = "";  // success or error
$rate_update_message = "";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if it's a transaction or rate update form submission
    if (isset($_POST['transaction_type'])) {
        // Handle deposit or withdrawal transactions
        $membership_code = $_POST['membership_code'];
        $amount = floatval($_POST['amount']);
        $transaction_type = intval($_POST['transaction_type']);

        // Step 1: Verify Membership Code
        $sql = "SELECT user_id FROM users WHERE membership_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $membership_code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id);
            $stmt->fetch();

            // Step 2: Insert transaction into user_transactions
            $sql_insert_transaction = "INSERT INTO user_transactions (user_id, membership_code, transaction_type_id, transaction_date)
                                       VALUES (?, ?, ?, NOW())";
            $stmt_insert_transaction = $conn->prepare($sql_insert_transaction);
            $stmt_insert_transaction->bind_param("isi", $user_id, $membership_code, $transaction_type);
            $stmt_insert_transaction->execute();
            $transaction_id = $stmt_insert_transaction->insert_id;

            if ($transaction_type === 1) {  // Deposit
                // Calculate new balance after deposit
                $sql_total_deposits = "SELECT COALESCE(SUM(amount), 0) FROM investments WHERE user_transactions_id IN 
                                      (SELECT user_transactions_id FROM user_transactions WHERE user_id = ?)";
                $stmt_total_deposits = $conn->prepare($sql_total_deposits);
                $stmt_total_deposits->bind_param("i", $user_id);
                $stmt_total_deposits->execute();
                $stmt_total_deposits->bind_result($total_deposits);
                $stmt_total_deposits->fetch();
                $stmt_total_deposits->close();

                $new_balance = $total_deposits + $amount;

                $sql_insert_investment = "INSERT INTO investments (user_transactions_id, amount, balance, withdrawal)
                                          VALUES (?, ?, ?, NULL)";
                $stmt_insert_investment = $conn->prepare($sql_insert_investment);
                $stmt_insert_investment->bind_param("idi", $transaction_id, $amount, $new_balance);
                $stmt_insert_investment->execute();

                $transaction_message = "Deposit processed successfully!";
                $message_type = "success"; // Success message

            } elseif ($transaction_type === 2) {  // Withdrawal
                // Retrieve the latest balance for the user
                $sql_last_balance = "SELECT balance FROM investments WHERE user_transactions_id IN 
                                     (SELECT user_transactions_id FROM user_transactions WHERE user_id = ?)
                                     ORDER BY updated_at DESC LIMIT 1";
                $stmt_last_balance = $conn->prepare($sql_last_balance);
                $stmt_last_balance->bind_param("i", $user_id);
                $stmt_last_balance->execute();
                $stmt_last_balance->bind_result($last_balance);
                $stmt_last_balance->fetch();
                $stmt_last_balance->close();

                if ($last_balance >= $amount) {  // Check if enough balance is available
                    $new_balance = $last_balance - $amount;

                    // Insert withdrawal record into investments
                    $sql_insert_investment = "INSERT INTO investments (user_transactions_id, amount, balance, withdrawal)
                                              VALUES (?, NULL, ?, ?)";
                    $stmt_insert_investment = $conn->prepare($sql_insert_investment);
                    $stmt_insert_investment->bind_param("idd", $transaction_id, $new_balance, $amount);
                    $stmt_insert_investment->execute();

                    $transaction_message = "Withdrawal processed successfully!";
                    $message_type = "success"; // Success message
                } else {
                    $transaction_message = "Insufficient funds for withdrawal!";
                    $message_type = "error"; // Error message
                }
            } else {
                $transaction_message = "Invalid transaction type!";
                $message_type = "error"; // Error message
            }

            // Close statements
            $stmt_insert_transaction->close();
            $stmt_insert_investment->close();

        } else {
            $transaction_message = "User with that membership code not found!";
            $message_type = "error"; // Error message
        }

        $stmt->close();

        // Store transaction message in session
        $_SESSION['transaction_message'] = $transaction_message;
        $_SESSION['message_type'] = $message_type;

        // Redirect to avoid resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();  // Ensure no further code is executed
    } else if (isset($_POST['rate_name'], $_POST['rate_value'])) {
        // Handle rate update form submission
        $rate_name = $_POST['rate_name'];
        $rate_value = $_POST['rate_value'];

        // Prepare SQL query to update the rate value based on rate name
        $stmt = $conn->prepare("UPDATE interest_rates SET rate_value = ?, user_id = ? WHERE rate_name = ?");

        // Bind parameters
        $stmt->bind_param("dis", $rate_value, $user_id, $rate_name);  // "d" for decimal, "i" for integer, "s" for string
        
        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['rate_update_message'] = "Interest rate for $rate_name has been updated successfully!";
        } else {
            $_SESSION['rate_update_message'] = "Error updating the interest rate. Please try again.";
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();

        // Redirect to avoid resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();  // Ensure no further code is executed
    }
}

// Fetch analytics (total deposits, withdrawals, balance, etc.)
$sql_deposits = "SELECT COALESCE(SUM(amount), 0) AS total_deposits FROM investments WHERE amount IS NOT NULL";
$result_deposits = $conn->query($sql_deposits);
$row_deposits = $result_deposits->fetch_assoc();
$total_deposits = $row_deposits['total_deposits'];

$sql_withdrawals = "SELECT COALESCE(SUM(withdrawal), 0) AS total_withdrawals FROM investments WHERE withdrawal IS NOT NULL";
$result_withdrawals = $conn->query($sql_withdrawals);
$row_withdrawals = $result_withdrawals->fetch_assoc();
$total_withdrawals = $row_withdrawals['total_withdrawals'];

$sql_current_balance = "SELECT balance FROM investments ORDER BY created_at DESC LIMIT 1";
$result_balance = $conn->query($sql_current_balance);

if ($result_balance && $result_balance->num_rows > 0) {
    $row_balance = $result_balance->fetch_assoc();
    $current_balance = $row_balance['balance'];
} else {
    $current_balance = 0;
    echo "No investment records found.";
}

// Close database connection
$conn->close();
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Market Transactions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <style>
          @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap');
/* General Reset and Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Lato', sans-serif;
    background-color: #f4f7f6;
    color: #333;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    font-weight: 400;
}

h1 {
    color: #333;
    margin-bottom: 30px;
}

/* Message Box Styles */
.message {
    width: 100%;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
    position: relative;
    max-width: 600px;
    margin: 10px auto;
}

.message.success {
    background-color: #A8E6A1; /* Light green background for success */
    color: #2D6A4F; /* Darker green text */
}

.message.error {
    background-color: #F8D7DA; /* Light red background for errors */
    color: #721C24; /* Darker red text */
}

/* Close Button Style */
.message .close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

/* Form Styling */
.container, .analytics {
    width: 100%;
    max-width: 600px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label {
    font-size: 14px;
    color: #555;
    margin-bottom: 5px;
}

.form-group input, 
.form-group select {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
}

/* Button Styling */
button[type="submit"] {
    background-color: #007bff;
    color: #fff;
    padding: 10px 15px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button[type="submit"]:hover {
    background: rgba(58, 219, 66, 0.95);
}



.analytics {
    font-family: 'Arial', sans-serif; /* Use a clean, readable font */
    color: #333; /* Dark grey for text to enhance readability */
    background-color: #f8f9fa; /* Light background for a clean look */
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    margin: 20px 0; /* Margin to separate from other content */
    max-width: 600px; /* Limit the width to ensure readability */
    margin-left: auto;
    margin-right: auto; /* Center the analytics box */
}

/* Header Styling */
.analytics h2 {
    font-size: 1 em; /* Larger text for the header */
    font-weight: 600; /* Make the header text bold */
    color: #007bff; /* A blue color for the header */
    margin-bottom: 20px; /* Space between header and summary */
    text-align: center;
}

/* Styling for individual summary items */
.analytics .summary div {
    font-size: 1.5 em; /* Increase font size for better readability */
    padding: 10px 0; /* Add padding between rows */

}

/* Styling for the span element (labels) within each summary div */
.analytics .summary div span {
    font-weight: 600; /* Bold for the labels */
    color: #333; /* Dark color for text */
}

/* Remove bottom border from the last summary item */
.analytics .summary div:last-child {
    border-bottom: none;
}

/* Form Container */
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



/* Message Box with Flexbox for Centering */
.container1 {
    display: flex;
    flex-direction: column; /* Align messages in a column */
    justify-content: center; /* Vertically center content */
    align-items: center; /* Horizontally center content */
    padding: 20px;
    text-align: center; /* Ensure text is centered inside the messages */
}

/* Media Query for Mobile Responsiveness */
@media (max-width: 600px) {
    .summary div {
        flex: 1 1 100%;
    }
}


    </style>
</head>
<body>
    <h1>Money Market Management</h1>
    <?php
// Display transaction message (if any)
if (isset($_SESSION['transaction_message'])) {
    $message_type = $_SESSION['message_type'] ?? 'success'; // Default to 'success' if not set
    echo '<div class="message ' . $message_type . '" id="transactionMessage">';
    echo $_SESSION['transaction_message'];
    echo '<button class="close-btn" onclick="closeMessage()">×</button>';
    echo '</div>';
    unset($_SESSION['transaction_message']);
    unset($_SESSION['message_type']);
}

// Display rate update message (if any)
if (isset($_SESSION['rate_update_message'])) {
    // Make sure message_type is set for this message
    $message_type = $_SESSION['message_type'] ?? 'success'; // Default to 'success' if not set
    echo '<div class="message ' . $message_type . '" id="rateUpdateMessage">';
    echo $_SESSION['rate_update_message'];
    echo '<button class="close-btn" onclick="closeMessage()">×</button>';
    echo '</div>';
    unset($_SESSION['rate_update_message']);
    unset($_SESSION['message_type']);
}
?>



    <div class="container">
        <form action="" method="POST">
            <div class="form-group">
                <label for="membership_code">Member's Membership Code</label>
                <input type="text" id="membership_code" name="membership_code" required>
            </div>

            <div class="form-group">
                <label for="action">Transaction Type</label>
                <select id="action" name="transaction_type" required>
                    <option value="1">Deposit</option>   <!-- ID 1 -->
                    <option value="2">Withdrawal</option> <!-- ID 2 -->
                    <option value="3">Loan Request</option> <!-- ID 3 -->
                    <option value="4">Loan Payment</option> <!-- ID 4 -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount (KSH)</label>
                <input type="number" id="amount" name="amount" required>
            </div>

            <div class="form-group">
                <button type="submit">Submit Transaction</button>
            </div>
        </form>
    </div>

    

    <!-- Analytics Section -->
    <div class="analytics">
        <h2>Analytics</h2>
        <div class="summary">
            <div>
 
                <div class="analytics">
        <div><span>Total Deposits: </span>Ksh<?php echo number_format($total_deposits, 2); ?></div>
        <div><span>Total Withdrawals: </span>Ksh<?php echo number_format($total_withdrawals, 2); ?></div>
        <div><span>Current Balance: </span>Ksh<?php echo number_format($current_balance, 2); ?></div>
    </div>
    </div>
    

    <div class="container1">

    </div>

<div class="form-container">


    <h2>Change Interest Rates</h2>
    <form id="rateForm" action="investment.php" method="POST">
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
    // Function to remove the message completely
    function closeMessage() {
        var message = document.getElementById("transactionMessage");
        if (message) {
            message.style.display = 'none';  // Hide the message element
        }

        var rateUpdateMessage = document.getElementById("rateUpdateMessage");
        if (rateUpdateMessage) {
            rateUpdateMessage.style.display = 'none';  // Hide the rate update message element
        }
    }

    window.history.pushState(null, "", window.location.href);
window.onpopstate = function () {
    window.history.go(1);  // Go forward in history
    window.location.replace("admin.php");  // Redirect to admin page
};
</script>

</body>
</html>

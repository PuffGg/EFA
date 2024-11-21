<?php
session_start();

// Assuming user is already logged in and user_id is stored in session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user ID

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "efa";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $receiver_id = $_POST['receiver_id']; // ID of the recipient user
    $message = $_POST['message']; // Message content

    if (!empty($receiver_id) && !empty($message)) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $receiver_id, $message);
        if ($stmt->execute()) {
            $success_message = "Message sent successfully!";
        } else {
            $error_message = "Failed to send the message.";
        }
    } else {
        $error_message = "Please enter a message.";
    }
}

// Handle message deletion
if (isset($_GET['delete_message_id'])) {
    $message_id = $_GET['delete_message_id'];
    $delete_sql = "DELETE FROM messages WHERE message_id = ? AND receiver_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $message_id, $user_id);
    if ($stmt->execute()) {
        $success_message = "Message deleted successfully!";
    } else {
        $error_message = "Failed to delete the message.";
    }
}

// Fetch list of users to select a recipient
$users_sql = "SELECT user_id, username FROM users WHERE user_id != ?";
$stmt = $conn->prepare($users_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$users_result = $stmt->get_result();

// Fetch received messages for the logged-in user
$messages_sql = "SELECT m.message_id, u.username, m.message, m.timestamp 
                 FROM messages m 
                 JOIN users u ON m.sender_id = u.user_id 
                 WHERE m.receiver_id = ? 
                 ORDER BY m.timestamp DESC";
$stmt = $conn->prepare($messages_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$messages_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Platform</title>
    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f4f8;
    margin: 0;
    padding: 0;
    line-height: 1.6;
    color: #333;
}

.container {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
    font-weight: 300;
}

.messages {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 25px;
    margin-bottom: 30px;
    transition: transform 0.3s ease;
}

.messages:hover {
    transform: translateY(-5px);
}

.message {
    background-color: #f9f9fb;
    border-left: 4px solid #007bff;
    margin: 15px 0;
    padding: 15px;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.message:hover {
    background-color: #f1f3f5;
}

.message span {
    display: block;
    font-size: 0.85em;
    color: #6c757d;
    margin-bottom: 5px;
}

.message-content {
    font-size: 1em;
    color: #2c3e50;
    line-height: 1.5;
}

.send-message {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 30px;
    transition: transform 0.3s ease;
}

.send-message:hover {
    transform: translateY(-5px);
}

.delete-message {
    display: inline-block;
    color: #dc3545;
    text-decoration: none;
    font-size: 0.85em;
    margin-top: 10px;
    padding: 5px 10px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.delete-message:hover {
    background-color: #dc3545;
    color: white;
}

.delete-message:active {
    transform: scale(0.95);
}

form {
    display: flex;
    flex-direction: column;
}

label {
    margin-bottom: 8px;
    color: #495057;
    font-weight: 600;
}

select, textarea {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
}

select:focus, textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

button {
    background-color: #007bff;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-weight: 600;
    align-self: flex-start;
}

button:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

button:active {
    transform: translateY(0);
}

.error {
    color: #dc3545;
    background-color: #f8d7da;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success {
    color: #28a745;
    background-color: #d4edda;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

/* Responsive Adjustments */
@media screen and (max-width: 600px) {
    .container {
        width: 95%;
        padding: 10px;
    }

    .messages, .send-message {
        padding: 15px;
    }

    h1 {
        font-size: 1.5em;
    }

    .message {
        padding: 10px;
    }

    select, textarea, button {
        padding: 10px;
    }
}

/* Scrollbar Styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #0056b3;
}
    </style>
</head>
<body>
<div class="container">
        <h1>Welcome to the Messaging Platform</h1>

        <div class="messages">
            <h3>Messages</h3>
            <?php if ($messages_result->num_rows > 0): ?>
                <?php while ($row = $messages_result->fetch_assoc()): ?>
                    <div class="message">
                        <span>From: <?php echo htmlspecialchars($row['username']); ?> - <?php echo htmlspecialchars($row['timestamp']); ?></span>
                        <div class="message-content"><?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                        <a href="?delete_message_id=<?php echo $row['message_id']; ?>" class="delete-message">Delete</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No messages yet.</p>
            <?php endif; ?>
        </div>

        <div class="send-message">
            <h3>Send a Message</h3>
            <?php if ($error_message): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php elseif ($success_message): ?>
                <p class="success"><?php echo $success_message; ?></p>
            <?php endif; ?>

            <form method="POST" action="">
<!-- Wrapper for the dropdown -->
<div style="position: relative; width: 100%; max-width: 300px;">
    <label for="receiver">Select Recipient</label>
    <!-- Search input -->
    <input type="text" id="searchDropdown" placeholder="Search recipient..." onkeyup="filterDropdown()" 
        style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px;">

    <!-- Styled dropdown list -->
    <div id="dropdownContainer" 
        style="position: absolute; width: 100%; border: 1px solid #ccc; background: white; border-radius: 5px; max-height: 200px; overflow-y: auto; display: none;">
        <div onclick="selectOption('')">Select a user</div>
        <?php while ($user = $users_result->fetch_assoc()): ?>
            <div data-value="<?php echo $user['user_id']; ?>" onclick="selectOption('<?php echo $user['user_id']; ?>')">
                <?php echo $user['username']; ?>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Hidden input to store the selected value -->
<input type="hidden" name="receiver_id" id="receiver" required>
                <label for="message">Message</label>
                <textarea name="message" id="message" rows="4" required></textarea>

                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>
</body>

<script>
        // Optional: You can add an AJAX request to delete messages without refreshing the page
        document.querySelectorAll('.delete-message').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const messageId = this.getAttribute('href').split('=')[1];

                fetch(`?delete_message_id=${messageId}`)
                    .then(response => response.text())
                    .then(data => {
                        // Handle success or failure
                        if (data.includes("Message deleted successfully")) {
                            this.closest('.message').remove();
                        } else {
                            alert("Failed to delete message.");
                        }
                    });
            });
        });




        function filterDropdown() {
    let input = document.getElementById("searchDropdown").value.toLowerCase();
    let dropdown = document.getElementById("dropdownContainer");
    let options = dropdown.getElementsByTagName("div");

    // Show or hide options based on search input
    for (let i = 0; i < options.length; i++) {
        let optionText = options[i].textContent || options[i].innerText;

        // Skip the first option (placeholder text)
        if (optionText.toLowerCase().indexOf(input) > -1 || i === 0) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }

    // Show the dropdown
    dropdown.style.display = "block";
}

function selectOption(value) {
    let dropdown = document.getElementById("dropdownContainer");
    let searchInput = document.getElementById("searchDropdown");
    let hiddenInput = document.getElementById("receiver");

    // Find the selected option and set its value
    let selectedOption = Array.from(dropdown.getElementsByTagName("div")).find(option => option.dataset.value === value);
    if (selectedOption) {
        searchInput.value = selectedOption.textContent;
        hiddenInput.value = value;
    }

    // Hide the dropdown
    dropdown.style.display = "none";
}


// Redirect to user.php when the back button is pressed
if (window.history) {
    history.pushState(null, null, window.location.href);
    window.onpopstate = function () {
        window.location.href = 'user.php'; // Redirect to user.php
    };
}
    </script>
</html>

<?php
// Close the database connection
$conn->close();
?>

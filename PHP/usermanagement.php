<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "efa";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update user status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];

    $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $user_id);

    if ($stmt->execute()) {
        $message = "User status updated successfully.";
    } else {
        $error = "Failed to update user status.";
    }
}

// Fetch all users
$sql = "SELECT user_id, username, status FROM users";
$result = $conn->query($sql);

// Assuming deletion logic is handled here
$message = isset($_GET['message']) ? $_GET['message'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
/* Responsive Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f0f0;
    line-height: 1.6;
    padding: 10px;
}

.container {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    padding: 15px;
}

/* Responsive Typography */
h1,h2 {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

/* Responsive Form Styles */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

input[type="number"], 
input[type="text"], 
#searchInput {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    transition: all 0.3s ease;
}

button {
    padding: 10px 15px;
    font-size: 16px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* Responsive Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

/* Media Queries for Extreme Responsiveness */
@media screen and (max-width: 480px) {
    body {
        padding: 5px;
        font-size: 14px;
    }

    .container {
        padding: 10px;
    }

    h1 {
        font-size: 1.5rem;
    }

    table {
        font-size: 0.9rem;
    }

    th, td {
        padding: 8px;
    }

    /* Make table horizontally scrollable on small screens */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    input[type="number"], 
    input[type="text"], 
    #searchInput, 
    button {
        width: 100%;
        padding: 8px;
        font-size: 14px;
    }
}

/* Popup Styles */
.popup-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.popup-content {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 90%;
    max-width: 350px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Responsive Popup Buttons */
.popup-content button {
    margin: 10px 5px;
    width: calc(50% - 10px);
}

/* Search Input Responsiveness */
#searchInput {
    max-width: 100%;
    margin: 15px auto;
    display: block;
}

/* Hover and Focus States */
input:focus, 
button:hover {
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

button:hover {
    background-color: #0056b3;
}


header {
    background-color: #0078d7;
    color: #fff;
    padding: 20px;
    text-align: center;
    font-size: 24px;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.box {
    text-decoration: none;
    color: black;
    font-size: 18px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    position: absolute;
    top: 50%;
    left: 20px;
    transform: translateY(-50%);
}
.box1 {
    text-decoration: none;
    color: black;
    font-size: 18px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    position: absolute;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
}

.box:hover {
    color: #ffd700;
}
.box1:hover {
    color: #ffd700;
}



    </style>
</head>
<body>
    <header>
<a href="admin.php" class="box"><i class="fas fa-arrow-left"></i> </a>
<a href="user.php" class="box1"><i class="fas fa-user-circle"></i> Personal Account</a>
    </header>


    <div class="container">
        <h1>Delete User</h1>

        <!-- Display success or error message -->
        <?php if ($message): ?>
            <p class="<?php echo strpos($message, 'Error') === false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <!-- Form to delete user -->
        <form id="deleteForm" action="" method="get" onsubmit="showConfirmation(event)">
            <label for="user_id">Enter User ID to Delete:</label>
            <input type="number" id="user_id" name="user_id" required>
            <button type="submit">Delete User</button>
        </form>
    </div>

    <h2>Manage User Status</h2>

    <?php if (!empty($message)): ?>
        <p style="color: green;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- Search bar to filter table -->
    <div style="margin-bottom: 20px; text-align: center;">
        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search for users..." title="Type in a username" >
       
    </div>

    <table id="userTable" border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $row['status'] === 'active' ? 'blocked' : 'active'; ?>">
                            <button type="submit">
                                <?php echo $row['status'] === 'active' ? 'Block' : 'Activate'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>

    <!-- Popup Confirmation -->
    <div class="popup-container" id="popup-container">
        <div class="popup-content">
            <p>Are you sure you want to delete this user?</p>
            <button onclick="confirmDelete()">Delete</button>
            <button onclick="closePopup()">Cancel</button>
        </div>
    </div>



    

    <script>
        // Show the confirmation popup
        function showConfirmation(event) {
            event.preventDefault(); // Prevent the form from submitting immediately
            var user_id = document.getElementById('user_id').value;
            document.getElementById('popup-container').style.display = 'flex'; // Show the popup
        }

        // Close the popup without deleting
        function closePopup() {
            document.getElementById('popup-container').style.display = 'none'; // Hide the popup
            // Immediately redirect to admin.php when canceled
            window.location.replace("admin.php");
        }

        // Confirm deletion and submit the form
        function confirmDelete() {
            var user_id = document.getElementById('user_id').value;
            // Simulate deletion by redirecting to delete.php (use actual deletion logic here)
            window.location.href = "delete.php?user_id=" + user_id;
        }

        // Prevent back navigation and immediately redirect to admin.php
        window.onload = function() {
            // Replace the current history entry to prevent the user from using the back button
            window.history.replaceState(null, null, window.location.href);
        };

        // If the user tries to navigate back, redirect them immediately to admin.php
        window.onpopstate = function() {
            window.location.replace("admin.php");
        };

        // Function to filter the table based on search input
        function filterTable() {
            var input, filter, table, tr, td, i, j, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("userTable");
            tr = table.getElementsByTagName("tr");

            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td");
                for (j = 0; j < td.length; j++) {
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                            break;
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>




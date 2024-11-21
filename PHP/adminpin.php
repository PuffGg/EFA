<?php
session_start();

// Get the user ID from the session
$user_id = $_SESSION['user_id'] ?? null;

// Check if the user is logged in (user_id must be available)
if ($user_id === null) {
    // Redirect to login if user_id is not set
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "efa"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the role_id and user_dept_id from the users table based on the user_id
$sql = "SELECT role_id, user_dept_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the role_id and user_dept_id for the user
    $row = $result->fetch_assoc();
    $role_id = $row['role_id'];
    $user_dept_id = $row['user_dept_id'];
} else {
    // If user_id is not found, redirect to login page or handle error
    echo "User not found.";
    exit();
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve PIN from POST request (combined from the form inputs)
    $pin = implode('', $_POST['pin']); // Combine the PIN inputs into a single string

    if (strlen($pin) === 4) {
        // Hash the PIN using bcrypt for security
        $hashed_pin = password_hash($pin, PASSWORD_BCRYPT);

        // Prepare SQL statement to insert the PIN into admin_pins table
        // Ensure user_dept_id is included only if it's valid
        if (isset($user_dept_id)) {
            $stmt = $conn->prepare("INSERT INTO admin_pins (role_id, user_id, user_dept_id, hashed_pin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $role_id, $user_id, $user_dept_id, $hashed_pin);
        } else {
            $stmt = $conn->prepare("INSERT INTO admin_pins (role_id, user_id, hashed_pin) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $role_id, $user_id, $hashed_pin);
        }

        // Execute the query and check if it was successful
        if ($stmt->execute()) {
            // PIN saved successfully, redirect to set.html
            header("Location: adminsetpin.php");
            exit();
        } else {
            echo "Error saving PIN: " . $stmt->error;
        }

        // Close the prepared statement
        $stmt->close();
    } else {
        echo "PIN must be exactly 4 digits.";
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PIN Entry</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      background-color: #f0f0f0;
    }

    header {
            background-color: #0078d7;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            position: relative; /* Enables positioning for child elements */
        }

        .box {
            text-decoration: none;
            color: #fff; /* Adjusted to match header text color */
            font-size: 18px;
            display: inline-flex;
            align-items: center;
            gap: 5px; /* Adds space between the icon and the text */
            position: absolute; /* Positions the link */
            top: 50%; /* Centers vertically */
            right: 20px; /* Moves link 20px from the right edge */
            transform: translateY(-50%); /* Adjusts for vertical alignment */
        }

        .box:hover {
            color: #ffd700; /* Adds hover effect */
        }
    .pin-container {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      width: 300px;
      text-align: center;
    }
    table {
      margin: 10px auto;
      border-spacing: 10px;
    }
    td {
      text-align: center;
    }
    input[type="password"] {
      width: 40px;
      height: 40px;
      text-align: center;
      font-size: 24px;
      margin: 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 4px;
      background-color: #007bff;
      color: white;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    .error {
      color: red;
      font-size: 14px;
      margin-top: 10px;
    }
    .success {
      color: green;
      font-size: 14px;
      margin-top: 10px;
    }
  </style>
</head>
<body>

<header>
        Administrator Department Choice Dashboard
        <a href="admindept.php" class="box">
    <i class="fa fa-home"></i> Departments board
</a>

    </header>
  <div class="pin-container">
    <h2>Enter and Confirm PIN</h2>
    <form method="POST" action="adminpin.php"> <!-- Ensure this points to your PHP script -->
      <table>
        <tr>
          <td colspan="4"><strong>Enter PIN</strong></td>
        </tr>
        <tr class="pin-inputs" data-group="enter-pin">
          <td><input type="password" name="pin[]" maxlength="1" required></td>
          <td><input type="password" name="pin[]" maxlength="1" required></td>
          <td><input type="password" name="pin[]" maxlength="1" required></td>
          <td><input type="password" name="pin[]" maxlength="1" required></td>
        </tr>
        <tr>
          <td colspan="4"><strong>Confirm PIN</strong></td>
        </tr>
        <tr class="pin-inputs" data-group="confirm-pin">
          <td><input type="password" name="pin_confirm[]" maxlength="1" required></td>
          <td><input type="password" name="pin_confirm[]" maxlength="1" required></td>
          <td><input type="password" name="pin_confirm[]" maxlength="1" required></td>
          <td><input type="password" name="pin_confirm[]" maxlength="1" required></td>
        </tr>
      </table>
      <button type="submit" id="submit-btn">Submit</button>
    </form>
    <p class="error" id="error-msg" style="display: none;">PINs do not match. Please try again.</p>
    <p class="success" id="success-msg" style="display: none;">PIN confirmed successfully!</p>
    <hr>

    <a href="adminsetpin.php">
    <button>Enter Account Pin to Continue</button>
</a>
  </div>



  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const inputs = document.querySelectorAll("input[type='password']");
      const submitButton = document.getElementById("submit-btn");
      const errorMsg = document.getElementById("error-msg");
      const successMsg = document.getElementById("success-msg");

      // Auto focus logic for navigation between inputs
      inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
          if (input.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
          }
        });

        input.addEventListener("keydown", (e) => {
          if (e.key === "Backspace" && !input.value && index > 0) {
            inputs[index - 1].focus();
          }
        });
      });

      // Submit button logic
      submitButton.addEventListener("click", (event) => {
        const enterPin = Array.from(document.querySelectorAll(".pin-inputs[data-group='enter-pin'] input"))
          .map(input => input.value)
          .join("");
        const confirmPin = Array.from(document.querySelectorAll(".pin-inputs[data-group='confirm-pin'] input"))
          .map(input => input.value)
          .join("");

        if (enterPin === confirmPin && enterPin.length === 4) {
          errorMsg.style.display = "none";
          successMsg.style.display = "block";
        } else {
          successMsg.style.display = "none";
          errorMsg.style.display = "block";
          event.preventDefault(); // Prevent form submission if PINs do not match
        }
      });
    });
  </script>
</body>
</html>

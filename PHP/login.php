<?php
session_start();

// Database connection parameters
$serverName = "localhost";
$userName = "root";
$password = "";
$database = "efa";

// Establish connection to the database
$connectDB = mysqli_connect($serverName, $userName, $password, $database);

// Check if the connection was successful
if (!$connectDB) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the login form is submitted
if (isset($_POST['regg'])) {
    // Retrieve user input from the login form
    $username = mysqli_real_escape_string($connectDB, $_POST['username']);
    $email = mysqli_real_escape_string($connectDB, $_POST['email']);
    $password = $_POST['password']; // Plain text password

    // Prepare the query to check if the user exists in the 'users' table
    $query = "SELECT * FROM users WHERE username=? AND email=?";
    $stmt = $connectDB->prepare($query);
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, fetch their details
        $user = $result->fetch_assoc();
        $role_id = $user['role_id']; // Fetch role ID
        $user_id = $user['user_id']; // Store user's ID in a variable
        $status = $user['status'];   // Check if the user is active or blocked

        // Check if the user is blocked
        if ($status === 'blocked') {
            header("Location: blockedaccount.html");
            exit();
        }

        // Check if the entered password matches the stored password
        if (password_verify($password, $user['password'])) {
            // Store login details for audit
            $login_time = date("Y-m-d H:i:s");
            $stmt = $connectDB->prepare("INSERT INTO logins (user_id, login_time) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $login_time);
            $stmt->execute();
            $stmt->close();

            // Fetch role name based on role ID
            $role_query = "SELECT role_name FROM roles WHERE role_id=?";
            $role_stmt = $connectDB->prepare($role_query);
            $role_stmt->bind_param('i', $role_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $role_data = $role_result->fetch_assoc();
            $role_name = $role_data['role_name'];

            // Store user_id in session
            $_SESSION['user_id'] = $user_id;

            // Check for required user details and redirect accordingly
            $user_details_query = "SELECT first_name, last_name, membership_code, gender, dob, contact FROM users WHERE user_id=?";
            $details_stmt = $connectDB->prepare($user_details_query);
            $details_stmt->bind_param('i', $user_id);
            $details_stmt->execute();
            $user_details = $details_stmt->get_result()->fetch_assoc();

            if (empty($user_details['first_name']) || empty($user_details['last_name']) || empty($user_details['membership_code']) || empty($user_details['gender']) || empty($user_details['dob']) || empty($user_details['contact'])) {
                // Redirect to userdetails.php if any required field is missing
                header("Location: userdetails.php");
                exit();
            } else {
                // Redirect to dashboard or specific page based on role
                if ($role_name === 'MEMBER') {
                    header('Location: user.php');
                    exit();
                } else if ($role_name === 'ADMINISTRATOR') {
                    header('Location: admindept.php');
                    exit();
                } else {
                    // Handle other roles if necessary
                    $error_message = "Unauthorized access!";
                    echo $error_message;
                    exit();
                }
            }
        } else {
            // Invalid password
            $error_message = "Invalid password!";
            echo $error_message;
        }
    } else {
        // User does not exist
        $error_message = "Invalid Username or Email";
        echo $error_message;
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>LOGIN</title>
    <link rel="stylesheet" href="login.css"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>
<style>
body {
  position: relative;
  margin: 0;
  height: 100vh;
  overflow-x: hidden;
  font-family: 'Roboto', sans-serif;
  padding: 0;
  box-sizing: border-box;
}

/* Pseudo-element to handle background image with lower opacity */
body::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('efa1.jpg');
  background-size: cover;
  background-position: center;
  opacity: 0.5;
  z-index: -1;
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Roboto', sans-serif;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* Form Container */
.con1 {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 2.5rem;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 450px;
    margin: 0; /* Remove any margins to ensure it's perfectly centered */
    border: 1px solid rgba(255, 255, 255, 0.3);

    /* Ensure it's centered both horizontally and vertically */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

/* Form Title */
h1 {
    color: #2d3748;
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

/* Input Container */
.con2 {
    text-align: left;
    width: 100%;
    max-width: 350px; /* Ensure inputs don't stretch too wide */
}

/* Labels */
label {
    display: block;
    color: #2d3748;
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-family: 'Roboto', sans-serif;
    
}

/* Input Fields */
input {
    width: 100%;
    padding: 1.25rem; /* Increased padding for larger fields */
    margin-bottom: 1.5rem;
    border: 1.5px solid rgba(200, 200, 200, 0.8);
    border-radius: 0.5rem;
    font-size: 1.2rem; /* Increased font size for readability */
    background: rgba(255, 255, 255, 0.9);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Placeholder Text */
::placeholder {
    color: rgba(0, 0, 0, 0.5);
}

/* Input Focus State */
input:focus {
    outline: none;
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
}

/* Login Button */
#login {
    width: 100%;
    padding: 0.875rem;
    background: rgba(66, 153, 225, 0.9);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

#login:hover {
    background: rgba(58, 219, 66, 0.95);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(66, 153, 225, 0.15);
}

/* Sign-up Prompt */
#p1 {
    color: #2d3748;
    font-size: 0.9rem;
    margin-top: 1.25rem;
}

#p1 a {
    color: #3182ce;
    text-decoration: none;
    font-weight: 500;
}

#p1 a:hover {
    color: #2c5282;
}

/* Error Message */
.error-msg {
    color: #e53e3e;
    background: rgba(255, 245, 245, 0.9);
    border: 1px solid rgba(254, 178, 178, 0.5);
    padding: 0.75rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

/* Mobile and Small Device Adjustments */
@media (max-width: 768px) {
    /* Adjust form padding and title font size for tablets */
    .con1 {
        padding: 1.5rem;
    }

    h1 {
        font-size: 1.75rem;
    }

    input, button {
        padding: 1rem;
    }

    .con2 {
        max-width: 100%;
    }
}

/* Smaller Devices (Phones) */
@media (max-width: 480px) {
    /* Further adjust form padding and title font size */
    .con1 {
        padding: 1.25rem;
        max-width: 90%; /* Make form more responsive on smaller screens */
    }

    h1 {
        font-size: 1.5rem;
    }

    input, button {
        padding: 0.75rem;
        font-size: 1rem; /* Slightly reduce font size for smaller screens */
    }

    #p1 {
        font-size: 0.8rem;
    }

    .error-msg {
        font-size: 0.75rem;
    }
}

/* Extra Small Devices */
@media (max-width: 320px) {
    h1 {
        font-size: 1.25rem;
    }

    input, button {
        padding: 0.75rem;
        font-size: 0.9rem;
    }

    #p1 {
        font-size: 0.7rem;
    }

    .error-msg {
        font-size: 0.7rem;
    }
}


</style>
<body>
<div class="con1">
    <h1>Login</h1>
    <form action="" method="post">
        <?php
        if (isset($error_message)) {
            echo '<span class="error-msg" id="errorContainer">' . $error_message . '</span>';
        }
        ?>
        <div class="con2">
            <label for="username">Username</label><br/>
            <input type="text" id="username" name="username" placeholder="Enter username"/><br/>
            <label for="email">Email</label><br/>
            <input type="text" id="email" name="email" placeholder="Enter email"/><br/>
            <label for="password">Password</label><br/>
            <input type="password" id="password" name="password" placeholder="Enter password"/><br/>
        </div>
        <br/>
        <p id="p1">Don't have an account? <a href="registration.php">Sign Up</a></p>
        <br/><br/>
        <button id="login" name="regg">LOGIN</button>
    </form>
</div>
<script src="login.js"></script>
</body>
</html>

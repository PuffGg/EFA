<?php
$serverName = "localhost";
$userName = "root";
$password = "";
$database = "efa";

$connectDB = mysqli_connect($serverName, $userName, $password, $database);
$errors = [];

if (isset($_POST['reg'])) {
    $username = mysqli_real_escape_string($connectDB, trim($_POST['username']));
    $email = mysqli_real_escape_string($connectDB, trim($_POST['email']));
    $password = trim($_POST['password']);
    $cpassword = trim($_POST['cpassword']);
    $role_id = $_POST['role']; // Role ID should be selected from the roles table

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format!';
    }

    // Check if passwords match
    if ($password != $cpassword) {
        $errors[] = 'Passwords do not match!';
    }

    // Check if user already exists
    $select_query = "SELECT * FROM users WHERE email=?";
    $stmt = mysqli_prepare($connectDB, $select_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $errors[] = 'User already exists!';
    } else {
        // Check admin limit
        if ($role_id == 2) { // Assuming '1' is for ADMINISTRATOR
            $stmt = mysqli_prepare($connectDB, "SELECT COUNT(*) AS admin_count FROM user_role WHERE role_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $role_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin_count = mysqli_fetch_assoc($result)['admin_count'];

            if ($admin_count >= 2) {
                $errors[] = 'Admin registration limit reached. Cannot register more admins.';
            }
        }

        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare and execute an INSERT query for the users table
            $insert_user_query = "INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($connectDB, $insert_user_query);
            mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $hashed_password, $role_id);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($connectDB);

            // Insert into user_role table
            $insert_role_query = "INSERT INTO user_role (user_id, role_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($connectDB, $insert_role_query);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $role_id);
            mysqli_stmt_execute($stmt);

            // Redirect to login page after successful registration
            header('Location: login.php');
            exit(); // Always exit after redirecting
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registration Page</title>
    <link rel="stylesheet" href="rege.css" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>
<style>


    </style>
<body>
    <div class="con1">
        <h1>Register</h1>
          <!-- Loading bar -->
    <div id="loading-bar"></div>
        <form action="" method="post" onsubmit="return validateForm()">
            <br /><br />
            <?php
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo '<span class="error-msg">' . $error . '</span>';
                }
            }
            ?>
            <div class="con2">
                <label for="username">Username</label><br />
                <input type="text" id="username" name="username" placeholder="Enter username" /><br />
                <label for="email">Email</label><br />
                <input type="text" id="email" name="email" placeholder="Enter email" /><br />
                <label for="password">Password</label><br />
                <input type="password" id="password" name="password" placeholder="Enter Password" /><br />
                <label for="cpassword">Confirm Password</label><br />
                <input type="password" id="cpassword" name="cpassword" placeholder="Confirm Password" /><br /><br />
                <label for="role">Role</label><br />
                <select name="role" id="role">
                    <option value="1">MEMBER</option>
                    <option value="2">ADMINISTRATOR</option>
                </select>
            </div>
            <p id="p1">Already have an account? <a href="login.php">Sign In</a></p>
            <br /><br />
            <button id="register" name="reg">REGISTER</button>
        </form>
        <div id="loading-bar"></div>
    </div>
    <script src="registration.js"></script>
</body>
</html>

<?php
session_start(); // Start the session to access session variables

$error_message = ""; // Initialize error message variable
$success_message = ""; // Initialize success message variable

// Check if the user is logged in and get the user ID from the session
if (!isset($_SESSION['user_id'])) {
    $error_message = "User not logged in!";
} else {
    $user_id = $_SESSION['user_id'];

    if (isset($_POST['submit'])) {
        // Retrieve form data
        $firstname = $_POST["first_name"];
        $lastname = $_POST["last_name"];
        $membershipcode = $_POST["membership_code"];
        $gender = $_POST["gender"];
        $contact = $_POST["contact"];
        $dob = $_POST["dob"];  // This comes in the format mm/dd/yyyy
        $department_ids = isset($_POST["departments"]) ? $_POST["departments"] : [];  // Array of selected departments
        $creg = filter_input(INPUT_POST, "creg", FILTER_VALIDATE_BOOLEAN);  // Whether the registration is complete

        if (!$creg) {
            $error_message = "Incomplete registration process!!"; // Set error message
        } else {
            // Convert date format from mm/dd/yyyy to yyyy-mm-dd
            $dateObject = DateTime::createFromFormat('m/d/Y', $dob);
            
            if ($dateObject) {
                $dob = $dateObject->format('Y-m-d');  // Convert to yyyy-mm-dd
            } else {
                $error_message = "Invalid date format. Please use mm/dd/yyyy.";
            }

            // Database connection
            $serverName = "localhost";
            $userName = "root";
            $password = "";
            $database = "efa";
            
            $connectDB = mysqli_connect($serverName, $userName, $password, $database);
            if (mysqli_connect_errno()) {
                die("Connection error: " . mysqli_connect_error());
            }

            // Step 1: Update user information in the users table
            $sql = "UPDATE users 
                    SET first_name = ?, last_name = ?, membership_code = ?, gender = ?, contact = ?, dob = ?, registered = ? 
                    WHERE user_id = ?";
            
            $stmt = mysqli_stmt_init($connectDB);
            if (!mysqli_stmt_prepare($stmt, $sql)) {
                $error_message = "Database error: " . mysqli_error($connectDB); // Set error message
            } else {
                mysqli_stmt_bind_param($stmt, "ssssssii", $firstname, $lastname, $membershipcode, $gender, $contact, $dob, $creg, $user_id);
                mysqli_stmt_execute($stmt);

                // Step 2: Update selected departments in the user_departments table
                if (!empty($department_ids)) {
                    // First, delete old entries in user_departments for this user
                    $delete_sql = "DELETE FROM user_departments WHERE user_id = ?";
                    $stmt = mysqli_prepare($connectDB, $delete_sql);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);

                    // Insert each department selection for the user
                    foreach ($department_ids as $department_id) {
                        $insert_sql = "INSERT INTO user_departments (user_id, department_id) VALUES (?, ?)";
                        $stmt = mysqli_prepare($connectDB, $insert_sql);
                        mysqli_stmt_bind_param($stmt, "ii", $user_id, $department_id);
                        mysqli_stmt_execute($stmt);
                    }
                }

                // Step 3: Fetch the user_dept_id from the user_departments table for the updated user
                $select_sql = "SELECT user_dept_id FROM user_departments WHERE user_id = ? ORDER BY user_dept_id DESC LIMIT 1";
                $stmt = mysqli_prepare($connectDB, $select_sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user_dept_row = mysqli_fetch_assoc($result);
                $user_dept_id = $user_dept_row['user_dept_id'];

                // Step 4: Update the users table with the new user_dept_id
                $update_user_sql = "UPDATE users SET user_dept_id = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($connectDB, $update_user_sql);
                mysqli_stmt_bind_param($stmt, "ii", $user_dept_id, $user_id);
                mysqli_stmt_execute($stmt);

                // Step 5: Set the success message
                $success_message = "Information successfully updated"; 

                // Step 6: Redirect to another page after successful update
                header("Location: user.php");
                exit();
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Update User Information</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>
<style>
    /* Base styles */
    body {
        font-family: 'Roboto', sans-serif;
        background-color: #f7f7f7;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 60%;
        margin: 50px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
        text-align: center;
        color: #333;
    }

    label {
        font-size: 16px;
        color: #333;
        margin-bottom: 5px;
        display: block;
    }

    button {
        background: rgba(66, 153, 225, 0.9);
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
        margin-top: 10px;
    }

    button:hover {
        background-color: #45a049;
    }

    /* Dropdown styling */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropbtn {
        background: rgba(66, 153, 225, 0.9);
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        width: 100%;
    }

    .dropbtn:hover {
        background-color: #45a049;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: white;
        min-width: 160px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
        padding: 10px;
        border-radius: 4px;
        width: 100%;
    }

    .dropdown-content label {
        display: block;
        margin-bottom: 5px;
    }

    .show {
        display: block;
    }

    .error {
        color: red;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .success {
        color: green;
        font-weight: bold;
        margin-bottom: 20px;
    }
</style>
<body>
    <div class="container">
        <?php if($error_message !== ""): ?>
            <div class="error"><?php echo $error_message; ?></div> <!-- Display error message -->
        <?php endif; ?>
        <?php if($success_message !== ""): ?>
            <div class="success"><?php echo $success_message; ?></div> <!-- Display success message -->
        <?php endif; ?>

        <form action="" method="post">
            <label for="firstname">FIRST NAME: </label>
            <input type="text" id="firstname" name="first_name" placeholder="Enter firstname" required/><br /><br />

            <label for="lastname">LAST NAME: </label>
            <input type="text" id="lastname" name="last_name" placeholder="Enter lastname" required/><br /><br />

            <label for="regno">MEMBERSHIP CODE: </label>
            <input type="text" id="regno" name="membership_code" placeholder="Enter Registration No" required/><br /><br />

            <label for="gender">GENDER:</label>
            <select id="gender" name="gender" required>
                <option value="M">MALE</option>
                <option value="F">FEMALE</option>
            </select><br /><br />

            <label for="dob">DATE OF BIRTH (yyyy-mm-dd): </label>
            <input type="date" id="dob" name="dob" placeholder="Enter date of birth (mm/dd/yyyy)" required /><br /><br />

            <label for="contact">CONTACT: </label>
            <input type="text" id="contact" name="contact" placeholder="Enter contact" required/><br /><br />

            <!-- Department Dropdown with checkboxes -->
            <label for="departments">Departments:</label>
            <div class="dropdown">
                <button type="button" class="dropbtn">Select Departments</button>
                <div id="departmentDropdown" class="dropdown-content">
                    <label><input type="checkbox" name="departments[]" value="1" /> ADMINISTRATION</label>
                    <label><input type="checkbox" name="departments[]" value="2" /> CHARITY</label>
                    <label><input type="checkbox" name="departments[]" value="3" /> SPECIAL NEEDS</label>
                    <label><input type="checkbox" name="departments[]" value="4" /> PROJECT MANAGEMENT</label>
                    <label><input type="checkbox" name="departments[]" value="5" /> YOUTH INITIATIVE</label>
                    <label><input type="checkbox" name="departments[]" value="6" /> ADVISORY BOARD</label>
                </div>
            </div>
            <br /><br />

            <label for="complete">COMPLETE REGISTRATION <input type="checkbox" id="complete" name="creg" required /></label>
            <br /><br />
            <button id="submit" name="submit">SUBMIT</button>
        </form>
    </div>

    <script>
        // Toggle the visibility of the dropdown content
        document.querySelector('.dropbtn').addEventListener('click', function() {
            document.getElementById("departmentDropdown").classList.toggle("show");
        });

        // Close the dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>
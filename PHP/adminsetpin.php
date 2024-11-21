<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user ID from session
$user_id = $_SESSION['user_id'];

// Define the error message variable
$error_message = "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['pin']) && count($_POST['pin']) == 4) {
        $pin = implode('', $_POST['pin']);

        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $database = "efa";

        $conn = new mysqli($servername, $username, $password, $database);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT a.hashed_pin, a.user_dept_id, u.department_id, d.department_name
                FROM admin_pins a
                JOIN user_departments u ON a.user_dept_id = u.user_dept_id
                JOIN departments d ON u.department_id = d.department_id
                WHERE a.user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($pin, $row['hashed_pin'])) {
                $department_name = $row['department_name'];
                switch ($department_name) {
                    case 'ADMINISTRATION':
                        header("Location: admin.php");
                        break;
                    case 'CHARITY':
                        header("Location: charitydept.html");
                        break;
                    case 'SPECIAL NEEDS':
                        header("Location: specialneeds.html");
                        break;
                    case 'PROJECT MANAGEMENT':
                        header("Location: projectmanagement.html");
                        break;
                    case 'YOUTH INITIATIVE':
                        header("Location: youthinitiative.html");
                        break;
                    case 'ADVISORY BOARD':
                        header("Location: advisoryboard.html");
                        break;
                    default:
                        echo "Unknown department.";
                        break;
                }
                exit();
            } else {
                $error_message = "Incorrect PIN.";
            }
        } else {
            $error_message = "User not found or no department assigned.";
        }

        $stmt->close();
        $conn->close();
    } else {
        $error_message = "PIN input is incomplete.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin PIN Entry</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
      body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f0f0;
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
    right: 20px;
    transform: translateY(-50%);
}

.box:hover {
    color: #ffd700;
}

.pin-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 300px;
    text-align: center;
    margin: 50px auto;
    box-sizing: border-box;
}

input[type="password"] {
    width: 40px;
    height: 40px;
    text-align: center;
    font-size: 24px;
    margin: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    margin-top: 10px;
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    border-radius: 4px;
    background-color: #007bff;
    color: white;
    cursor: pointer;
    width: 100%;
}

button:hover {
    background-color: green;
}

.error-message {
    color: red;
    margin-bottom: 10px;
}

/* Responsive Adjustments */
@media screen and (max-width: 480px) {
    header {
        flex-direction: column;
        padding: 15px;
        font-size: 20px;
    }

    .box {
        position: static;
        transform: none;
        margin-top: 10px;
        justify-content: center;
        width: 100%;
        text-align: center;
    }

    .pin-container {
        margin: 20px auto;
        padding: 15px;
        width: 90%;
        max-width: none;
    }

    input[type="password"] {
        width: 35px;
        height: 35px;
        font-size: 20px;
        margin: 3px;
    }

    button {
        font-size: 14px;
        padding: 8px 15px;
    }
}

/* Improved Responsiveness for Small Tablets */
@media screen and (min-width: 481px) and (max-width: 768px) {
    header {
        padding: 15px;
        font-size: 22px;
    }

    .pin-container {
        margin: 30px auto;
        width: 80%;
        max-width: 350px;
    }
}
    </style>
</head>
<body>
    <header>
        
    
        <a href="admindept.php" class="box">
            <i class="fa fa-home"></i> Departments Board
        </a>
    </header>

    <div class="pin-container">
        <h2>Enter Admin PIN</h2>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="adminsetpin.php" method="POST" id="pin-form">
            <div>
                <input type="password" name="pin[]" maxlength="1" required autofocus oninput="moveFocus(event, 1)">
                <input type="password" name="pin[]" maxlength="1" required oninput="moveFocus(event, 2)">
                <input type="password" name="pin[]" maxlength="1" required oninput="moveFocus(event, 3)">
                <input type="password" name="pin[]" maxlength="1" required oninput="moveFocus(event, 4)">
            </div>
            <button type="submit">Submit PIN</button>
        </form>
    </div>

    <script>
        function moveFocus(event, nextField) {
            if (event.target.value.length === 1 && nextField <= 3) {
                document.getElementsByName('pin[]')[nextField].focus();
            }
        }
    </script>
</body>
</html>

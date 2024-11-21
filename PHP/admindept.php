<?php
session_start();

// Assuming user_id is stored in session
$user_id = $_SESSION['user_id'] ?? null;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "efa"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hasPin = false; // Default if user doesn't have a PIN
if ($user_id) {
    // Query to fetch the hashed PIN and department info
    $sql = "SELECT a.hashed_pin
            FROM admin_pins a
            WHERE a.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['hashed_pin'])) {
            $hasPin = true;
        }
    }
    $stmt->close();
}

// Fetch departments
$sql = "SELECT department_id, department_name FROM departments";
$result = $conn->query($sql);

$departments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}


$sql = "
  SELECT 
      first_name, 
      last_name, 
      username, 
      email, 
      membership_code,
      dob, 
      contact, 
      gender, 
      created_at
  FROM users
  WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);  // "i" denotes an integer parameter
$stmt->execute();
$result = $stmt->get_result();

// Check if data was fetched successfully
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();  // Fetch data as an associative array
} else {
    die("User not found");
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f6fa;
            color: #333;
        }

        header {
            background-color: #0078d7;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            position: relative; /* Enables positioning for child elements */
        }

        .header:hover {
            color: #ffd700; /* Adds hover effect */
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

        main {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #0078d7;
        }
        
        p{text-align: center;}

        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .link-item {
            padding: 20px;
            text-align: center;
            background-color: #0078d7;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .link-item:hover {
            background-color: #005fa3;
        }

        .restricted {
            filter: blur(4px);
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .highlight {
            border: 2px solid #ffcc00;
        }

        footer {
            background-color: #0078d7;
            color: white;
            text-align: center;
            font-size: 14px;
            padding: 20px;
            margin-top: auto; /* This pushes the footer to the bottom */
        }

        @media (max-width: 600px) {
            body {
                font-size: 16px;
            }

            header, footer {
                font-size: 18px;
                padding: 15px;
            }

            .link-item {
                font-size: 14px;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <header>
        DEPARTMENT CHOICE DASHBOARD
    </header>

    <main>
    </div>
            <h2>Welcome,</span> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>
        </div>
        <p>Select a section to proceed to your dashboard:</p>

        <div class="links">
            <?php foreach ($departments as $department): ?>
                <a href="department.php?id=<?= $department['department_id']; ?>" 
                   class="link-item">
                    <?= htmlspecialchars($department['department_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</body>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    // Fetch PHP variable indicating if the user has a PIN
    const hasPin = <?= json_encode($hasPin); ?>;

    document.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", event => {
        event.preventDefault(); // Prevent the default action

        if (hasPin) {
          window.location.href = "adminsetpin.php"; // Redirect to adminsetpin.php if user has a PIN
        } else {
          window.location.href = "adminpin.php"; // Redirect to adminpin.php otherwise
        }
      });
    });
  });
</script>


</html>

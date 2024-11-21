<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #333;
            color: #fff;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>

<h1>Membership Details</h1>

<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to fetch membership details
    $sql = "SELECT user_id, username, email, role_id, first_name, last_name, membership_code, gender, dob, contact, department_id, registered 
            FROM users";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Check if any results were returned
    if ($stmt->rowCount() > 0) {
        echo "<table>";
        echo "<tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Membership Code</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>Contact</th>
                <th>Department ID</th>
                <th>Registered</th>
              </tr>";

        // Display each row from the database
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['user_id']}</td>
                    <td>{$row['username']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['role_id']}</td>
                    <td>{$row['first_name']}</td>
                    <td>{$row['last_name']}</td>
                    <td>{$row['membership_code']}</td>
                    <td>{$row['gender']}</td>
                    <td>{$row['dob']}</td>
                    <td>{$row['contact']}</td>
                    <td>{$row['department_id']}</td>
                    <td>" . ($row['registered'] ? 'Yes' : 'No') . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No membership details found.</p>";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

</body>
</html>

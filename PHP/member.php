<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";  // Adjust to your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user ID from session or URL parameter (sanitize and validate as needed)
session_start();
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'];  // Example, adapt as necessary

// Prepare SQL query to fetch user data and associated departments
$sql = "
  SELECT 
      u.first_name, 
      u.last_name, 
      u.username, 
      u.email, 
      u.membership_code,
      u.dob, 
      u.contact, 
      u.gender, 
      u.created_at,
      r.role_name,
      GROUP_CONCAT(d.department_name ORDER BY d.department_name ASC) AS department_names
  FROM users u
  JOIN roles r ON u.role_id = r.role_id
  JOIN user_departments ud ON u.user_id = ud.user_id  -- Join user_departments to get user_dept_id
  JOIN departments d ON ud.department_id = d.department_id  -- Join departments to get department_name
  WHERE u.user_id = ? 
  GROUP BY u.user_id";  // Group departments for the user into a comma-separated list

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

// Total Meetings: Hardcoded to 52 meetings (assuming one per week)
$totalMeetings = 52;

// Total Attended: Sum of meetings attended by the logged-in user (start_time and end_time not NULL)
$totalAttendedQuery = $conn->prepare("SELECT COUNT(*) FROM meetings WHERE user_id = ? AND start_time IS NOT NULL AND end_time IS NOT NULL");
$totalAttendedQuery->bind_param("i", $user_id);
$totalAttendedQuery->execute();
$totalAttendedResult = $totalAttendedQuery->get_result();
$totalAttended = $totalAttendedResult->fetch_column();

// Total Missed: Number of weeks missing (weeks without start_time or end_time)
$totalMissed = 0;

// Get the current year
$currentYear = date('Y');

// Fetch all weeks in the current year that the user missed (no start_time and end_time)
$missedWeeksQuery = $conn->prepare("
    SELECT DISTINCT WEEK(meeting_date) AS week 
    FROM meetings 
    WHERE user_id = ? 
    AND YEAR(meeting_date) = ?
    AND (start_time IS NULL OR end_time IS NULL)
");
$missedWeeksQuery->bind_param("ii", $user_id, $currentYear);
$missedWeeksQuery->execute();
$missedWeeksResult = $missedWeeksQuery->get_result();

// Calculate missed weeks count
$totalMissed = $missedWeeksResult->num_rows;

// Total Remaining: Total meetings - (attended + missed)
$totalRemaining = $totalMeetings - ($totalAttended + $totalMissed);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="memba.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    
</head>
<body>

    <div class="container">

        <div class="box total-meetings">
            <h3> <span1>Full Name:</span> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h3>
            <h3>  <span1>Membership Code:</span> <?php echo htmlspecialchars($user_data['membership_code']); ?></h3>
            <h3><span1>Email:</span> <?php echo htmlspecialchars($user_data['email']); ?></h3>
        </div>

                <div class="box total-meetings">
            <h3>Total Meetings</h3>
            <p><?php echo $totalMeetings; ?></p>
        </div>
        <div class="box total-meetings">
            <h3>Total Attended</h3>
            <p><?php echo $totalAttended; ?></p>
        </div>
        <div class="box total-meetings">
            <h3>Total Missed</h3>
            <p><?php echo $totalMissed; ?></p>
        </div>
        <div class="box total-meetings">
            <h3>Total Remaining</h3>
            <p><?php echo $totalRemaining; ?></p>
        </div>
        <div class="box note">
            <a href="calender.html" class="box">
                <h3>Calendar</h3>
            </a>
        </div>

        
    </div>

    
    <footer>
        <p>&copy; 2024 Eben Foundation Africa 🌍. All rights reserved.</p>
    </footer>

    <script src="membership.js"></script>

</body>
</html>
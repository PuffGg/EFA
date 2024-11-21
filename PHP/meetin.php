<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Only execute the following code for POST requests (AJAX calls)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "efa";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }

    // Handle yearly data request
    if (isset($_POST['yearly']) && $_POST['yearly'] === 'true') {
        $year = date("Y"); // Current year

        // Prepare SQL to select all meetings for the current year
        $sql = "SELECT month, SUM(TIME_TO_SEC(total_duration)) AS total_duration 
                FROM Meetings 
                WHERE year = ? 
                GROUP BY month";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $monthData = [];
        while ($row = $result->fetch_assoc()) {
            $month = $row['month'];
            $totalDuration = $row['total_duration'] / 3600; // Convert seconds to hours
            $monthData[$month] = $totalDuration;
        }

        $monthsOrder = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        $orderedMonthData = [];
        foreach ($monthsOrder as $month) {
            $orderedMonthData[] = [
                'month' => $month,
                'total_duration' => isset($monthData[$month]) ? round($monthData[$month], 2) : 0
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $orderedMonthData]);

        $stmt->close();
        $conn->close();
        exit();
    }

    // Handle meeting data insertion
    if (isset($_POST['month'], $_POST['week'], $_POST['startTime'], $_POST['endTime'])) {
        $month = $_POST['month'];
        $week = intval($_POST['week']) + 1;
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];

        // Check if the user is logged in (assuming user ID is stored in the session)
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
            $conn->close();
            exit();
        }

        try {
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);
            $interval = $start->diff($end);
            $totalDuration = $interval->format('%H:%I:%S');

            if ($end < $start) {
                echo json_encode(['status' => 'error', 'message' => 'End time must be after start time']);
                $conn->close();
                exit();
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid time format']);
            $conn->close();
            exit();
        }

        $year = date("Y");

        $sql = "INSERT INTO Meetings (meeting_date, start_time, end_time, total_duration, week, month, year, user_id) 
                VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssis", $startTime, $endTime, $totalDuration, $week, $month, $year, $userId);

        if ($stmt->execute()) {
            // Success - Retrieve updated data for the first chart (monthly/week data)
            $chart_data = [];
            $sql_chart = "SELECT meeting_date, TIME_TO_SEC(total_duration) AS total_duration 
                          FROM Meetings 
                          WHERE month = ? AND year = ? AND user_id = ?";
            $stmt_chart = $conn->prepare($sql_chart);
            $stmt_chart->bind_param("sii", $month, $year, $userId);
            $stmt_chart->execute();
            $result_chart = $stmt_chart->get_result();

            while ($row = $result_chart->fetch_assoc()) {
                $chart_data[] = [
                    'meeting_date' => $row['meeting_date'],
                    'total_duration' => $row['total_duration'] / 3600 // Convert seconds to hours
                ];
            }

            echo json_encode(['status' => 'success', 'data' => $chart_data]);

            $stmt_chart->close();
        } else {
            file_put_contents('log.txt', "Error inserting data: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(['status' => 'error', 'message' => 'Error inserting data']);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    }

    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Hours Tracker</title>
    <link rel="stylesheet" type="text/css" href="meetin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
</head>
<style>
    .current-day {
        font-family: Arial, sans-serif;
        font-size: 20px;
        color: #333;
        margin: 20px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    .label {
        font-weight: bold;
    }
    #timer {
        font-size: 20px;
        color: red;
    }
    .message {
        font-size: 18px;
        margin-top: 20px;
    }
    .error {
        color: red;
    }
    .success {
        color: green;
    }
</style>
<body>
    <div class="container">
        <div class="form-container">
            <form id="meetingForm" method="post">
            <h1>Current Week of the Month</h1>
                <p id="current-week"></p>
                <div class="current-day">
                    <span class="label">Today: </span> <span id="current-day"></span>
                </div>

                <label for="month">Month:</label>
                <select id="month" name="month">
                    <option value="January">January</option>
                    <option value="February">February</option>
                    <option value="March">March</option>
                    <option value="April">April</option>
                    <option value="May">May</option>
                    <option value="June">June</option>
                    <option value="July">July</option>
                    <option value="August">August</option>
                    <option value="September">September</option>
                    <option value="October">October</option>
                    <option value="November">November</option>
                    <option value="December">December</option>
                </select>
                <br>
                <label for="week">Week:</label>
                <select id="week" name="week">
                    <option value="0">Week 1</option>
                    <option value="1">Week 2</option>
                    <option value="2">Week 3</option>
                    <option value="3">Week 4</option>
                    <option value="4">Week 5</option>
                </select>
                <br>
                <label for="startTime">Start Time (HH:MM):</label>
                <input type="time" id="startTime" name="startTime" required>
                <br>
                <label for="endTime">End Time (HH:MM):</label>
                <input type="time" id="endTime" name="endTime" required>
                <br>
                <button type="button" id="addMeetingButton">Add Meeting || Check graph</button>
            </form>
        </div>
    </div>
    <div id="responseMessage" class="message"></div>
                
                <!-- Timer Countdown -->
                <div id="timer"></div>

    <footer>
        <p>&copy; 2024 Eben Foundation Africa 🌍. All rights reserved.</p>
    </footer>

    <script>
        // Function to check if today is Friday, Saturday, or the last Sunday of the month
        function isValidMeetingDay() {
            const today = new Date();
            const dayOfWeek = today.getDay();  // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            const currentDate = today.getDate();

            // Check if today is Friday (5), Saturday (6), or last Sunday of the month
            if (dayOfWeek === 5 || dayOfWeek === 6 || isLastSundayOfMonth(today)) {
                return true; // Valid day for meeting
            }
            return false; // Invalid day for meeting
        }

        // Function to check if today is the last Sunday of the month
        function isLastSundayOfMonth(date) {
            const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
            const lastSunday = lastDay.getDate() - lastDay.getDay(); // Get the date of the last Sunday

            return date.getDate() === lastSunday;
        }

        // Function to restrict the time selection between 4:00 PM and 4:10 PM
        function restrictTime() {
            const startTimeField = document.getElementById('startTime');
            const endTimeField = document.getElementById('endTime');

            const timeFormat = "16:00";  // 4:00 PM
            const endTime = "16:25";     // 4:10 PM

            startTimeField.setAttribute('min', timeFormat);
            startTimeField.setAttribute('max', endTime);
            endTimeField.setAttribute('min', timeFormat);
            endTimeField.setAttribute('max', endTime);
        }

        // Set the current week in the HTML
        function getCurrentWeekOfMonth() {
            const today = new Date();
            const dayOfMonth = today.getDate();
            return Math.ceil(dayOfMonth / 7);
        }

        const currentWeek = getCurrentWeekOfMonth();
        document.getElementById("current-week").textContent = "We are in Week " + currentWeek;

        // Get the current day and display it in the HTML
        function getCurrentDay() {
            const today = new Date();
            const daysOfWeek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            const dayOfWeek = today.getDay(); // Get the day of the week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)

            const currentDate = today.toISOString().split('T')[0]; // Format the date as YYYY-MM-DD

            return `${daysOfWeek[dayOfWeek]}, ${currentDate}`; // Return formatted string like "Monday, 2024-11-14"
        }

        // Display the current day in the HTML
        document.getElementById("current-day").textContent = getCurrentDay();

        // Disable time inputs if today is not a valid meeting day
        if (!isValidMeetingDay()) {
            document.getElementById('startTime').disabled = true;
            document.getElementById('endTime').disabled = true;
            alert("Meetings can only be scheduled on Fridays, Saturdays, or the last Sunday of the month.");
        } else {
            // Restrict time selection to 4:00 PM to 4:10 PM
            restrictTime();
        }
    </script>
</body>
</html>

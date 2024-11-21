<?php
session_start();

// Check if user is logged in (session holds user_id)
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Database connection (adjust your database parameters as needed)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";  // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$user_id = $_SESSION['user_id'];
$query = "SELECT username FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

$date = date("F j, Y");

// Fetch the user's role from the user_role table
$query = "SELECT role_id FROM user_role WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($role_id);
$stmt->fetch();
$stmt->close();

// If role_id is 2 (Admin), allow access to the page, else redirect to user.php
if ($role_id == 2) {
    // User is an admin, continue with loading the page
    // Display admin content
} else {
    // User is not an admin, redirect to user page
    header("Location: user.php");
    exit();
}

// Calculate Total Users
$totalUsersQuery = "SELECT COUNT(DISTINCT user_id) AS total_users FROM users";
$resultTotalUsers = $conn->query($totalUsersQuery);
$totalUsers = 0;
if ($resultTotalUsers->num_rows > 0) {
    $row = $resultTotalUsers->fetch_assoc();
    $totalUsers = $row['total_users'];
}

// Fetch logged-in users for display
$loggedInUsers = [];
$filterDuration = isset($_GET['duration']) ? intval($_GET['duration']) : 1440; // Default to 24 hours
$timeLimit = date('Y-m-d H:i:s', strtotime("-$filterDuration minutes"));

// Adjust SQL to use user_id for both tables
$sql = "SELECT u.username, u.email, l.login_time 
        FROM users u 
        JOIN logins l ON u.user_id = l.user_id  /* Join on user_id */
        WHERE l.login_time > ? AND l.logout_time IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $timeLimit);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $loggedInUsers[] = $row;
    }
}

// Count the number of logged-in users
$loggedInUsersCount = count($loggedInUsers);


// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="addi.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($username); ?> | <?php echo $date; ?></p>
        <nav>
        <div class="container">
            <button class="menu-toggle1" id="menu-toggle">☰</button>
        </div>
            <a href="usermanagement.php"><i class="fas fa-users"></i> User Management</a>
            <a href="report.html"><i class="fas fa-file-alt"></i> Reports</a>
            <a href="#"><i class="fas fa-cogs"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <aside>
        <div class="container">
            <button class="menu-toggle1" id="menu-toggle">☰</button>
        </div>
        <ul>
            <li><a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard Overview</a></li>
            <li><a href="loaninterest.php"><i class="fas fa-user-cog"></i> Account Management</a></li>
            <li><a href="#staff"><i class="fas fa-users-cog"></i> Staff Management</a></li>
            <li><a href="investment.php"><i class="fas fa-hand-holding-usd"></i> Financial Transactions</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Notifications</a></li>
            <li><a href="user.php"><i class="fas fa-user-circle"></i> Personal Account</a></li>
        </ul>
    </aside>

    <main>
        <section class="overview">
            <div class="stat">
                <h3>Total Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>
            <div class="stat">
                <h3>Total Online Users</h3>
                <p><?php echo $loggedInUsersCount; ?></p>
            </div>
            <div class="stat">
                <h3>Total Contributions</h3>
                <p>$500,000</p>
            </div>
            <div class="stat">
                <h3>Total Withdrawals</h3>
                <p>$100,000</p>
            </div>
            <div class="stat">
                <h3>Total Interest Paid</h3>
                <p>$50,000</p>
            </div>
            <div class="stat">
                <h3>Total Account Balances</h3>
                <p>$450,000</p>
            </div>
        </section>

        <section class="charts">
            <div class="chart-container">
                <canvas id="contributionsChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="withdrawalsChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="interestChart"></canvas>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Eben Foundation Africa 🌍. All rights reserved.</p>
    </footer>

    <script>
        // Sample chart data
        var ctx = document.getElementById('contributionsChart').getContext('2d');
        var contributionsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May'],
                datasets: [{
                    label: 'Contributions',
                    data: [5000, 10000, 7500, 15000, 20000],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('aside');
            const mainContent = document.querySelector('main');

            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                mainContent.classList.toggle('sidebar-expanded');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('sidebar-expanded');
                }
            });
        });
    </script>
</body>
</html>

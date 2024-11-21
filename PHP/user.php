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

// Fetch the user's role from the user_role table
$query = "SELECT role_id FROM user_role WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($role_id);
$stmt->fetch();
$stmt->close();

// Redirect to admin page if role_id is 2
if ($role_id == 2) {
    // User is an admin, can access admin page
    // (This can be optional since you're already doing this check in admin.php)
} else {
    // User is not an admin, do not allow access to admin.php
    // The sidebar should still show, but we will prevent any admin actions.
}

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


// Fetch total deposits for the logged-in user
$sql_deposits = "
    SELECT COALESCE(SUM(i.amount), 0) AS total_deposits 
    FROM investments i
    JOIN user_transactions ut ON i.user_transactions_id = ut.user_transactions_id
    WHERE ut.user_id = ?
";
$stmt_deposits = $conn->prepare($sql_deposits);
$stmt_deposits->bind_param("i", $user_id);
$stmt_deposits->execute();
$result_deposits = $stmt_deposits->get_result();
$row_deposits = $result_deposits->fetch_assoc();
$total_deposits = $row_deposits['total_deposits'];

// Fetch total withdrawals for the logged-in user
$sql_withdrawals = "
    SELECT COALESCE(SUM(i.withdrawal), 0) AS total_withdrawals 
    FROM investments i
    JOIN user_transactions ut ON i.user_transactions_id = ut.user_transactions_id
    WHERE ut.user_id = ?
";
$stmt_withdrawals = $conn->prepare($sql_withdrawals);
$stmt_withdrawals->bind_param("i", $user_id);
$stmt_withdrawals->execute();
$result_withdrawals = $stmt_withdrawals->get_result();
$row_withdrawals = $result_withdrawals->fetch_assoc();
$total_withdrawals = $row_withdrawals['total_withdrawals'];

// Fetch current balance for the logged-in user (latest investment record)
$sql_current_balance = "
    SELECT i.balance 
    FROM investments i
    JOIN user_transactions ut ON i.user_transactions_id = ut.user_transactions_id
    WHERE ut.user_id = ? 
    ORDER BY i.created_at DESC 
    LIMIT 1
";
$stmt_balance = $conn->prepare($sql_current_balance);
$stmt_balance->bind_param("i", $user_id);
$stmt_balance->execute();
$result_balance = $stmt_balance->get_result();

if ($result_balance && $result_balance->num_rows > 0) {
    $row_balance = $result_balance->fetch_assoc();
    $current_balance = $row_balance['balance'];
} else {
    $current_balance = 0;
    $_SESSION['error'] = "No investment records found.";
}

// Fetch contributions per year for the logged-in user
$sql_contributions = "
    SELECT 
        YEAR(ut.transaction_date) AS year_time, 
        COALESCE(SUM(i.amount), 0) AS total_contributions
    FROM investments i
    JOIN user_transactions ut ON i.user_transactions_id = ut.user_transactions_id
    WHERE ut.user_id = ?
    GROUP BY YEAR(ut.transaction_date)
    ORDER BY YEAR(ut.transaction_date) ASC
";
$stmt_contributions = $conn->prepare($sql_contributions);
$stmt_contributions->bind_param("i", $user_id);
$stmt_contributions->execute();
$result_contributions = $stmt_contributions->get_result();

// Prepare data for the graph
$years = [];
$contributions = [];

while ($row = $result_contributions->fetch_assoc()) {
    $years[] = $row['year_time']; // Year for the x-axis
    $contributions[] = $row['total_contributions']; // Total contributions for the y-axis
}


// SQL query to fetch the image from the users table
$sql = "SELECT image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // Bind the user_id parameter
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($image_data); // Bind the result to the image_data variable

// Fetch the image data
if ($stmt->fetch()) {
    // Image data fetched successfully
    $image_base64 = base64_encode($image_data);  // Encode the image data as base64
} else {
    // Handle the case where no image is found
    $image_base64 = null;
}


// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Dashboard</title>
    <link rel="stylesheet" href="uz.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">

        <div class="profile">
                  <!-- Check if the image exists, and if so, display it -->
        <div class="profile-image-container">
            <?php if ($image_base64): ?>
                <img src="data:image/jpeg;base64,<?php echo $image_base64; ?>" alt="Profile Picture" width="150" height="150">
            <?php else: ?>
                <!-- Default image or placeholder if no image is found -->
                <img src="default-profile-pic.jpg" alt="Profile Picture" width="150" height="150">
            <?php endif; ?>
        </div>
            <h2></span> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h2>
        </div>
        <nav>
            <ul>
                <li><i class="fas fa-home"></i><a href="user.php">Home</a></li>
                <li><i class="fas fa-envelope"></i><a href="messages.php">Messages</a></li>
                <li><i class="fas fa-user"></i><a href="profile.php">Profile</a></li> 
                <li><i class="fas fa-cog"></i><a href="loanrequest.html">Settings</a></li>
                <li><i class="fas fa-sign-out-alt"></i><a href="logout.php">Sign Out</a></li>
              <?php
                    if ($role_id == 2) {
                        // Show Admin link only if role_id is 2
                        echo '<li><i class="fas fa-user-shield"></i><a href="admin.php" id="adminLink">Admin</a></li>';
                    }
                    ?>
            </ul>
        </nav>
    </div>
    <div class="container">
        <header>
        <button class="menu-toggle1" id="menu-toggle">☰</button>

            <h1>Dashboard</h1>
        </header>
        <main>
            <section class="main-content">
                <div class="boxes">
                    <a href="member.php" class="box">Membership Details</a>
                    <a href="meetings.php" class="box">Meetings</a>
                    <a href="usercontibutions.php" class="box">Contributions</a>
                </div>
                <div class="graph">
                    <h3>Contributions Graph</h3>
                    <canvas id="contributionsChart"></canvas>
                </div>
            </section>
        </main>
    </div>

    <footer>
        <p>&copy; 2024 Eben Foundation Africa 🌍. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    var years = <?php echo json_encode($years); ?>;
    var contributions = <?php echo json_encode($contributions); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('contributionsChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'bar', // You can also try 'line', 'doughnut', or other types for different styles
                data: {
                    labels: years, 
                    datasets: [{
                        label: 'Contributions',
                        data: contributions,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',  // Soft blue background
                        borderColor: 'rgba(54, 162, 235, 1)',  // Darker blue border
                        borderWidth: 1.5,  // Thicker borders
                        hoverBackgroundColor: 'rgba(54, 162, 235, 0.8)',  // Hover effect
                        hoverBorderColor: 'rgba(54, 162, 235, 1)',  // Hover border color
                        hoverBorderWidth: 2,  // Thicker border on hover
                    }]
                },
                options: {
                    responsive: true, // Makes the chart responsive to window size
                    plugins: {
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',  // Darker tooltip background
                            titleColor: '#fff',  // White title in tooltip
                            bodyColor: '#fff',  // White body text in tooltip
                            borderColor: '#ccc',  // Light border around tooltip
                            borderWidth: 1,
                            displayColors: false,  // Hide color box in tooltips
                        },
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    family: "'Roboto', sans-serif",  // Custom font
                                    size: 14,  // Font size for legend
                                    weight: 'bold',  // Font weight for legend
                                },
                                color: '#333',  // Legend text color
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: true,  // Show grid lines
                                color: '#ddd',  // Light grid color
                            },
                            ticks: {
                                font: {
                                    family: "'Roboto', sans-serif",  // Custom font
                                    size: 12,  // Font size for x-axis ticks
                                    weight: '500',
                                },
                                color: '#333',  // Color for x-axis ticks
                            }
                        },
                        y: {
                            grid: {
                                display: true,
                                color: '#ddd',  // Light grid color
                            },
                            ticks: {
                                beginAtZero: true,  // Ensures the y-axis starts from 0
                                font: {
                                    family: "'Roboto', sans-serif",  // Custom font
                                    size: 12,  // Font size for y-axis ticks
                                    weight: '500',
                                },
                                color: '#333',  // Color for y-axis ticks
                            }
                        }
                    },
                    elements: {
                        bar: {
                            borderRadius: 8,  // Rounded corners for bars
                        }
                    }
                }
            });
        }
    });

    // Filter function to update chart data dynamically
    function filterData(min, max) {
        // Send AJAX request to server to get filtered data
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'filter_data.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                const filteredYears = data.years;
                const filteredContributions = data.contributions;

                // Update the chart with the filtered data
                const chart = Chart.getChart('contributionsChart'); // get chart instance
                chart.data.labels = filteredYears;
                chart.data.datasets[0].data = filteredContributions;
                chart.update();
            }
        };
        xhr.send('min=' + min + '&max=' + max);
    }
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const container = document.getElementById('container');
            const menuToggle = document.getElementById('menu-toggle');

            menuToggle.addEventListener('click', function() {
                // Toggle sidebar classes
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('show');
                
                // Toggle container margin and width
                container.classList.toggle('sidebar-hidden');
            });
        });

        

</script>
</body>

</html>

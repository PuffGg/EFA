<?php
// Start the session
session_start();

// Database connection (adjust with your credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "efa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if the user is not logged in
    $_SESSION['error'] = "You need to log in to access this page.";
    header('Location: login.php');
    exit();
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

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



// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Account Summary</title>
    <link rel="stylesheet" href="usercontributions.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Modern CSS Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2563eb;
            --secondary-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --border-radius: 0.75rem;
        }

        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.5;
            padding: 1.5rem;
        }

        header,h1{
            align-items: center;
            text-align: center;
        }

        /* Container Layout */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            gap: 2rem;
            grid-template-columns: 1fr;
        }

        /* Message Box Styling */
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: var(--border-radius);
            color: #fff;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message.success {
            background-color: var(--success-color);
        }

        .message.error {
            background-color: #ef4444; /* Red background for error */
        }

        .message .close-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Account Summary Section */
        .account-summary {
            margin-bottom: 2rem;
        }

        .account-summary h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .summary-item {
            background-color: var(--card-background);
            padding: 1.25rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1rem;
        }

        .summary-item p {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        /* Footer Styling */
        footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        footer p {
            color: var(--text-secondary);
        }

        /* JavaScript for message hiding */
        .hidden {
            display: none;
        }

        .graph {
    margin-top: 30px;
}

.graph h3 {
    font-size: 1.25rem;
    color: #4a5568;
    margin-bottom: 20px;
    font-weight: 600;
}

    /* Filter Buttons Styling */
    .filter-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 20px;
    }

    .filter-buttons button {
        padding: 10px 20px;
        background-color: #2563eb; /* Primary blue */
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .filter-buttons button:hover {
        background-color: #1d4ed8; /* Darker blue on hover */
        transform: translateY(-2px); /* Slight elevation effect */
    }

    .filter-buttons button:active {
        background-color: #1e40af; /* Darker blue on click */
        transform: translateY(0); /* Reset elevation on click */
    }

    .filter-buttons button:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5); /* Focus ring */
    }

        .chart-container {
      width: 80%;
      max-width: 800px;
      margin: 40px auto;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 30px;
    }

    #contributionsChart {
      max-height: 500px;
    }
    </style>
</head>
<body>
    <!-- Display Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error" id="error-message">
            <?php echo $_SESSION['error']; ?>
            <button class="close-btn" onclick="closeMessage('error-message')">x</button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="message success" id="success-message">
            <?php echo $_SESSION['success']; ?>
            <button class="close-btn" onclick="closeMessage('success-message')">x</button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <header>
        <h1>Account Summary</h1>
    </header>

    <main class="container">
        <!-- Money Market Account Summary -->
        <section class="account-summary">
            <h2>MONEY MARKET</h2>
            <div class="summary-item">
                <p>Summary of Contributions: Ksh <?php echo number_format($total_deposits, 2); ?></p>
            </div>
            <div class="summary-item">
                <p>Current Month Contribution: Ksh 500</p>
            </div>
            <div class="summary-item">
                <p>Interest Earned: Ksh 300</p>
            </div>
            <div class="summary-item">
                <p>Amount Withdrawn: Ksh <?php echo number_format($total_withdrawals, 2); ?></p>
            </div>
            <div class="summary-item">
                <p>Balance: Ksh <?php echo number_format($current_balance, 2); ?></p>
            </div>
        </section>

        <!-- Savings Account Summary -->
        <section class="account-summary">
            <h2>SAVINGS</h2>
            <div class="summary-item">
                <p>Summary savings Contributions: Ksh 0</p>
            </div>
            <div class="summary-item">
                <p>Current Month savings Contribution: Ksh 0</p>
            </div>
            <div class="summary-item">
                <p>Amount Withdrawn: Ksh 0</p>
            </div>
            <div class="summary-item">
                <p>Balance: Ksh 0</p>
            </div>
        </section>
    </main>
    <div class="filter-buttons">
    <button onclick="filterData(100, 1000)">Ksh 100 - Ksh 1000</button>
    <button onclick="filterData(500, 10000)">Ksh 500 - Ksh 10,000</button>
    <button onclick="filterData(100, 10000)">Ksh 100 - Ksh 10,000</button>
</div>

    <div class="graph">
        <h3>Contributions Graph</h3>
        <canvas id="contributionsChart"></canvas>
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

    // Function to close the message box
function closeMessage(messageId) {
    // Get the message element by its ID
    var messageElement = document.getElementById(messageId);

    // If the message exists, hide it by adding the 'hidden' class
    if (messageElement) {
        messageElement.classList.add('hidden');
    }
}

    
</script>

</body>
</html>

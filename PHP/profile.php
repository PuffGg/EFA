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


// Close the database connectio
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Profile | Charity Organization</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="profile.css">
  <link rel="stylesheet" href="uz.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Basic Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Roboto', Arial, sans-serif;
    }

    body {
      background-color: #f3f4f6;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: #333;
    }

    .profile-container {
      background-color: #fff;
      width: 90%;
      max-width: 800px;
      border-radius: 10px;
      box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
      overflow: hidden;
   
    }

    .header {
      background-color: #0066cc;
      color: #fff;
      padding: 24px;
      text-align: center;
    }

    .header h1 {
      font-size: 1.75rem;
      font-weight: 600;
    }

    .profile-content {
      display: flex;
      flex-direction: column;
      padding: 32px;
    }

    .profile-info, .department-info, .work-details {
      margin-bottom: 24px;
    }

    .profile-info h2, .department-info h2, .work-details h2 {
      font-size: 1.375rem;
      color: #0066cc;
      margin-bottom: 12px;
      font-weight: 500;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      color: #555;
      font-size: 0.9rem;
    }

    .info-item span {
      font-weight: 500;
    }

    .work-details ul {
      list-style: none;
    }

    .work-details ul li {
      background-color: #e9f1ff;
      padding: 12px;
      margin-bottom: 12px;
      border-radius: 6px;
      font-size: 0.9rem;
    }

    .btn-edit-profile {
      display: inline-block;
      background-color: #0066cc;
      color: #fff;
      text-align: center;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }

    .btn-edit-profile:hover {
      background-color: #005bb5;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .profile-content {
        padding: 24px;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .profile-info h2, .department-info h2, .work-details h2 {
        font-size: 1.125rem;
      }

      .info-item {
        font-size: 0.85rem;
      }

      .work-details ul li {
        font-size: 0.85rem;
      }

      .btn-edit-profile {
        padding: 10px 20px;
        font-size: 0.85rem;
      }
    }
  </style>
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
        <span></span> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
        </div>
        <nav>
            <ul>
                <li><i class="fas fa-home"></i><a href="user.php">Home</a></li>
                <li><i class="fas fa-envelope"></i><a href="loanrequest.html">Messages</a></li>
                <li><i class="fas fa-user"></i><a href="profile.php">Profile</a></li> 
                <li><i class="fas fa-cog"></i><a href="#">Settings</a></li>
                <li><i class="fas fa-sign-out-alt"></i><a href="logout.php">Sign Out</a></li>

            </ul>
        </nav>
    </div>



  <div class="profile-container">
    <!-- Header Section -->
    <div class="header">
      <h1>Member Profile</h1>
    </div>

    <!-- Profile Content Section -->
    <div class="profile-content">
      <!-- Personal Info Section -->
      <div class="profile-info">
        <h2>Personal Information</h2>
        <div class="info-item">
          <span>Full Name:</span> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
        </div>
        <div class="info-item">
          <span>Role:</span> <?php echo htmlspecialchars($user_data['role_name']); ?>
        </div>
        <div class="info-item">
          <span>Gender:</span> <?php echo $user_data['gender'] == 'M' ? 'Male' : 'Female'; ?>
        </div>
        <div class="info-item">
          <span>Contact:</span> <?php echo htmlspecialchars($user_data['contact']); ?>
        </div>
        <div class="info-item">
          <span>Date of Birth:</span> <?php echo date("M d, Y", strtotime($user_data['dob'])); ?>
        </div>
        <div class="info-item">
          <span>Email:</span> <?php echo htmlspecialchars($user_data['email']); ?>
        </div>
      </div>

      <!-- Department Info Section -->
      <div class="department-info">
        <h2>Department Information</h2>
        <div class="info-item">
          <span>Department:</span> <?php echo htmlspecialchars($user_data['department_names']); ?>
        </div>
        <div class="info-item">
          <span>Position:</span> <?php echo htmlspecialchars($user_data['role_name']); ?>
        </div>
        <div class="info-item">
          <span>Joined Date:</span> <?php echo htmlspecialchars($user_data['created_at']); ?>
          </div><!-- Insert joined date logic here if needed -->
        </div>
      </div>

      <!-- Work Details Section (This can be customized) -->
      <div class="work-details">
        <h2>Work Details</h2>
        <p>Activities and Projects:</p>
        <ul>
          <li>Organized a community clean-up event on June 2023.</li>
          <li>Conducted health awareness sessions in collaboration with local health departments.</li>
          <li>Coordinated with schools to provide educational resources for underprivileged children.</li>
        </ul>
      </div>

      <!-- Edit Profile Button -->
      <a href="profiledit.php" class="btn-edit-profile">Edit Profile</a>
      <button onclick="goBack()" class="btn-edit-profile">Go Back</button>
    </div>
  </div>
</body>

<script>
  // Function to handle back button click
  function goBack() {
    // Check if the user is on the profile page
    if (document.referrer.indexOf("user.php") === -1) {
      window.location.href = "user.php";  // Redirect to user.php
    } else {
      history.back();  // Go back if coming from another page
    }
  }



</script>
</html>

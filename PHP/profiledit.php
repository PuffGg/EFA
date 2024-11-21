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

// Fetch user ID from session
session_start();
$user_id = $_SESSION['user_id'];  // Assuming user_id is stored in session

// Fetch user data and associated departments
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
      r.role_id,  -- Fetch the role_id
      GROUP_CONCAT(d.department_name ORDER BY d.department_name ASC) AS department_names,
      u.image  -- Include image column here
  FROM users u
  JOIN roles r ON u.role_id = r.role_id
  JOIN user_departments ud ON u.user_id = ud.user_id
  JOIN departments d ON ud.department_id = d.department_id
  WHERE u.user_id = ?
  GROUP BY u.user_id"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);  // Prevent SQL injection by binding parameter
$stmt->execute();
$result = $stmt->get_result();

// Check if data was fetched successfully
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();  // Fetch data as an associative array
} else {
    die("User not found");
}

// Fetch all available roles and departments for editing
$roles_sql = "SELECT role_id, role_name FROM roles";
$roles_result = $conn->query($roles_sql);

$departments_sql = "SELECT department_id, department_name FROM departments";
$departments_result = $conn->query($departments_sql);

// Update user profile if form is submitted
if (isset($_POST['update_profile'])) {
    // Collect form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $role_id = $_POST['role_id'];
    $selected_departments = $_POST['departments'];  // Array of selected departments

    // Handle image upload
    if ($_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $image_type = mime_content_type($_FILES['profile_image']['tmp_name']);
        if (strpos($image_type, 'image') === false) {
            $error_message = "Please upload a valid image.";
        } else {
            $image_data = file_get_contents($_FILES['profile_image']['tmp_name']);
        }
    } else {
        // Use old image if no new image is uploaded
        $image_data = $user_data['image'];
    }

    if (!isset($error_message)) {
        // Update query for user data
        $update_sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, contact = ?, dob = ?, gender = ?, role_id = ?, image = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssisi", $first_name, $last_name, $email, $contact, $dob, $gender, $role_id, $image_data, $user_id);
        $update_stmt->execute();

        // Redirect after successful update
        header("Location: profile.php");
        exit();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
/* Base styles */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: var(--background-color);
    color: var(--text-primary);
    line-height: 1.5;
    margin: 0;
    padding: 2rem;
}

.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background-color: var(--card-background);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

h1 {
    color: var(--text-primary);
    font-size: 1.875rem;
    font-weight: 600;
    margin-bottom: 2rem;
    text-align: center;
}

/* Form styles */
form {
    display: grid;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

label {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: 0.875rem;

}

input[type="text"],
input[type="email"],
input[type="date"],
select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
    color: var(--text-primary);
    background-color: var(--card-background);
    transition: border-color 0.2s, box-shadow 0.2s;
    box-shadow: 0 0 0 1px #ccc, 0 0 0 0.1rem rgba(255, 0, 0, 0.2); /* Light gray border-radius */
    border-radius: 0.5rem;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="date"]:focus,
select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    box-shadow: 0 0 0 1px #ccc, 0 0 0 0.1rem rgba(255, 0, 0, 0.2); /* Light gray border-radius */
    border-radius: 0.5rem;
}

/* Checkbox styles */
.info-item div {
    display: grid;
    gap: 0.75rem;
}

input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
    accent-color: var(--primary-color);
}

input[type="checkbox"] + label {
    display: inline-block;
    cursor: pointer;
}

/* File input styles */
input[type="file"] {
    padding: 0.5rem;
    border: 1px dashed var(--border-color);
    border-radius: 0.5rem;
    width: 100%;
}

/* Image preview */
img {
    border-radius: 50%;
    object-fit: cover;
    margin-top: 0.5rem;
    border: 2px solid var(--border-color);
    width: 100px;
    height: 100px;
    margin-bottom: 1rem;
}

/* Submit button */
button[type="submit"] {
    background-color: blue;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    width: 100%;
    margin-top: 1rem;
}

button[type="submit"]:hover {
    background-color: green;
}

button[type="submit"]:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
}

/* Responsive design */
@media (max-width: 640px) {
    body {
        padding: 1rem;
    }
    
    .profile-container {
        padding: 1.5rem;
    }
    
    h1 {
        font-size: 1.5rem;
    }
    
    input[type="text"],
    input[type="email"],
    input[type="date"],
    select {
        font-size: 0.875rem;
        padding: 0.625rem;
    }

    button[type="submit"] {
        padding: 0.625rem 1rem;
    }
}

/* Error message styling */
.error-message {
    color: #ef4444;
    background-color: #fee2e2;
    padding: 0.75rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

/* Profile Image Container */
.profile-image-container {
    text-align: center;
    margin-top: 1rem;
}

.profile-image-container img {
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
    width: 100px;
    height: 100px;
    margin-bottom: 1rem;
}

/* Section Styling */
.profile-info h2,
.department-info h2,
.work-details h2 {
    font-size: 1.375rem;
    color: var(--primary-color);
    margin-bottom: 12px;
    font-weight: 500;
}

.info-item span {
    font-weight: 500;
    font-size: 1rem;
    color: var(--text-primary);
}

</style>
</head>
<body>
    <div class="profile-container">
        <h1>Edit Profile</h1>
      

        <!-- Form to edit profile -->
        <form action="profiledit.php" method="POST" enctype="multipart/form-data">
            <!-- Personal Information -->
            <div class="info-item">
                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
            </div>
            <div class="info-item">
                <label for="last_name">Last Name:</label>
                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
            </div>
            <div class="info-item">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
            </div>
            <div class="info-item">
                <label for="contact">Contact:</label>
                <input type="text" name="contact" id="contact" value="<?php echo htmlspecialchars($user_data['contact']); ?>">
            </div>
            <div class="info-item">
                <label for="dob">Date of Birth:</label>
                <input type="date" name="dob" id="dob" value="<?php echo $user_data['dob']; ?>" required>
            </div>
            <div class="info-item">
                <label for="gender">Gender:</label>
                <select name="gender" id="gender">
                    <option value="M" <?php echo $user_data['gender'] == 'M' ? 'selected' : ''; ?>>Male</option>
                    <option value="F" <?php echo $user_data['gender'] == 'F' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <!-- Role Selection -->
            <div class="info-item">
                <label for="role_id">Role:</label>
                <select name="role_id" id="role_id">
                    <?php while ($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?php echo $role['role_id']; ?>" <?php echo $user_data['role_id'] == $role['role_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Profile Image Upload -->
            <div class="info-item">
                <label for="profile_image">Profile Image:</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($user_data['image']); ?>" alt="Current Image" width="100" height="100">
            </div>

               <!-- Submit -->
         <button type="submit" name="update_profile">Update Profile</button>

           
        </form>
    </div>
</body>
</html>

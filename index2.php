<?php
session_start();

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Database connection
    $conn = new mysqli("localhost", "root", "", "csd_system");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query with JOIN to fetch fullname and user_type from id_emp table
    $query = "SELECT *
              FROM id_emp e
              WHERE e.username = '$username'";
    
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the password
        if ($password === $row['password']) {
            // Store session data
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['desig_id'] = $row['desig_id'];
            $_SESSION['group_id'] = $row['group_id'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['middle_name'] = $row['middle_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['is_created'] = $row['is_created'];
            $_SESSION['user_type'] = $row['user_type'];

            // Determine redirection based on user_type
            if ($row['user_type'] === 'user') {
                header('Location: user_dashboard.php');
                exit;
            } elseif ($row['user_type'] === 'admin') {
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $_SESSION['error_message'] = "Invalid user type.";
            }
        } else {
            $_SESSION['error_message'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
    }

    $conn->close();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Clear error message after displaying it
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
} else {
    $error_message = '';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<style>
body {
  font-family: 'Roboto', sans-serif;
  margin: 0;
  padding: 0;
  /* background: url('./images/bg_canteen.png') no-repeat center center fixed; */
  /* background:#f0f2f5; */
  background:rgb(243,218,206);
  background-size: cover;
  display: flex;
  flex-direction: column;
  height:100%;
  width:100%;
}
main {
  flex: 1; /* Ensure main content takes up remaining space */
}
.container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 60%;
  margin: auto;
  margin-top:100px;
  height: 60%;
  background-color: rgba(25, 150, 150, 0.1); /* Add a semi-transparent background */
  box-shadow: 0 0 10px rgba(10, 10, 10, 0.5);
  padding: 20px;
  border-radius: 15px; /* Rounded corners for the container */
  box-shadow: 0 0 20px rgba(10, 10, 10, 0.2);
  flex: 1;
}

.left {
  flex: 1;
  height: 50%;
  width: 70%;
  display: flex;
  margin-top: -85px;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align:center;
}

.right {
  flex: 1;
  /* margin-top: 75px; */
}

h1 {
  text-align: center;
  margin-bottom: 20px;
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 40px;
  color: #333;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  padding: 12px;
  font-size: 16px;
  margin: 10px 0;
  border: 1px solid #ddd;
  border-radius: 5px; /* Rounded corners for the input fields */
  margin-bottom: 20px;
  box-sizing: border-box;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

button {
  background-color: #007BFF;
  color: white;
  padding: 12px 20px;
  margin: 20px auto; /* Center the button horizontally */
  border: none;
  border-radius: 5px; /* Rounded corners for the button */
  cursor: pointer;
  width: 100%;
  max-width: 200px; /* Set maximum width if needed */
  text-align: center; /* Center align text */
  font-size: 16px;
  display: block; /* Ensure block display for centering */
  transition: background-color 0.3s ease, transform 0.3s ease; /* Add transition for smooth effect */
}

button:hover {
  background-color: #0056b3;
  transform: scale(1.05); /* Slightly increase size on hover */
}

img {
  max-width: 80%; /* Increase image size */
  height: auto;
  transition: opacity 0.3s ease, transform 0.3s ease; /* Add transition for smooth effect */
}

img:hover {
  opacity: 0.8; /* Slightly decrease opacity on hover */
  transform: scale(1.05); /* Slightly increase size on hover */
}

.forgot {
  text-align: center;
  margin-top: 10px;
}

.forgot a {
  color: #007BFF;
  text-decoration: none;
}

.forgot a:hover {
  text-decoration: underline;
}

footer {
  background-color: #002147;
  color: white;
  text-align: center;
  padding: 10px 0;
  margin-top: auto;
}

.topic {
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

.error {
  color: red;
  text-align: center;
  margin-bottom: 10px;
}

.bold-label {
  font-weight: bold;
  font-size: 18px;
  color: #333;
}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="left">
    <div class="topic">
      <h2>Canteen Store Department</h2>
    </div>
    <div class="image-container">
  <img src="./images/loginlogo.png" alt="Login Image" style="width: 275px; height: auto;">
</div>
  </div>
  <div class="right">
    <h1>Login Credentials</h1>
    <?php if (!empty($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
    <form method="post">
      <label for="username" class="bold-label">Username:</label>
      <input type="text" id="username" name="username" placeholder="Type your username" required>
      <label for="password" class="bold-label">Password:</label>
      <input type="password" id="password" name="password" placeholder="Type your password" required>
      <button type="submit" name="login">Submit</button>
    </form>
    
  </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

session_start();

// Establish database connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$current_month = (int)date('m');
$start_date = '';

if ($current_month % 2 == 0) {
    // Even month: start date from the previous odd month
    $start_date = date('Y-m-01', strtotime('first day of -1 month'));
} else {
    // Odd month: start date from the beginning of this month
    $start_date = date('Y-m-01', strtotime('first day of this month'));
}

$total_items = 0;
$total_price = 0;
$user_id = $_SESSION['user_id'];

$query = "SELECT i.itemId, SUM(od.quantity) as total_quantity
          FROM orders o
          JOIN order_details od ON od.order_id = o.order_id
          JOIN items i ON i.itemId = od.item_id
          WHERE o.date_and_time BETWEEN ? AND NOW() AND o.user_id = ? AND o.status = 2 GROUP BY i.itemId;";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("si", $start_date, $user_id);

// Execute statement
$stmt->execute();

// Get result
$result = $stmt->get_result();
if ($result === false) {
    die("Get result failed: " . $stmt->error);
}

$arr = array();
while ($row = $result->fetch_assoc()) {
    $arr[$row['itemId']] = $row['total_quantity'];
}

// Output result as JSON
echo json_encode($arr);

// Close connection
$stmt->close();
$conn->close();
?>
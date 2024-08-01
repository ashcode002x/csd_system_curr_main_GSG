<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

session_start();

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Sorry, Connection with database is not built " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'];
    $order_id = $_POST['order_id'];
    $quantity = $_POST['quantity'];

    // Debugging: Check received values
    error_log("Received - item_id: $item_id, order_id: $order_id, quantity: $quantity");

    $stmt = $conn->prepare("UPDATE order_details SET quantity = ? WHERE item_id = ? AND order_id = ?");
    if (!$stmt) {
        // Debugging: Prepare statement failed
        error_log("Prepare failed: " . $conn->error);
        echo 'error';
        exit;
    }

    $stmt->bind_param("dii", $quantity, $item_id, $order_id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        // Debugging: Execute failed
        error_log("Execute failed: " . $stmt->error);
        echo 'error';
    }

    $stmt->close();
}

$conn->close();
?>

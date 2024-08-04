<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "increase") {
    if (isset($_REQUEST["itemId"])) {
        $maxvalue = $_REQUEST["maxValue"];
        $itemId = $_REQUEST["itemId"];
        $response = array(
            "status" => 200,
            "id" => $itemId,
            "price" => $_SESSION["order_list"][$key]["price"],
            "quantity" => $_SESSION["order_list"][$key]["selected_quantity"]
        );
        // Check if the order list exists in the session
        if (isset($_SESSION["order_list"]) && is_array($_SESSION["order_list"])) {
            foreach ($_SESSION["order_list"] as $key => $value) {
                if (isset($value["itemId"]) && $value["itemId"] == $itemId) {
                    if ($_SESSION["order_list"][$key]["selected_quantity"] >= $maxvalue) {
                        print_r("this is maxvalue " . $maxvalue);
                        print_r("this is selected quantity " . $_SESSION["order_list"][$key]["selected_quantity"]);
                        // print_r($maxval . " " . $_SESSION["order_list"][$key]["selected_quantity"]);
                        echo json_encode(array("status" => 400, "message" => "Maximum quantity reached"));
                        die;
                    }
                    $_SESSION["order_list"][$key]["selected_quantity"] += 1;
                    $response["price"] = intval($_SESSION["order_list"][$key]["price"]);
                    $response["quantity"] = $_SESSION["order_list"][$key]["selected_quantity"];
                    // print_r($_SESSION["order_list"]);
                    echo json_encode($response);
                    die;
                }
            }
        }
    }
}
if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "decrease") {
    if (isset($_REQUEST["itemId"])) {
        $itemId = $_REQUEST["itemId"];
        $response = array(
            "status" => 200,
            "id" => $itemId,
            "price" => $_SESSION["order_list"][$key]["price"],
            "quantity" => $_SESSION["order_list"][$key]["selected_quantity"]
        );
        // Check if the order list exists in the session
        if (isset($_SESSION["order_list"]) && is_array($_SESSION["order_list"])) {
            foreach ($_SESSION["order_list"] as $key => $value) {
                if (isset($value["itemId"]) && $value["itemId"] == $itemId) {
                    if ($_SESSION["order_list"][$key]["selected_quantity"] <= 1) {
                        die;
                    }
                    $_SESSION["order_list"][$key]["selected_quantity"] -= 1;
                    $response["price"] = intval($_SESSION["order_list"][$key]["price"]);
                    $response["quantity"] = $_SESSION["order_list"][$key]["selected_quantity"];
                    // print_r($_SESSION["order_list"]);
                    echo json_encode($response);
                    die;
                }
            }
        }
    }
}

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


if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "stock") {
    $itemId = $_REQUEST["itemId"];
    $query = "SELECT itemId, stock_quantity 
              FROM items WHERE itemId = ?";
    // print_r($_REQUEST);
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        // print_r($conn->error);
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("i", $itemId);

    // Execute statement
    $stmt->execute();

    // Get result
    $result = $stmt->get_result();
    if ($result === false) {
        // print_r($stmt->error);
        die("Get result failed: " . $stmt->error);
    }

    $row = $result->fetch_assoc();
    // $row = $row['stock_quantity'];
    // print_r($row);
    echo json_encode($row["stock_quantity"], JSON_NUMERIC_CHECK);
    die;
};

$total_items = 0;
$total_price = 0;
$user_id = $_SESSION['user_id'];

$query = "SELECT i.itemId, SUM(od.quantity)
          FROM orders o
          JOIN order_details od ON od.order_id = o.order_id
          JOIN items i ON i.itemId = od.item_id
          WHERE o.date_and_time BETWEEN ? AND NOW() AND o.user_id = ? AND o.status = 2 GROUP BY i.itemId;";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    // print_r($conn->error);
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("si", $start_date, $user_id);

// print_r("Prepared");
// Execute statement
$stmt->execute();

// print_r("Executed");
// Get result
$result = $stmt->get_result();
if ($result === false) {
    // print_r($stmt->error);
    die("Get result failed: " . $stmt->error);
}
// print_r("Got result");
// print_r($result->fetch_all());

$arr = array();
// print_r($result->fetch_assoc());
// print_r($_REQUEST);
while ($row = $result->fetch_assoc()) {
    $arr[$row['itemId']] = min($row['total_quantity'], $row['stock_quantity']);
    // print_r($arr);
}

// Output result as JSON
echo json_encode($arr);

// Close connection
$stmt->close();
$conn->close();

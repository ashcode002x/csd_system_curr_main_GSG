<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json'); // Ensure the response is JSON

function jsonResponse($data)
{
    echo json_encode($data, JSON_NUMERIC_CHECK);
    exit;
}


// Establish database connection
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    jsonResponse(["status" => 500, "message" => "Database connection failed: " . $conn->connect_error]);
}


// find piller of stock market
if (isset($_REQUEST["method"]) && $_REQUEST["method"] == "fetchAll") {
    $itemId = $_REQUEST['itemid'];
    $user_id = $_SESSION['user_id'];
    $current_month = (int)date('m');

    $start_date = $current_month % 2 == 0 ? date('Y-m-01', strtotime('first day of -1 month')) : date('Y-m-01');

    $query = "SELECT limit1, stock_quantity FROM items WHERE itemId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetch = $result->fetch_assoc();
    if ($result === false) {
        die("Error fetching total quantity: " . mysqli_error($conn));
    }
    $result_limit1 = $fetch['limit1'];
    $result_stock_quantity = $fetch['stock_quantity'];
    jsonResponse(array("limit1" => $result_limit1, "stock" => $result_stock_quantity));
    die;
}

// handle 3 sum
if (isset($_REQUEST["method"]) && $_REQUEST["method"] == "fetchlimit1") {
    $itemId = $_REQUEST['itemid'];
    $user_id = $_SESSION['user_id'];
    $current_month = (int)date('m');

    $start_date = $current_month % 2 == 0 ? date('Y-m-01', strtotime('first day of -1 month')) : date('Y-m-01');

    $query = "SELECT limit1 FROM items WHERE itemId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetch = $result->fetch_assoc();
    if ($result === false) {
        die("Error fetching total quantity: " . mysqli_error($conn));
    }
    $result_limit1 = $fetch['limit1'];
    print_r($result_limit1);
    die;
    jsonResponse($result_limit1);
    die;
}

// Handling the "increase" operation
if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "increase") {
    if (isset($_REQUEST["itemId"])) {
        $itemId = $_REQUEST["itemId"];
        $maxValue = $_SESSION['maxvalues'][$itemId];
        // Check if the order list exists in the session
        if (isset($_SESSION["order_list"]) && is_array($_SESSION["order_list"])) {
            foreach ($_SESSION["order_list"] as $key => $value) {
                if ($value["itemId"] == $itemId) {
                    if ($_SESSION["order_list"][$key]["selected_quantity"] >= $maxValue) {
                        jsonResponse(["status" => 400, "message" => "Maximum quantity reached"]);
                    }

                    $_SESSION["order_list"][$key]["selected_quantity"] += 1;
                    $response = [
                        "status" => 200,
                        "id" => $itemId,
                        "price" => intval($_SESSION["order_list"][$key]["price"]),
                        "quantity" => $_SESSION["order_list"][$key]["selected_quantity"]
                    ];
                    jsonResponse($response);
                }
            }
        }
        jsonResponse(["status" => 404, "message" => "Item not found in order list"]);
    }
}

if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "fetchmin") {
    $itemId = $_REQUEST['itemId'];
    echo jsonResponse($_SESSION['maxvalues'][$itemId]);
}

// Handle the "decrease" operation
if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "decrease") {
    if (isset($_REQUEST["itemId"])) {
        $itemId = $_REQUEST["itemId"];

        // Check if the order list exists in the session
        if (isset($_SESSION["order_list"]) && is_array($_SESSION["order_list"])) {
            foreach ($_SESSION["order_list"] as $key => $value) {
                if ($value["itemId"] == $itemId) {
                    if ($_SESSION["order_list"][$key]["selected_quantity"] <= 1) {
                        jsonResponse(["status" => 400, "message" => "Cannot decrease below 1"]);
                    }

                    $_SESSION["order_list"][$key]["selected_quantity"] -= 1;
                    $response = [
                        "status" => 200,
                        "id" => $itemId,
                        "price" => intval($_SESSION["order_list"][$key]["price"]),
                        "quantity" => $_SESSION["order_list"][$key]["selected_quantity"]
                    ];
                    jsonResponse($response);
                }
            }
        }
        jsonResponse(["status" => 404, "message" => "Item not found in order list"]);
    }
}






// Determine the start date based on the current month
$current_month = (int)date('m');
$start_date = $current_month % 2 == 0 ? date('Y-m-01', strtotime('first day of -1 month')) : date('Y-m-01');


// handling the "stock" operation.
if (isset($_REQUEST["operation"]) && $_REQUEST["operation"] == "stock") {
    if (isset($_REQUEST["itemId"])) {
        $itemId = $_REQUEST["itemId"];

        $query = "SELECT itemId, stock_quantity FROM items WHERE itemId = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            jsonResponse(["status" => 500, "message" => "Prepare failed: " . $conn->error]);
        }

        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            jsonResponse(["status" => 500, "message" => "Get result failed: " . $stmt->error]);
        }

        $row = $result->fetch_assoc();
        if ($row) {
            jsonResponse(["status" => 200, "stock_quantity" => $row["stock_quantity"]]);
        } else {
            jsonResponse(["status" => 404, "message" => "Item not found"]);
        }
    }
}

// Fetch the user's order data based on the date range and status
$total_items = 0;
$total_price = 0;
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $query = "SELECT i.itemId, SUM(od.quantity) AS total_quantity, i.stock_quantity, i.limit1
              FROM orders o
              JOIN order_details od ON od.order_id = o.order_id
              JOIN items i ON i.itemId = od.item_id
              WHERE o.date_and_time BETWEEN ? AND NOW() AND o.user_id = ? AND o.status = 2 
              GROUP BY i.itemId, i.stock_quantity";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        jsonResponse(["status" => 500, "message" => "Prepare failed: " . $conn->error]);
    }

    $stmt->bind_param("si", $start_date, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        jsonResponse(["status" => 500, "message" => "Get result failed: " . $stmt->error]);
    }

    $arr = [];
    while ($row = $result->fetch_assoc()) {
        // print_r($row);
        $arr[$row['itemId']] = ["total_qty" => min($row['total_quantity'], $row['stock_quantity']), "limit1" => $row['limit1']];
    }
    // print_r($arr);
    // die;

    echo jsonResponse($arr);
}

$stmt->close();
$conn->close();

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

$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session

// Handle form submission to update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $order_id = $_POST['order_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("UPDATE order_details SET quantity = ? WHERE item_id = ? AND order_id = ?");
    $stmt->bind_param("dii", $quantity, $item_id, $order_id);

    if ($stmt->execute()) {
        $success_message = 'Quantity updated successfully';
    } else {
        $error_message = 'Failed to update quantity';
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete_item'])) {
        $item_id = $_GET['delete_item'];

        $delete_stmt = $conn->prepare("DELETE FROM order_details WHERE item_id = ?");
        $delete_stmt->bind_param("i", $item_id);

        if ($delete_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to delete item';
        }

        $delete_stmt->close();
    }

    if (isset($_GET['approve_order'])) {
        $order_id = $_GET['approve_order'];

        $approve_stmt = $conn->prepare("UPDATE orders SET status = 2, date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $approve1_stmt = $conn->prepare("UPDATE order_details SET date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $approve_stmt->bind_param("i", $order_id);
        $approve1_stmt->bind_param("i", $order_id);

        $approve1_stmt->execute();

        if ($approve_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to approve order';
        }

        $approve_stmt->close();
    }

    if (isset($_GET['reject_order'])) {
        $order_id = $_GET['reject_order'];

        $reject_stmt = $conn->prepare("UPDATE orders SET status = 0, date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $reject1_stmt = $conn->prepare("UPDATE order_details SET date_and_time = CURRENT_TIMESTAMP WHERE order_id = ?");
        $reject_stmt->bind_param("i", $order_id);
        $reject1_stmt->bind_param("i", $order_id);

        $reject1_stmt->execute();

        if ($reject_stmt->execute()) {
            header('Location: admin_orders.php');
            exit();
        } else {
            echo 'Failed to reject order';
        }

        $reject_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="all.min.css">
    <style>
        body {
            background-color: #e6f7ff; /* Light blue background color */
            font-family: Arial, sans-serif;
        }

        .section-title {
            margin-top: 20px;
            color: #2c3e50; /* Darker shade for heading */
            font-weight: bold;
        }

        .table-container {
            margin-top: 20px;
            background-color: #ffffff; /* White background for table */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .no-orders {
            text-align: center;
            font-size: 1.2rem;
            color: #95a5a6;
            margin-top: 20px;
        }

        .total-price {
            font-weight: bold;
        }

        h4 {
            color: #3498db; /* Bright blue color for Order ID heading */
            margin-bottom: 10px;
        }

        .table thead th {
            background-color: #ecf0f1; /* Light grey background for table header */
            color: #2c3e50; /* Dark text color for table header */
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9; /* Very light grey for zebra striping */
        }

        .table tbody tr:hover {
            background-color: #e0f7fa; /* Light cyan hover effect */
        }

        .btn-primary {
            background-color: #3498db; /* Bright blue for primary button */
            border-color: #3498db;
        }

        .btn-primary:hover {
            background-color: #2980b9; /* Darker blue for hover effect */
            border-color: #2980b9;
        }

        .btn-action {
            margin: 0 5px;
        }

        .btn-back {
            position: absolute;
            top: 10px;
            right: 10px;
            margin-top: 110px;
            margin-right : 180px;
        }

        td.d-flex {
            border: none; /* Remove any border around the td */
            outline: none; /* Remove any outline */
            box-shadow: none; /* Remove any shadow */
            gap:4px;
            padding: 0; /* Remove any default padding */
            margin: 0; /* Remove any default margin */
        }

        /* Remove any border, outline, or shadow from buttons */
        td.d-flex button {
            border: none; /* Remove border from buttons */
            outline: none; /* Remove outline from buttons */
            box-shadow: none; /* Remove any shadow from buttons */
            margin: 0; /* Ensure no extra margin around buttons */
        }
    </style>
</head>
<body>

    <!-- Back Button -->
    <a href="admin_dashboard.php" class="btn btn-secondary btn-back font-weight-bold temp">&lt; Back</a>

    <!-- navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <!-- Current Orders Section -->
        <h2 class="section-title">Current Orders</h2>
        <div class="table-container">
            <?php
            if (isset($success_message)) {
                echo "<div class='alert alert-success'>$success_message</div>";
            }
            if (isset($error_message)) {
                echo "<div class='alert alert-danger'>$error_message</div>";
            }

            $query = "SELECT * FROM orders WHERE status = 1";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 0) {
                echo "<div class='no-orders'>No current orders.</div>";
            } else {
                while ($order = mysqli_fetch_assoc($result)) {
                    $order_id = $order['order_id'];
                    echo "<h4>Order ID: $order_id</h4>";
                    echo "<table class='table table-bordered' data-order-id='$order_id'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Sno.</th>";
                    echo "<th>Item ID</th>";
                    echo "<th>Item Name</th>";
                    echo "<th>Category</th>";
                    echo "<th>Description</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Price</th>";
                    echo "<th>Unit</th>";
                    echo "<th>Remarks</th>";
                    echo "<th>Date and Time</th>"; 
                    echo "<th>Actions</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    $item_query = "SELECT od.*, i.category, i.description, i.Unit as unit, i.Remarks as remarks, i.stock_quantity, od.date_and_time 
                                FROM order_details od 
                                JOIN items i ON od.item_id = i.itemId 
                                WHERE od.order_id = $order_id";
                    $item_result = mysqli_query($conn, $item_query);
                    $serial_number = 1;
                    $total_price = 0;

                    while ($item = mysqli_fetch_assoc($item_result)) {
                        $item_id = $item['item_id'];
                        $item_name = $item['item_name'];
                        $category = $item['category'];
                        $description = $item['description'];
                        $quantity = $item['quantity'];
                        $unit = $item['unit'];
                        $price = $item['price'];
                        $remarks = $item['remarks'];
                        $date_and_time = $item['date_and_time'];
                        $stock_quantity = $item['stock_quantity'];
                        $total_price += $price * $quantity;

                        echo "<tr id='item-$item_id'>";
                        echo "<td>$serial_number</td>";
                        echo "<td>$item_id</td>";
                        echo "<td>$item_name</td>";
                        echo "<td>$category</td>";
                        echo "<td>$description</td>";
                        echo "<td class='item-quantity'>$quantity</td>";
                        echo "<td class='item-price'>" . number_format($price, 2) . "</td>";
                        echo "<td>$unit</td>";
                        echo "<td>$remarks</td>";
                        echo "<td>$date_and_time</td>";
                        echo "<td class='d-flex mt-2 gap-2'>
                                <button class='btn btn-sm btn-primary btn-update' 
                                        data-item-id='$item_id' 
                                        data-quantity='$quantity' 
                                        data-stock-quantity='$stock_quantity'>Update</button>
                                <button class='btn btn-sm btn-danger btn-delete' 
                                        data-item-id='$item_id'>Delete</button>
                            </td>";
                        echo "</tr>";

                        $serial_number++;
                    }

                    echo "<tr id='total-price-row'>";
                    echo "<td colspan='10' class='text-right total-price'>Total Price</td>";
                    echo "<td class='total-price' colspan='6' id='total-price'>" . number_format($total_price, 2) . "</td>";
                    echo "</tr>";

                    echo "</tbody>";
                    echo "</table>";
                    echo "<div class='text-right'>
                            <button class='btn btn-sm btn-success btn-action btn-approve' data-order-id='$order_id'>Approve Order</button>
                            <button class='btn btn-sm btn-danger btn-action btn-reject' data-order-id='$order_id'>Reject Order</button>
                        </div>";
                }
            }
            ?>
        </div>

       <!-- Past Orders Section -->
       <h2 class="section-title">Past Orders</h2>
        <div class="table-container">
            <?php
            $query = "SELECT * FROM orders WHERE status IN (2, 0) ORDER BY status DESC, date_and_time DESC";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) == 0) {
                echo "<div class='no-orders'>No past orders.</div>";
            } else {
                while ($order = mysqli_fetch_assoc($result)) {
                    $order_id = $order['order_id'];
                    $status = $order['status'];
                    $status_text = $status == 2 ? 'Approved' : 'Rejected'; // Convert status to text for display
                    $status_color = $status == 2 ? 'green' : 'red'; // Define color based on status

                    echo "<h4>Order ID: $order_id</h4>";
                    echo "<table class='table table-bordered' data-order-id='$order_id'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Sno.</th>";
                    echo "<th>Item ID</th>";
                    echo "<th>Item Name</th>";
                    echo "<th>Category</th>";
                    echo "<th>Description</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Price</th>";
                    echo "<th>Unit</th>";
                    echo "<th>Remarks</th>";
                    echo "<th>Date and Time</th>"; 
                    echo "<th>Status</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    $item_query = "SELECT od.*, i.category, i.description, i.Unit as unit, i.Remarks as remarks, od.date_and_time 
                                FROM order_details od 
                                JOIN items i ON od.item_id = i.itemId 
                                WHERE od.order_id = $order_id";
                    $item_result = mysqli_query($conn, $item_query);
                    $serial_number = 1;
                    $total_price = 0;

                    while ($item = mysqli_fetch_assoc($item_result)) {
                        $item_id = $item['item_id'];
                        $item_name = $item['item_name'];
                        $category = $item['category'];
                        $description = $item['description'];
                        $quantity = $item['quantity'];
                        $unit = $item['unit'];
                        $price = $item['price'];
                        $remarks = $item['remarks'];
                        $date_and_time = $item['date_and_time'];
                        $total_price += $price * $quantity;

                        echo "<tr>";
                        echo "<td>$serial_number</td>";
                        echo "<td>$item_id</td>";
                        echo "<td>$item_name</td>";
                        echo "<td>$category</td>";
                        echo "<td>$description</td>";
                        echo "<td>$quantity</td>";
                        echo "<td>" . number_format($price, 2) . "</td>";
                        echo "<td>$unit</td>";
                        echo "<td>$remarks</td>";
                        echo "<td>$date_and_time</td>";
                        echo "<td style='color: $status_color;'>$status_text</td>"; // Status color
                        echo "</tr>";

                        $serial_number++;
                    }

                    echo "<tr>";
                    echo "<td colspan='10' class='total-price text-right'>Total Price:</td>";
                    echo "<td colspan='8' class='total-price'>" . number_format($total_price, 2) . "</td>";
                    echo "</tr>";

                    echo "</tbody>";
                    echo "</table>";
                }
            }
            ?>
        </div>
    </div>

    <!-- Update Quantity Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Quantity</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" step="0.01" required>
                        </div>
                        <input type="hidden" id="updateItemId" name="item_id">
                        <input type="hidden" id="updateOrderId" name="order_id">
                        <input type="hidden" id="updateStockQuantity" name="stock_quantity">
                        <button type="submit" name="update_quantity" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="jquery-3.3.1.slim.min.js"></script>
    <script src="popper.min.js"></script>
    <script src="bootstrap.min.js"></script>

    <script>
    $(document).ready(function() {
        // Handle update button click
        $('.btn-update').on('click', function() {
            var itemId = $(this).data('item-id');
            var quantity = $(this).data('quantity');
            var orderId = $(this).closest('table').data('order-id'); // Assuming the order ID is stored in the table's data attribute
            var stockQuantity = $(this).data('stock-quantity');
            $('#updateItemId').val(itemId);
            $('#updateOrderId').val(orderId);
            $('#updateStockQuantity').val(stockQuantity);
            $('#quantity').val(quantity);
            $('#updateModal').modal('show');
        });

        // Handle delete button click
        $('.btn-delete').on('click', function() {
            var itemId = $(this).data('item-id');
            if (confirm("Are you sure you want to delete this item?")) {
                window.location.href = `admin_orders.php?delete_item=${itemId}`;
            }
        });

        // Handle approve button click
        $('.btn-approve').on('click', function() {
            var orderId = $(this).data('order-id');
            if (confirm("Are you sure you want to approve this order?")) {
                window.location.href = `admin_orders.php?approve_order=${orderId}`;
            }
        });

        // Handle reject button click
        $('.btn-reject').on('click', function() {
            var orderId = $(this).data('order-id');
            if (confirm("Are you sure you want to reject this order?")) {
                window.location.href = `admin_orders.php?reject_order=${orderId}`;
            }
        });
    });
    </script>
    
</body>
</html>

<?php
mysqli_close($conn);
?>

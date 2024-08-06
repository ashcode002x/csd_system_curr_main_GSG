<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";
global $limit1;
session_start();

// Establish database connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Pagination variables
$results_per_page = 10; // Number of items per page

// Determine current page number
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}



// Calculate SQL LIMIT starting row number for the pagination formula
$start_limit = ($page - 1) * $results_per_page;

// Search functionality
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}


// Filter functionality
$category_filter = "";
if (isset($_GET['category_filter'])) {
    $category_filter = $_GET['category_filter'];
}

// Handle adding items to order list
if (isset($_POST['Add_To_Order'])) {
    $itemId = $_POST['itemId'];
    $user_id = $_SESSION['user_id'];
    $current_month = (int)date('m');
    $start_date = $current_month % 2 == 0 ? date('Y-m-01', strtotime('first day of -1 month')) : date('Y-m-01');

    $query = "SELECT 
    SUM(order_details.quantity) AS total_quantity, 
    items.limitt AS limitt, 
    items.stock_quantity AS stock,  
    items.limit1 AS limit1
    FROM 
        items
    LEFT JOIN 
        order_details ON items.itemId = order_details.item_id
    LEFT JOIN 
        orders ON order_details.order_id = orders.order_id
    WHERE 
        items.itemId = ? 
        AND (orders.user_id = ? OR orders.user_id IS NULL)
        AND (orders.status IS NULL OR orders.status != 0 or orders.status != 1)
        AND (order_details.date_and_time BETWEEN ? AND NOW() OR order_details.date_and_time IS NULL);
";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $itemId, $user_id, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $fetch = $result->fetch_assoc();
    if ($result === false) {
        die("Error fetching total quantity: " . mysqli_error($conn));
    }
    if ($fetch['total_quantity'] == null) {
        $total_quantity_purchased_2months = 0;
    } else {
        $total_quantity_purchased_2months = $fetch['total_quantity'];
    }

    $limitt = $fetch['limitt'];
    $limit1 = $fetch['limit1'];
    // print_r($fetch);

    $limit = $limitt - $total_quantity_purchased_2months;

    $stock = $fetch['stock'];
    $maxVal = min($limit, $stock, $limit1);
    if (!isset($_SESSION['maxvalues']) || !is_array($_SESSION['maxvalues'])) {
        $_SESSION['maxvalues'] = [];
    }

    // Store the max value for the specific itemId
    $_SESSION['maxvalues'][$itemId] = $maxVal;
    print_r($_SESSION);


    $item = [
        'itemId' => $_POST['itemId'],
        'name' => $_POST['name'],
        'category' => $_POST['category'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock_quantity' => $_POST['stock_quantity'],
        'remarks' => $_POST['remarks'],
        'unit' => $_POST['unit'],
        'selected_quantity' => $_POST['selected_quantity']
    ];

    if (!isset($_SESSION['order_list'])) {
        $_SESSION['order_list'] = [];
    }

    $found = false;
    foreach ($_SESSION['order_list'] as &$existingItem) {
        if ($existingItem['itemId'] == $item['itemId']) {
            // Update the quantity
            $existingItem['selected_quantity'] += $item['selected_quantity'];
            if ($existingItem['selected_quantity'] > $maxVal) {
                $existingItem['selected_quantity'] = $maxVal;
            }
            $found = true;
            break;
        }
    }
    if (!$found) {
        // Item not found, add it to the order list
        $_SESSION['order_list'][] = $item;
    }
}

// Handle removing items from order list
if (isset($_POST['Remove_From_Order'])) {
    $index = $_POST['index'];
    if (isset($_SESSION['order_list'][$index])) {
        unset($_SESSION['order_list'][$index]);
        $_SESSION['order_list'] = array_values($_SESSION['order_list']); // Re-index the array
    }
}

// Handle adding the order list to the cart
if (isset($_POST['Add_To_Cart'])) {


    // print_r($_REQUEST);
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $sessionId = $_REQUEST['session'];
    $sessionData = json_decode($sessionId, true);


    foreach ($_SESSION['order_list'] as $item) {
        $found = false; // Initialize found flag



        foreach ($_SESSION['cart'] as &$cart_item) { // Use reference to update quantity directly
            if ($cart_item['itemId'] === $item['itemId']) {
                // Update the quantity if the item is already in the cart
                $limit = intval($sessionData[$item['itemId']]);
                $cart_item['selected_quantity'] += $item['selected_quantity'];
                // if ($cart_item['selected_quantity'] > $cart_item['stock_quantity']) {
                if ($cart_item['selected_quantity'] > $limit) {
                    $cart_item['selected_quantity'] = $limit; // Ensure quantity doesn't exceed stock
                }
                $found = true; // Set found flag to true
                break;
            }
        }

        if (!$found) {
            // Item not found in the cart, add it as a new item
            $_SESSION['cart'][] = $item;
        }
    }

    // Clear the order list after adding to cart
    $_SESSION['order_list'] = [];

    // Redirect to cart page or display a success message
    header("Location: cartpage.php");
    exit();
}

print_r($_SESSION);

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="bootstrap.min1.css">
    <link rel="stylesheet" href="all.min.css">
    <link rel="stylesheet" href="dataTables.dataTables.min.css">
    <title>User Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f0f4f8;
            transition: background 0.5s ease-in-out;
        }

        .container {
            margin-top: 20px;
            /* display: flex;  showimg orderlist on right side by gsg */

        }

        /*  Reducing the no of item in single row
        .main-content {
            flex: ;
            margin-right: 20px;
        } */

        /* .order-list {
            flex: 1;
            background-color: #ffffff;
            padding: 10px;
            margin-top:95px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-height: 400px; /* Fixed height for scrolling */
        /* overflow-y: auto; /* Enable vertical scrolling */



        /* .order-list {
                flex: 1;
                background-color: #ffffff;
                padding: 10px;
                margin-top: 95px;
                border-radius: 5px; 
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                max-height: 400px;
                overflow-y: auto;
                position: fixed;
                top: 0;
                right: -100%;
                height: 100%;
                transition: right 0.5s ease-in-out;
            }

        .order-list.visible {
            right: 0;
        } */
        /* .order-list {
    flex: 1;
    background-color: #ffffff;
    padding: 10px;
    margin-top: 95px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-height: 400px;
    overflow-y: auto;
    position: fixed;
    top: 0;
    right: 0; /* Change this to ensure it's visible on load */
        /* height: 100%;
    transition: right 0.5s ease-in-out;
}

.order-list.visible {
    right: 0;
} */

        .order-list {
            flex: 1;
            background-color: #ffffff;
            padding: 10px;
            margin-top: 95px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
            position: fixed;
            top: 0;
            right: -100%;
            height: 100%;
            transition: right 0.1s ease-in-out;
        }

        .order-list.visible {
            right: 0;
        }




        .header-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
        }

        .header-actions h2 {
            margin: 0;
            font-weight: bold;
            color: #333;
            transition: color 0.5s ease-in-out;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px 20px;
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card img {
            width: 100px;
            height: 100px;
            /* Reduced height of the image */
            object-fit: cover;
            margin-top: 20px;
            margin: auto;
            padding-top: 4px;
        }

        .card-body {
            padding: 15px;
            flex: 1;
        }

        .card-title {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: #333;
            background-color: #e3f2fd;
            padding: 5px;
            border-radius: 3px;
        }

        .card-text {
            font-size: 0.76em;
            color: #666;
            background-color: #fafafa;
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #e1f5fe;
            border-top: 1px solid #ddd;
        }

        .card-footer .btn {
            transition: background-color 0.3s ease-in-out, transform 0.3s ease-in-out;
            padding: 0.375rem 0.75rem;
            /* Reduced padding for the button */
            font-size: 0.8em;
            margin-left: 30px;
            /* Reduced font size for the button */
        }

        .card-footer .btn:hover {
            transform: scale(1.05);
        }

        .select-quantity {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .select-quantity input {
            width: 60px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        #add-btn {
            background-color: #ffcc80;
            border-color: #ffcc80;
        }

        #add-btn:hover {
            background-color: #ffb74d;
        }

        #print-btn {
            background-color: #9575cd;
            border-color: #9575cd;
        }

        #print-btn:hover {
            background-color: #7e57c2;
        }

        #logout-btn {
            background-color: #ef5350;
            border-color: #ef5350;
        }

        #logout-btn:hover {
            background-color: #e53935;
        }

        .btn-orders {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
            margin-right: 3px;
            transition: background-color 0.3s, border-color 0.3s, transform 0.3s;
        }

        .btn-orders:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .order-list h4 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .order-list-item {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-list-item span {
            flex: 1;
        }

        .order-list-item button {
            margin-left: 10px;
            background-color: #ef5350;
            border: none;
            color: white;
            padding: 5px 10px;
            cursor: pointer;
        }


        .temp1 {
            margin-left: 645px;
        }
    </style>
</head>


<body onload="toggleOrderList()">

    <!-- navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="text-center my-4">
                <h2 class="font-weight-bold">User Dashboard</h2>
            </div>
            <div class="header-actions">
                <h2>Available Items</h2>
                <!-- new lines  -->
                <div>
                    <!-- Your existing buttons -->
                    <button id="toggle-order-list-btn" class="btn btn-primary">Toggle Order List</button>
                </div>
                <!-- new ended -->
                <div>
                    <?php
                    $count = 0;
                    if (isset($_SESSION['cart'])) {
                        $count = count($_SESSION['cart']);
                    }
                    ?>
                    <button id="orders-btn" class="btn btn-orders" onclick="window.location.href='my_orders.php';">
                        <i class="fa-solid fa-box"></i> My Orders
                    </button>
                    <button id="add-btn" class="btn btn-primary" onclick="window.location.href='cartpage.php';"><i class="fa-solid fa-cart-plus"></i> My Cart </button>
                    <button id="print-btn" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
                    <button id="logout-btn" class="btn btn-danger" onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>

            <form id="filter-form" class="form-inline mb-3" method="GET">
                <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>

                <select id="category-filter" class="form-control temp1" name="category_filter">
                    <option value="">Select Category</option>
                    <!-- <option value="">All Categories</option> -->
                    <option value="C1" <?php if ($category_filter == "C1") echo 'selected'; ?>>C1</option>
                    <option value="C2" <?php if ($category_filter == "C2") echo 'selected'; ?>>C2</option>
                    <option value="C3" <?php if ($category_filter == "C3") echo 'selected'; ?>>C3</option>
                    <option value="C4" <?php if ($category_filter == "C4") echo 'selected'; ?>>C4</option>
                    <option value="C5" <?php if ($category_filter == "C5") echo 'selected'; ?>>C5</option>
                    <option value="C6" <?php if ($category_filter == "C6") echo 'selected'; ?>>C6</option>
                </select>

            </form>



            <div class="card-grid">
                <?php
                // Fetch items with pagination and search
                $sql = "SELECT * FROM items WHERE name LIKE '%$search%' OR itemId LIKE '%$search%' OR category LIKE '%$search%' OR description LIKE '%$search%' OR price LIKE '%$search%' OR stock_quantity LIKE '%$search%' OR Unit LIKE '%$search%' OR Remarks LIKE '%$search%'";

                if (!empty($_REQUEST['category_filter']) && $_REQUEST['category_filter'] != "All Categories") {
                    $category_filter = $_REQUEST['category_filter'];
                    $sql = "SELECT * FROM items WHERE category = '$category_filter'";
                }


                $sql .= " LIMIT $start_limit, $results_per_page";



                $result = mysqli_query($conn, $sql);

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                ?>
                        <div class="card">
                            <img src="<?php echo 'items_image/' . $row['item_image']; ?>" alt="<?php echo $row['name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                <div class="card-text">
                                    <span><strong>ID:</strong> <?php echo $row['itemId']; ?></span>
                                    <span style="flex-grow: 1;"></span> <!-- Spacer -->
                                    <span><strong>Category:</strong> <?php echo $row['category']; ?></span>
                                </div>
                                <div class="card-text">
                                    <span><strong>Description:</strong> <?php echo $row['description']; ?></span>
                                </div>
                                <div class="card-text">
                                    <span><strong>Price:</strong> Rs <?php echo number_format($row['price'], 2); ?></span>
                                    <span style="flex-grow: 1;"></span> <!-- Spacer -->
                                    <span><strong>Stock:</strong> <?php echo $row['stock_quantity']; ?></span>
                                </div>
                                <div class="card-text">
                                    <span><strong>Remark:</strong> <?php echo $row['Remarks']; ?></span>
                                    <span style="flex-grow: 1;"></span> <!-- Spacer -->
                                    <span><strong>Unit:</strong> <?php echo $row['Unit']; ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <form action="" method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="itemId" value="<?php echo $row['itemId']; ?>">
                                    <input type="hidden" name="name" value="<?php echo $row['name']; ?>">
                                    <input type="hidden" name="category" value="<?php echo $row['category']; ?>">
                                    <input type="hidden" name="description" value="<?php echo $row['description']; ?>">
                                    <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                    <input type="hidden" name="stock_quantity" value="<?php echo $row['stock_quantity']; ?>">
                                    <input type="hidden" name="remarks" value="<?php echo $row['Remarks']; ?>">
                                    <input type="hidden" name="unit" value="<?php echo $row['Unit']; ?>">
                                    <div class="select-quantity">
                                        <input type="number" class="integer-input" name="selected_quantity" min="1" step="<?php echo ($row['Unit'] == 'Packets') ? '1' : '1'; ?>" value="0" max="0" data-item-id="<?php echo $row['itemId']; ?>" data-limit="<?php echo $row['limitt']; ?>">
                                        <input type="text" class="limit" hidden name="" value="<?php echo $row['limitt']; ?>">
                                        <input type="text" class="item-id" hidden name="" value="<?php echo $row['itemId']; ?>">
                                        <button type="submit" name="Add_To_Order" class="btn btn-outline-primary add-btn" style="padding: 0.2rem 0.5rem; font-size: 0.8em;" data-limit="<?php echo $row['limitt']; ?>" data-item-id="<?php echo $row['itemId']; ?>" max="<?php echo $row['limitt']; ?>" onclick="ToggleOrderList()">Add To Order</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                <?php
                    }

                    // Free result set
                    mysqli_free_result($result);

                    // Pagination links
                    $sql_pagination = "SELECT COUNT(*) AS total FROM items WHERE name LIKE '%$search%' OR itemId LIKE '%$search%' OR category LIKE '%$search%' OR description LIKE '%$search%' OR price LIKE '%$search%' OR stock_quantity LIKE '%$search%'";

                    if (!empty($category_filter)) {
                        $sql_pagination .= " AND category = '$category_filter'";
                    }


                    $result_pagination = mysqli_query($conn, $sql_pagination);
                    $row_pagination = mysqli_fetch_assoc($result_pagination);
                    $total_pages = ceil($row_pagination['total'] / $results_per_page);

                    // Display pagination controls if there's more than one page
                    if ($total_pages > 1) {
                        echo '<div class="d-flex justify-content-center mt-4">';
                        echo '<ul class="pagination">';
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . $search . '&category_filter=' . $category_filter . '">' . $i . '</a></li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                } else {
                    echo "<p>No items found.</p>";
                }
                ?>
            </div>


        </div>

        <div class="order-list">
            <h4>Order List</h4>
            <?php
            $arr = array();


            $total_items = 0;
            $total_price = 0;

            if (isset($_SESSION['order_list']) && count($_SESSION['order_list']) > 0) {
                foreach ($_SESSION['order_list'] as $index => $item) {


                    $item_total_price = $item['price'] * $item['selected_quantity'];
                    $total_items += $item['selected_quantity'];
                    $total_price += $item_total_price;

            ?>
                    <div class="order-list-item" style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="display: flex; justify-content: space-between;">
                            <div>
                                <?php echo $item['name']; ?>
                                (<span id="<?= $item["itemId"] . '-qty' ?>"><?php echo $item['selected_quantity']; ?></span>
                                x Rs.<?php echo $item['price']; ?>) =
                                Rs.<span id="<?= $item["itemId"] . '-total' ?>"><?php echo $item_total_price; ?></span>
                            </div>
                            <div>
                                <button onclick="IncreaseQuantity(<?= $item['itemId'] ?>)">+</button>
                                <button onclick="DecreaseQuantity(<?= $item['itemId'] ?>)">-</button>
                                <input type="text" id="intial_price" hidden value="<?php echo $item_total_price; ?>">
                            </div>
                        </span>
                        <form action="" method="POST" style="padding-top: 14px;">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="submit" name="Remove_From_Order">X</button>
                        </form>
                    </div>
            <?php
                }
            } else {
                echo "<p>No items in the order list.</p>";
            }
            ?>
            <div class="order-summary">
                <span id="total-items">Total Items:<?php echo $total_items; ?></span>
                <span id="total-price">Total Price: Rs.<?php echo $total_price; ?></span>
                <input type="text" id="total_items" hidden value="<?php echo $total_items; ?>">
                <input type="text" id="total_price" hidden value="<?php echo $total_price; ?>">
                <form action="" method="POST" class="mt-3">
                    <input type="text" name="session" id="sessionId" value="" hidden>
                    <button type="submit" name="Add_To_Cart" class="btn btn-success">Add All to Cart</button>
                </form>
            </div>
        </div>
    </div>
    <script src="jquery-3.3.1.slim.min.js"></script>
    <!-- Optional JavaScript -->
    <script src="popper.min.js"></script>
    <script src="bootstrap.min1.js"></script>
    <script src="dataTables.min.js"></script>

    <script>
        $(document).ready(function() {});

        function runAfter() {
            $(".integer-input").each(function() {
                var $input = $(this);
                var itemId = $input.data('item-id');

                fetch(`api.php?method=fetchlimit1&itemid=${itemId}`, {
                        method: 'GET',
                    })
                    .then(response => response.json()) // Parse the JSON response
                    .then(data => { // Process the data from the first fetch
                        console.log("this is data: " + data);
                        var maxValue = parseInt($input.attr('max'));
                        maxValue = Math.min(data, maxValue);

                        // Make the second fetch call inside the first then block
                        return fetch(`api.php?operation=stock&itemId=${itemId}`, {
                            method: 'GET',
                        }).then(response => response.json()); // Return the parsed JSON response
                    })
                    .then(data => { // Process the data from the second fetch
                        if (data.status === 200) {
                            var maxValue = parseInt($input.attr('max'));
                            maxValue = Math.min(data.stock_quantity, maxValue);
                            $input.attr('max', maxValue); // Set the max attribute for the current input
                        }
                    })
                    .catch(error => console.error('Error:', error)); // Add error handling
            });
        }


        function IncreaseQuantity(itemId) {
            maxvalue = parseInt(sessionStorage.getItem(itemId));
            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        operation: 'increase',
                        itemId: itemId,
                        maxValue: maxvalue,
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200) {
                        const itemId = data.id;
                        const price = data.price;
                        const quantity = data.quantity;
                        const total = price * quantity;
                        let total_items = parseInt($("#total_items").val());
                        $("#total_items").val(total_items + 1);
                        $("#" + itemId + "-qty").text(quantity);
                        $("#" + itemId + "-total").text(total);
                        let currentTotalPrice = parseInt($("#total_price").val()) + price;
                        $("#total-items").text("Total Items: " + (total_items + 1));
                        $("#total-price").text("Total Price: Rs." + (currentTotalPrice));
                        $("#total_price").val(currentTotalPrice);
                    }
                })
                .catch(error => {
                    console.log('Error:', error);
                });
        }

        function DecreaseQuantity(itemId) {
            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        operation: 'decrease',
                        itemId: itemId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200) {
                        const itemId = data.id;
                        const price = data.price;
                        const quantity = data.quantity;
                        if (quantity <= 0) {
                            return;
                        }
                        const total = price * quantity;
                        let total_items = parseInt($("#total_items").val());
                        $("#total_items").val(total_items - 1);
                        $("#" + itemId + "-qty").text(quantity);
                        $("#" + itemId + "-total").text(total);
                        let currentTotalPrice = parseInt($("#total_price").val()) - price;
                        $("#total-items").text("Total Items: " + (total_items - 1));
                        $("#total-price").text("Total Price: Rs." + (currentTotalPrice));
                        $("#total_price").val(currentTotalPrice);
                    }
                })
                .catch(error => {
                    console.log('Error:', error);
                });
        }

        function toggleOrderList() {
            const orderList = document.querySelector('.order-list');
            const hasItems = <?php echo isset($_SESSION['order_list']) && count($_SESSION['order_list']) > 0 ? 'true' : 'false'; ?>;
            if (hasItems) {
                orderList.classList.add('visible');
            } else {
                orderList.classList.remove('visible');
            }
        }

        $(document).ready(function() {
            function getAllSessionData() {
                let data = {};
                for (let i = 0; i < sessionStorage.length; i++) {
                    let key = sessionStorage.key(i);
                    data[key] = sessionStorage.getItem(key);
                }
                return JSON.stringify(data);
            }
            $('#sessionId').val(getAllSessionData());

            // Check if there is an order list
            let hasList = <?php echo isset($_SESSION['order_list']) && count($_SESSION['order_list']) > 0 ? 'true' : 'false'; ?>;
            if (hasList) {
                toggleOrderList();
            }

            console.log('hello');
            var $dataMap = new Map();

            fetch('api.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(data);

                    // Convert response data to a Map
                    // var dataMap = new Map(Object.entries(data));
                    var dataMap = new Map(Object.entries(data).map(([key, value]) => [parseInt(key), parseFloat(value)]));
                    console.log(dataMap);

                    // Function to set max value
                    function setMaxValue(itemId, limit) {
                        $('.card').each(function() {
                            var $this = $(this);
                            var $input = $this.find('.integer-input');
                            var $dataLimit = $this.find('.limit').val();
                            var $dataItemId = $this.find('.item-id').val();

                            if ($dataItemId == itemId) {
                                var maxValue = limit - (dataMap.has(itemId) ? dataMap.get(itemId) : 0);
                                // console.log(itemId);
                                // let stock = fetchStock(itemId);
                                // console.log("this is stock: " + stock);
                                // maxValue = Math.min(stock, maxValue);
                                // console.log("this is maxValue: " + maxValue);
                                if (itemId == 100) {
                                    console.log(maxValue);
                                    console.log(itemId);
                                    console.log(dataMap.get(itemId)); // Map { 100 → "4.00" } item id is 100
                                    console.log(dataMap.has(itemId));
                                    console.log(limit);
                                }
                                sessionStorage.setItem(itemId, maxValue);

                                $input.attr('max', maxValue);
                                runAfter();
                            }
                        });
                    }

                    // Apply max value for each item in the DOM
                    $('.add-btn').each(function() {
                        var $button = $(this);
                        var itemId = $button.data('item-id');
                        var limit = parseFloat($button.data('limit'));

                        setMaxValue(itemId, limit);
                    });
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });

            // Print button functionality
            document.getElementById('print-btn').addEventListener('click', function() {
                window.print();
            });

            // Toggle Order List functionality
            document.getElementById('toggle-order-list-btn').addEventListener('click', function() {
                const orderList = document.querySelector('.order-list');
                orderList.classList.toggle('visible');
            });

            // Category filter change event
            $("#category-filter").change(function() {
                $("#filter-form").submit();
                console.log('category-filter changed');
            });

        });
        // $(".integer-input").each(function() {
        //     var $input = $(this);
        //     var itemId = $input.data('item-id');
        //     var limit = parseFloat($input.data('limit'));

        //     // Assuming fetchStock is an asynchronous function returning a promise
        //     console.log("ready to take js itemId: " + itemId);
        //     var stock = fetchStock(itemId);
        //     console.log("stock: " + stock);
        //     $input.attr('max', Math.min(stock, limit));
        // });
    </script>

</body>


</html>
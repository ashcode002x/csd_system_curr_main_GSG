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

$item_id = $_GET['item_id']; // Get the item ID from the query parameter

$query = "SELECT i.*, od.quantity, od.price FROM items i JOIN order_details od ON i.itemId = od.item_id WHERE i.itemId = $item_id";
$result = mysqli_query($conn, $query);

$item = mysqli_fetch_assoc($result);

if (!$item) {
    die("Item not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="all.min.css">
    <style>
        body {
            background-color: #f0f8ff; /* Light blue background color */
            font-family: Arial, sans-serif;
            transition: background-color 0.5s ease;
        }

        .container {
            margin-top: 20px;
            background-color: #ffffff; /* White background for form */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }

        .container:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        h2 {
            color: #3498db; /* Blue color for headings */
            transition: color 0.3s ease;
        }

        h2:hover {
            color: #2980b9; /* Darker blue for hover effect */
        }

        .form-group label {
            font-weight: bold;
            color: #2c3e50; /* Darker shade for labels */
            transition: color 0.3s ease;
        }

        .form-group label:hover {
            color: #34495e; /* Slightly lighter shade for hover effect */
        }

        .form-control {
            background-color: #ecf0f1; /* Light grey background for inputs */
            color: #2c3e50; /* Dark text color for inputs */
            border: 1px solid #bdc3c7; /* Light grey border for inputs */
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db; /* Blue border for focus effect */
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        .btn-primary, .btn-secondary {
            margin: 10px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-primary {
            background-color: #3498db; /* Bright blue for primary button */
            border-color: #3498db;
        }

        .btn-primary:hover {
            background-color: #2980b9; /* Darker blue for hover effect */
            border-color: #2980b9;
            transform: scale(1.05); /* Slightly larger on hover */
        }

        .btn-secondary {
            background-color: #2ecc71; /* Green for secondary button */
            border-color: #2ecc71;
        }

        .btn-secondary:hover {
            background-color: #27ae60; /* Darker green for hover effect */
            border-color: #27ae60;
            transform: scale(1.05); /* Slightly larger on hover */
        }

        .button-group {
            text-align: center;
        }

        .item-image {
            max-width: 200px; /* Limit the size of the image */
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h2>Item Details</h2>
        <form>
            <div class="form-group">
                <label for="item_image">Item Image</label>
                <?php if ($item['item_image']): ?>
                    <img src="<?php echo 'items_image/' . $item['item_image']; ?>" alt="Item Image" class="item-image">
                <?php else: ?>
                    <p>No image available</p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="item_id">Item ID</label>
                <input type="text" id="item_id" class="form-control" value="<?php echo $item['itemId']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" class="form-control" value="<?php echo $item['name']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" class="form-control" value="<?php echo $item['category']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" class="form-control" readonly><?php echo $item['description']; ?></textarea>
            </div>
            <div class="form-group">
                <label for="unit">Unit</label>
                <input type="text" id="unit" class="form-control" value="<?php echo $item['Unit']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="text" id="quantity" class="form-control" value="<?php echo $item['quantity']; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="text" id="price" class="form-control" value="<?php echo number_format($item['price'], 2); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" class="form-control" readonly><?php echo $item['Remarks']; ?></textarea>
            </div>
            <div class="form-group">
                <label for="total_price">Total Price</label>
                <input type="text" id="total_price" class="form-control" value="<?php echo number_format($item['price'] * $item['quantity'], 2); ?>" readonly>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='my_orders.php'">Back</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </form>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="jquery-3.3.1.slim.min.js"></script>
    <script src="popper.min.js"></script>
    <script src="bootstrap.min.js"></script>
</body>
</html>

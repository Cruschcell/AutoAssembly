<?php
include 'dbconnection.php';
if (!isset($_SESSION['customerID'])) {
    header("Location: log-in.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = "No order specified.";
    header("Location: mainpage.php");
    exit();
}

$orderID = $_GET['order_id'];
$customerID = $_SESSION['customerID'];

$order_query = $conn->prepare("SELECT o.*, p.paymentType, p.paymentStatus, 
       SUM(od.quantity * pr.prodPrice) as totalAmount 
FROM orders o
LEFT JOIN payment p ON o.orderID = p.orderID
LEFT JOIN order_details od ON o.orderID = od.orderID
LEFT JOIN product pr ON od.productID = pr.productID
WHERE o.orderID = ? AND (o.customerID = ? OR o.recipientID = ?)
GROUP BY o.orderID");
$order_query->bind_param("iii", $orderID, $customerID, $customerID);

$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows == 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: mainpage.php");
    exit();
}
$order = $order_result->fetch_assoc();

$items_query = $conn->prepare("SELECT od.*, p.prodName, p.prodPrice, p.thumbnail 
                              FROM order_details od
                              JOIN product p ON od.productID = p.productID
                              WHERE od.orderID = ?");
$items_query->bind_param("i", $orderID);
$items_query->execute();
$items_result = $items_query->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="generalstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .confirmation-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .summary-box {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-md py-4">
        <div class="container-fluid">
            <a href="mainpage.php" class="navbar-brand fs-3">AutoAssembly</a>
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a href="<?php echo isset($_SESSION['username']) ? 'profile.php' : 'log-in.php'; ?>" class="nav-link fs-4 px-3">
                            <?php
                            if (isset($_SESSION['username'])) {
                                echo htmlspecialchars($_SESSION['username']);
                            } else {
                                echo "Guest User";
                            }
                            ?>
                        </a>
                    </li>
                    <li class="nav-item"><a href="mainpage.php" class="nav-link fs-4 px-3">Store</a></li>
                    <li class="nav-item"><a href="support.php" class="nav-link fs-4 px-3">Support</a></li>
                    <li class="nav-item"><a href="about.php" class="nav-link fs-4 px-3">About</a></li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['username'])): ?>
                        <li class="nav-item"><a href="?logout=true" class="nav-link small text-white">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a href="register.php" class="nav-link small text-white">Register</a></li>
                        <li class="nav-item"><a href="log-in.php" class="nav-link small text-white">Log-in</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="confirmation-container bg-white">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="confirmation-header text-center">
                <h1 class="text-success mb-3"><i class="bi bi-check-circle-fill"></i> Order Confirmed</h1>
                <p class="lead">Thank you for your purchase, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Customer'); ?>!</p>
                <p>Your order #<?php echo $orderID; ?> has been received and is being processed.</p>
            </div>

            <div class="row mt-4">
                <div class="col-md-8">
                    <h4>Order Details</h4>
                    
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <div class="order-item d-flex">
                            <div class="flex-shrink-0">
                                <img src="<?php echo !empty($item['thumbnail']) ? htmlspecialchars($item['thumbnail']) : 'images/placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['prodName']); ?>" 
                                     class="item-image">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5><?php echo htmlspecialchars($item['prodName']); ?></h5>
                                <p class="mb-1">Quantity: <?php echo $item['quantity']; ?></p>
                                <p class="mb-1">Price: ₱<?php echo number_format($item['prodPrice'], 2); ?></p>
                                <p class="mb-0">Subtotal: ₱<?php echo number_format($item['quantity'] * $item['prodPrice'], 2); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="summary-box">
                        <h4>Order Summary</h4>
                        <table class="table table-borderless">
                            <tr>
                                <td>Order Number:</td>
                                <td class="text-end">#<?php echo $orderID; ?></td>
                            </tr>
                            <tr>
                                <td>Order Date:</td>
                                <td class="text-end"><?php echo date('F j, Y', strtotime($order['orderDate'])); ?></td>
                            </tr>
                            <tr>
                                <td>Payment Method:</td>
                                <td class="text-end text-capitalize"><?php echo htmlspecialchars($order['paymentType']); ?></td>
                            </tr>
                            <tr>
                                <td>Payment Status:</td>
                                <td class="text-end text-capitalize"><?php echo htmlspecialchars($order['paymentStatus']); ?></td>
                            </tr>
                            <tr>
                                <td>Order Status:</td>
                                <td class="text-end text-capitalize"><?php echo htmlspecialchars($order['orderStatus']); ?></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong>₱<?php echo number_format($order['totalAmount'], 2); ?></strong></td>
                            </tr>
                        </table>
                        
                        <?php if ($order['paymentType'] === 'cod'): ?>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> Please prepare the exact amount for cash on delivery.
                            </div>
                        <?php elseif ($order['paymentType'] === 'gcash' || $order['paymentType'] === 'paymaya'): ?>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i> Payment instructions have been sent to your mobile number.
                            </div>
                        <?php endif; ?>
                        
                        <a href="mainpage.php" class="btn btn-primary w-100 mt-3">Continue Shopping</a>
                        <a href="profile.php" class="btn btn-outline-secondary w-100 mt-2">View Order History</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
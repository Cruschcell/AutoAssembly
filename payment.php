<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method</title>
    <link rel="stylesheet" href="generalstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .payment-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .conditional-field {
            display: none;
            margin-top: 1rem;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .btn-continue {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
        }
        .region-note {
            font-size: 0.9rem;
            color: #666;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<?php
include 'dbconnection.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: log-in.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['customerID'])) {
    header("Location: log-in.php");
    exit();
}

$customerID = $_SESSION['customerID'];

// Check if order ID is provided
if (isset($_GET['order_id'])) {
    $orderID = $_GET['order_id'];
} else {
    $_SESSION['error'] = "No order specified.";
    header("Location: mainpage.php");
    exit();
}

// Validate order belongs to customer
$order_query = $conn->prepare("SELECT o.*, SUM(od.quantity * p.prodPrice) as totalAmount 
                               FROM orders o 
                               JOIN order_details od ON o.orderID = od.orderID 
                               JOIN product p ON od.productID = p.productID
                               WHERE o.orderID = ? AND o.customerID = ?
                               GROUP BY o.orderID");
$order_query->bind_param("ii", $orderID, $customerID);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows == 0) {
    $_SESSION['error'] = "Invalid order.";
    header("Location: mainpage.php");
    exit();
}

$order = $order_result->fetch_assoc();
$totalAmount = $order['totalAmount'];

// Process payment form submission
$mobileNumber = isset($_POST['mobileNumber']) ? $_POST['mobileNumber'] : null;
if (isset($_POST['confirm_payment'])) {
    $paymentType = $_POST['paymentMethod'];
    $billAddress = $_POST['billAddress'];
    $paymentStatus = 'pending';
    
    $conn->begin_transaction();
    
    try {
        // Check inventory
        $check_details = $conn->prepare("SELECT od.*, p.prodInv, p.prodName FROM order_details od 
                                        JOIN product p ON od.productID = p.productID 
                                        WHERE od.orderID = ?");
        $check_details->bind_param("i", $orderID);
        $check_details->execute();
        $check_result = $check_details->get_result();
        
        $inventory_ok = true;
        $error_product = "";
        $error_product_name = "";
        
        // Check inventory for all products
        while ($item = $check_result->fetch_assoc()) {
            if ($item['quantity'] > $item['prodInv']) {
                $inventory_ok = false;
                $error_product = $item['productID'];
                $error_product_name = $item['prodName'];
                break;
            }
        }
        
        if (!$inventory_ok) {
            throw new Exception("Not enough inventory for " . $error_product_name);
        }
        
        // Insert payment record
        $insert_payment = $conn->prepare("INSERT INTO payment (paymentType, billAddress, amount, paymentStatus, orderID) VALUES (?, ?, ?, ?, ?)");
        $insert_payment->bind_param("ssdsi", $paymentType, $billAddress, $totalAmount, $paymentStatus, $orderID);
        $insert_payment->execute();
        
        // Update inventory
        $check_details->execute();
        $check_result = $check_details->get_result();
        
        while ($item = $check_result->fetch_assoc()) {
            $new_inventory = $item['prodInv'] - $item['quantity'];
            $update_inv = $conn->prepare("UPDATE product SET prodInv = ? WHERE productID = ?");
            $update_inv->bind_param("ii", $new_inventory, $item['productID']);
            $update_inv->execute();
        }
        
        // Update order status
        $update_order = $conn->prepare("UPDATE orders SET orderStatus = 'to be delivered', orderDate = NOW() WHERE orderID = ?");
        $update_order->bind_param("i", $orderID);
        $update_order->execute();
        
        // For COD, mark as pending; for other methods, mark as completed
        if ($paymentType == 'cod') {
            $payment_status = 'pending';
        } else {
            $payment_status = 'completed';
        }
        
        $update_payment = $conn->prepare("UPDATE payment SET paymentStatus = ? WHERE orderID = ?");
        $update_payment->bind_param("si", $payment_status, $orderID);
        $update_payment->execute();
        
        if ($paymentType === 'gcash' || $paymentType === 'paymaya') {
        $update_mobile = $conn->prepare("UPDATE orders SET mobileNumber = ? WHERE orderID = ?");
        $update_mobile->bind_param("si", $mobileNumber, $orderID);
        $update_mobile->execute();
    }

        $conn->commit();
        
        // Success - redirect to view order page instead of order_confirmation.php
        $_SESSION['success'] = "Payment processed successfully!";
        header("Location: order_confirmation.php?order_id=" . $orderID);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        
        $_SESSION['error'] = $e->getMessage();
        header("Location: payment.php?order_id=" . $orderID);
        exit();
    }
}

// Handle cancel order
if (isset($_POST['cancel_order'])) {
    try {
        $conn->begin_transaction();
        
        // Delete order details first (foreign key constraint)
        $delete_details = $conn->prepare("DELETE FROM order_details WHERE orderID = ?");
        $delete_details->bind_param("i", $orderID);
        $delete_details->execute();
        
        // Then delete the order
        $delete_order = $conn->prepare("DELETE FROM orders WHERE orderID = ? AND customerID = ?");
        $delete_order->bind_param("ii", $orderID, $customerID);
        $delete_order->execute();
        
        if ($delete_order->affected_rows == 0) {
            throw new Exception("Failed to cancel order.");
        }
        
        $conn->commit();
        $_SESSION['success'] = "Order has been cancelled successfully.";
        header("Location: mainpage.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: payment.php?order_id=" . $orderID);
        exit();
    }
}
?>
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
        <div class="payment-container bg-white">
            <div class="payment-header">
                <h3 class="mb-0">Payment Method</h3>
            </div>
            
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
            
            <div class="order-summary mb-4">
                <h4>Order Summary</h4>
                <p><strong>Total Amount:</strong> ₱<?php echo number_format($totalAmount, 2); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Select Payment Method</label>
                    <select class="form-select" id="paymentMethod" name="paymentMethod" required>
                        <option value="" selected disabled>Choose payment method</option>
                        <option value="cod">Cash on Delivery</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Billing Address</label>
                    <textarea class="form-control" name="billAddress" rows="3" placeholder="Enter your full billing address" required></textarea>
                </div>
                
                <div id="mobileFields" class="conditional-field">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control" name="mobileNumber" placeholder="09XXXXXXXXX">
                        <small class="text-muted">We'll send payment instructions to this number</small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" name="cancel_order" class="btn btn-outline-danger">Cancel Order</button>
                    <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.getElementById('paymentMethod');
            const mobileFields = document.getElementById('mobileFields');
            
            paymentMethod.addEventListener('change', function() {
                mobileFields.style.display = 'none';
                
                if (this.value === 'gcash' || this.value === 'paymaya') {
                    mobileFields.style.display = 'block';
                }
            });
        });
    </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

</body>

</html>
<?php
include 'dbconnection.php';

if (!isset($_SESSION['customerID'])) {
    header("Location: log-in.php");
    exit();
}

$customerID = $_SESSION['customerID'];

// Get friends
$friend_stmt = $conn->prepare("
    SELECT c.customerID, c.username
    FROM customer c
    WHERE c.customerID IN (
        SELECT f.receiverID FROM friends f WHERE f.senderID = ? AND f.status = 'accepted'
        UNION
        SELECT f.senderID FROM friends f WHERE f.receiverID = ? AND f.status = 'accepted'
    )
");
$friend_stmt->bind_param("ii", $customerID, $customerID);
$friend_stmt->execute();
$friend_result = $friend_stmt->get_result();

// Gift creation logic
if (isset($_GET['product_id'], $_GET['recipient_id'])) {
    $productID = (int) $_GET['product_id'];
    $recipientID = (int) $_GET['recipient_id'];

    // Check product existence
    $product_stmt = $conn->prepare("SELECT * FROM product WHERE productID = ?");
    $product_stmt->bind_param("i", $productID);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();

    if ($product_result->num_rows === 0) {
        $_SESSION['error'] = "Invalid product.";
        header("Location: mainpage.php");
        exit();
    }

    // Create new order
    $create_order = $conn->prepare("INSERT INTO orders (customerID, orderStatus) VALUES (?, 'initiated')");
    $create_order->bind_param("i", $customerID);
    $create_order->execute();
    $new_order_id = $create_order->insert_id;

    // Add to order_details
    $insert_details = $conn->prepare("INSERT INTO order_details (orderID, productID, quantity) VALUES (?, ?, 1)");
    $insert_details->bind_param("ii", $new_order_id, $productID);
    $insert_details->execute();

    header("Location: giftcheckout.php?order_id=" . $new_order_id);
    exit();
}

// Continue with existing gift order
$orderID = isset($_GET['order_id']) ? (int) $_GET['order_id'] : (isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0);

// Add debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug POST data
    error_log("POST data: " . print_r($_POST, true));
}

$order_query = $conn->prepare("
    SELECT o.*, SUM(od.quantity * p.prodPrice) as totalAmount 
    FROM orders o 
    JOIN order_details od ON o.orderID = od.orderID 
    JOIN product p ON od.productID = p.productID
    WHERE o.orderID = ? AND o.customerID = ?
    GROUP BY o.orderID
");
$order_query->bind_param("ii", $orderID, $customerID);
$order_query->execute();
$order_result = $order_query->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found or does not belong to you.";
    header("Location: mainpage.php");
    exit();
}

$totalAmount = $order['totalAmount'];

// Debug the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request - confirm_gift set: " . (isset($_POST['confirm_gift']) ? 'yes' : 'no'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_gift'])) {
    // Debug the form values
    error_log("Processing gift confirmation");
    error_log("RecipientID: " . (isset($_POST['recipientID']) ? $_POST['recipientID'] : 'not set'));
    error_log("Payment Method: " . (isset($_POST['paymentType']) ? $_POST['paymentType'] : 'not set'));
    error_log("Mobile Number: " . (isset($_POST['mobileNumber']) ? $_POST['mobileNumber'] : 'not set'));
    
    $recipientID = isset($_POST['recipientID']) ? (int) $_POST['recipientID'] : 0;
    $paymentType = isset($_POST['paymentType']) ? trim($_POST['paymentType']) : '';
    $mobileNumber = isset($_POST['mobileNumber']) ? trim($_POST['mobileNumber']) : '';

    // Validate input fields
    if (!$recipientID || empty($paymentType) || empty($mobileNumber)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: giftcheckout.php?order_id=" . $orderID);
        exit();
    }

    try {
        // Begin transaction
        $conn->begin_transaction();
        
       $gift_stmt = $conn->prepare("
            UPDATE orders 
            SET isGift = 1, recipientID = ?, mobileNumber = ?, orderStatus = 'to be delivered', giftStatus = 'pending'
            WHERE orderID = ? AND customerID = ?
        ");
        $gift_stmt->bind_param("isii", $recipientID, $mobileNumber, $orderID, $customerID);
        $gift_result = $gift_stmt->execute();
         
        // Recalculate total
        $amount_stmt = $conn->prepare("
            SELECT SUM(od.quantity * p.prodPrice) AS total
            FROM order_details od
            JOIN product p ON od.productID = p.productID
            WHERE od.orderID = ?
        ");
        $amount_stmt->bind_param("i", $orderID);
        $amount_stmt->execute();
        $amount_result = $amount_stmt->get_result();
        $amount_row = $amount_result->fetch_assoc();
        $total = $amount_row['total'] ?? 0;

        $insert_payment = $conn->prepare("
            INSERT INTO payment (orderID, paymentType, paymentStatus, amount)
            VALUES (?, ?, 'completed', ?)
            ON DUPLICATE KEY UPDATE
                paymentType = VALUES(paymentType),
                paymentStatus = 'completed',
                amount = VALUES(amount)
        ");
        $insert_payment->bind_param("isd", $orderID, $paymentType, $total);
        $insert_payment->execute();

        if (!$gift_result) {
            throw new Exception("Failed to update order: " . $conn->error);
        }
       
        // Update final order amount
        $update_order_stmt = $conn->prepare("UPDATE orders SET totalAmount = ? WHERE orderID = ?");
        $update_order_stmt->bind_param("di", $total, $orderID);
        $update_result = $update_order_stmt->execute();
        
        if (!$update_result) {
            throw new Exception("Failed to update order total: " . $conn->error);
        }

        // Remove from wishlist if exists
        $product_query = $conn->prepare("SELECT productID FROM order_details WHERE orderID = ? LIMIT 1");
        $product_query->bind_param("i", $orderID);
        $product_query->execute();
        $product_result = $product_query->get_result();
        $product_row = $product_result->fetch_assoc();
        $productID = $product_row['productID'] ?? null;

        if ($productID) {
            $delete_wishlist = $conn->prepare("DELETE FROM wishlist WHERE customerID = ? AND productID = ?");
            $delete_wishlist->bind_param("ii", $recipientID, $productID);
            $delete_wishlist->execute();
        }
        
        // Commit transaction
        $conn->commit();

        // Log success and redirect
        error_log("Gift order processed successfully. Redirecting to order_confirmation.php?order_id=" . $orderID);
        $_SESSION['success'] = "Gift sent successfully!";
        header("Location: order_confirmation.php?order_id=" . $orderID);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Gift order error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to process gift order: " . $e->getMessage();
        header("Location: giftcheckout.php?order_id=" . $orderID);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift Checkout</title>
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
    <div class="payment-container bg-white p-4">
        <h3 class="mb-4 payment-header">Gift Checkout</h3>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <h5>Order Summary</h5>
            <p><strong>Total Amount:</strong> ₱<?php echo number_format($totalAmount, 2); ?></p>
        </div>

        <form method="POST" action="giftcheckout.php?order_id=<?php echo $orderID; ?>">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($orderID); ?>">
            <div class="form-group mb-3">
                <label for="recipientID" class="form-label">Select Recipient</label>
                <select class="form-select" name="recipientID" id="recipientID" required>
                    <option value="" disabled selected>Select a friend to gift to</option>
                    <?php 
                    // Reset the result pointer since we used it earlier
                    $friend_stmt->execute();
                    $friend_result = $friend_stmt->get_result();
                    
                    while ($friend = $friend_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $friend['customerID']; ?>"><?php echo htmlspecialchars($friend['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="paymentType" class="form-label">Payment Method</label>
                <select class="form-select" id="paymentType" name="paymentType" required>
                    <option value="" disabled selected>Select a payment method</option>
                    <option value="gcash">GCash</option>
                    <option value="paymaya">PayMaya</option>
                </select>
            </div>

            <div class="form-group mb-3 conditional-field" id="mobileField">
                <label for="mobileNumber" class="form-label">Mobile Number</label>
                <input type="tel" class="form-control" name="mobileNumber" id="mobileNumber" placeholder="09XXXXXXXXX" required>
                <small class="text-muted">Used for sending payment instructions</small>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="mainpage.php" class="btn btn-outline-secondary">Back</a>
                <button type="submit" name="confirm_gift" class="btn btn-primary btn-continue">Confirm & Send Gift</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentType = document.getElementById('paymentType');
        const mobileField = document.getElementById('mobileField');
        const mobileNumber = document.getElementById('mobileNumber');

        paymentType.addEventListener('change', function () {
            if (this.value === 'gcash' || this.value === 'paymaya') {
                mobileField.style.display = 'block';
                mobileNumber.setAttribute('required', 'required');
            } else {
                mobileField.style.display = 'none';
                mobileNumber.removeAttribute('required');
            }
        });
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            const recipient = document.getElementById('recipientID').value;
            const payment = document.getElementById('paymentType').value;
            
            let hasError = false;
            
            if (!recipient) {
                hasError = true;
                alert("Please select a recipient");
            }
            
            if (!payment) {
                hasError = true;
                alert("Please select a payment method");
            }
            
            if (mobileField.style.display === 'block' && !mobileNumber.value) {
                hasError = true;
                alert("Please enter your mobile number");
            }
            
            if (hasError) {
                event.preventDefault();
            }
        });
    });
</script>

</body>
</html>
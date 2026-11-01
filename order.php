<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <link rel="stylesheet" href="generalstyle.css">
    <link rel="stylesheet" href="order.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php
include 'dbconnection.php';
if (!isset($_SESSION['customerID'])) {
    header("Location: log-in.php");
    exit();
}

$customerID = $_SESSION['customerID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productID']) && isset($_POST['rating'])) {
    $productID = $_POST['productID'];
    $rating = $_POST['rating'];
    $revcontent = isset($_POST['revcontent']) ? $_POST['revcontent'] : '';
    $orderID = $_POST['orderID'];
    
    $insert_review = $conn->prepare("INSERT INTO reviews (customerID, productID, rating, revcontent, revDate) VALUES (?, ?, ?, ?, NOW())");
    $insert_review->bind_param("iiis", $customerID, $productID, $rating, $revcontent);
    
    if ($insert_review->execute()) {
        $_SESSION['success'] = "Your review has been submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting review: " . $insert_review->error;
    }
    
    header("Location: order.php?order_id=" . $orderID);
    exit();
}

if (isset($_GET['order_id'])) {
    $orderID = $_GET['order_id'];
    $viewSingleOrder = true;
} else {
    $viewSingleOrder = false;
}

function getProductImage($conn, $productID) {
    $img_query = $conn->prepare("SELECT thumbnail FROM product WHERE productID = ? LIMIT 1");
    $img_query->bind_param("i", $productID);
    $img_query->execute();
    $img_result = $img_query->get_result();
    
    if ($img_result->num_rows > 0) {
        $img = $img_result->fetch_assoc();
        return $img['thumbnail'];
    } else {
        return "https://jolstatic.fr/www/captures/4949/2/144312-800.jpg"; 
    }
}
?>

<nav class="navbar navbar-expand-md py-4">
    <div class="container-fluid">
        <a href="#" class="navbar-brand fs-3">AutoAssembly</a>
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
                    <li class="nav-item"><a href="log-in.php" class="nav-link small text-white">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success mt-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mt-4">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($viewSingleOrder): ?>
        <?php
        $order_query = $conn->prepare("SELECT o.* FROM orders o 
                                    WHERE o.orderID = ? AND o.customerID = ?
                                    LIMIT 1");
        $order_query->bind_param("ii", $orderID, $customerID);
        $order_query->execute();
        $order_result = $order_query->get_result();
        
        if ($order_result->num_rows == 0): ?>
            <div class="alert alert-warning mt-4">
                Order not found. <a href="order.php">View all orders</a>
            </div>
        <?php else:
            $order = $order_result->fetch_assoc();
            
            $details_query = $conn->prepare("SELECT od.*, p.prodName, p.prodPrice, p.prodInv, p.prodDesc, 
                                            b.brandName, c.ctgName 
                                            FROM order_details od 
                                            JOIN product p ON od.productID = p.productID 
                                            JOIN brand b ON p.brandID = b.brandID
                                            JOIN category c ON p.ctgID = c.ctgID
                                            WHERE od.orderID = ?");
            $details_query->bind_param("i", $orderID);
            $details_query->execute();
            $details_result = $details_query->get_result();
            
            $warehouse_query = $conn->prepare("SELECT DISTINCT w.warehouseID, w.address 
                                            FROM order_details od
                                            JOIN product p ON od.productID = p.productID
                                            JOIN warehouse w ON p.shipped_from = w.warehouseID
                                            WHERE od.orderID = ?");
            $warehouse_query->bind_param("i", $orderID);
            $warehouse_query->execute();
            $warehouse_result = $warehouse_query->get_result();
            $warehouse = $warehouse_result->num_rows > 0 ? $warehouse_result->fetch_assoc() : ['address' => 'Main Warehouse'];
            
            $order_date = new DateTime($order['orderDate']);
            $processing_date = clone $order_date;
            $processing_date->modify('+1 day');
            $shipping_date = clone $processing_date;
            $shipping_date->modify('+1 day');
            $delivery_date = clone $shipping_date;
            $delivery_date->modify('+3 days');
            
            $current_step = 1; 
            if ($order['orderStatus'] == 'to be delivered') {
                $current_date = new DateTime();
                if ($current_date > $shipping_date) {
                    $current_step = 3; 
                } else if ($current_date > $processing_date) {
                    $current_step = 2; 
                }
            } else if ($order['orderStatus'] == 'delivered') {
                $current_step = 4; 
            }
        ?>
        
        <div class="order-container">
            <div class="order-header">
                <div>
                    <h3 class="mb-1">Order Details</h3>
                    <div class="order-id">Order #<?php echo $orderID; ?></div>
                </div>
                <div class="text-end">
                    <?php 
                    $statusClass = '';
                    switch ($order['orderStatus']) {
                        case 'delivered':
                            $statusClass = 'status-delivered';
                            break;
                        case 'to be delivered':
                            $statusClass = 'status-to-be-delivered';
                            break;
                        case 'failed':
                            $statusClass = 'status-failed';
                            break;
                    }
                    ?>
                    <div class="order-status <?php echo $statusClass; ?>">
                        <?php echo ucwords(str_replace('-', ' ', $order['orderStatus'])); ?>
                    </div>
                    <div class="mt-2">
                        <small>Ordered on <?php echo date('F j, Y', strtotime($order['orderDate'])); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="tracking-container">
                <h5 class="mb-4">Track Your Order</h5>
                
                <div class="step-indicator">
                    <div class="step <?php echo ($current_step >= 1) ? 'completed' : ''; ?>">
                        <div class="step-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-title">Order Processing</div>
                        <small><?php echo $processing_date->format('M j'); ?></small>
                    </div>
                    
                    <div class="step <?php echo ($current_step >= 2) ? 'completed' : ''; ?>">
                        <div class="step-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-title">Shipped</div>
                        <small><?php echo $shipping_date->format('M j'); ?></small>
                    </div>
                    
                    <div class="step <?php echo ($current_step >= 3) ? 'completed' : ($current_step == 3 ? 'active' : ''); ?>">
                        <div class="step-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-title">In Transit</div>
                        <small>In progress</small>
                    </div>
                    
                    <div class="step <?php echo ($current_step >= 4) ? 'completed' : ''; ?>">
                        <div class="step-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div class="step-title">Delivered</div>
                        <small><?php echo $delivery_date->format('M j'); ?></small>
                    </div>
                </div>
                
                <?php if ($order['orderStatus'] != 'delivered' && $order['orderStatus'] != 'failed'): ?>
                    <div class="map-container">
                        <p>Shipment map visualization would appear here</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="order-details">
                        <h5>Shipping Details</h5>
                        <div class="detail-row">
                            <div class="detail-label">Estimated Delivery:</div>
                            <div><?php echo $delivery_date->format('F j, Y'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Shipped From:</div>
                            <div><?php echo htmlspecialchars($warehouse['address']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Shipping Method:</div>
                            <div>Standard Shipping</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Tracking Number:</div>
                            <div>AA<?php echo sprintf('%08d', $orderID); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="order-details">
                        <h5>Order Summary</h5>
                        <div class="detail-row">
                            <div class="detail-label">Order Status:</div>
                            <div><?php echo ucwords(str_replace('-', ' ', $order['orderStatus'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div><?php echo date('F j, Y', strtotime($order['orderDate'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Total Amount:</div>
                            <div>₱<?php echo number_format($order['totalAmount'], 2); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div>
                                <?php 
                                $payment_query = $conn->prepare("SELECT paymentType FROM payment WHERE orderID = ?");
                                $payment_query->bind_param("i", $orderID);
                                $payment_query->execute();
                                $payment_result = $payment_query->get_result();
                                
                                if ($payment_result->num_rows > 0) {
                                    $payment = $payment_result->fetch_assoc();
                                    echo htmlspecialchars(ucfirst($payment['paymentType']));
                                } else {
                                    echo "Cash on Delivery";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="product-list">
                <h5>Order Items</h5>
                
                <?php if ($details_result->num_rows > 0): ?>
                    <?php while ($item = $details_result->fetch_assoc()): 
                        $productImage = getProductImage($conn, $item['productID']);
                    ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['prodName']); ?>" class="product-image">
                        
                        <div class="product-info">
                            <h6 class="product-name"><?php echo htmlspecialchars($item['prodName']); ?></h6>
                            <div class="product-meta">
                                <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($item['ctgName']); ?></span>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['brandName']); ?></span>
                            </div>
                        </div>
                        
                        <div class="product-price">
                            <div class="fw-bold mb-1">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="text-muted">Qty: <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No items found for this order.</div>
                <?php endif; ?>
            </div>
            
            <?php if ($order['orderStatus'] == 'delivered'): ?>
            <div class="help-section">
                <h5>What would you like to do next?</h5>
                <div class="mt-3">
                    <a href="mainpage.php" class="btn btn-primary btn-action">
                        <i class="bi bi-shop me-2"></i>Continue Shopping
                    </a>
                    <button class="btn btn-outline-primary btn-action" data-bs-toggle="modal" data-bs-target="#leaveReviewModal">
                        <i class="bi bi-star me-2"></i>Leave a Review
                    </button>
                    <a href="support.php" class="btn btn-outline-secondary btn-action">
                        <i class="bi bi-question-circle me-2"></i>Need Help?
                    </a>
                </div>
            </div>
            <?php elseif ($order['orderStatus'] == 'to be delivered'): ?>
            <div class="help-section">
                <p>Your order is on its way! If you have any questions or concerns, please contact our support team.</p>
                <a href="support.php" class="btn btn-outline-primary">
                    <i class="bi bi-headset me-2"></i>Contact Support
                </a>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="order.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to All Orders
                </a>
            </div>
        </div>
        
        <!-- Leave Review Modal -->
        <div class="modal fade" id="leaveReviewModal" tabindex="-1" aria-labelledby="leaveReviewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="leaveReviewModalLabel">Leave a Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please select a product to review:</p>
                        <form id="reviewForm" action="order.php" method="post">
                            <input type="hidden" name="orderID" value="<?php echo $orderID; ?>">
                            
                            <div class="mb-3">
                                <label for="productSelect" class="form-label">Product</label>
                                <select class="form-select" id="productSelect" name="productID" required>
                                    <option value="" selected disabled>Choose a product</option>
                                    <?php 
                                    // Reset the details query to get fresh results
                                    $details_query->execute();
                                    $details_result = $details_query->get_result();
                                    if ($details_result->num_rows > 0):
                                        while ($item = $details_result->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $item['productID']; ?>"><?php echo htmlspecialchars($item['prodName']); ?></option>
                                    <?php 
                                        endwhile;
                                    endif;
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating">
                                    <div class="btn-group" role="group">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" class="btn-check" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-warning" for="rating<?php echo $i; ?>"><?php echo $i; ?> ★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reviewContent" class="form-label">Your Review</label>
                                <textarea class="form-control" id="reviewContent" name="revcontent" rows="4" placeholder="Share your experience with this product..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="reviewForm" class="btn btn-primary">Submit Review</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
    <?php else: ?>
        <div class="my-4">
            <h2>Your Orders</h2>
            <p class="text-muted">View your order history and track shipments</p>
        </div>
        
        <?php
        $orders_query = $conn->prepare("SELECT o.*, 
                                   (SELECT COUNT(*) FROM order_details WHERE orderID = o.orderID) as item_count 
                                   FROM orders o 
                                   WHERE o.customerID = ? 
                                   ORDER BY o.orderDate DESC");
        $orders_query->bind_param("i", $customerID);
        $orders_query->execute();
        $orders_result = $orders_query->get_result();
        
        if ($orders_result->num_rows == 0):
        ?>
            <div class="alert alert-info">
                <p>You haven't placed any orders yet.</p>
                <a href="mainpage.php" class="btn btn-primary mt-3">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="order-list container" style="height:1000px">
                <?php while ($order = $orders_result->fetch_assoc()): 
                    $sample_query = $conn->prepare("SELECT p.productID FROM order_details od
                                                   JOIN product p ON od.productID = p.productID
                                                   WHERE od.orderID = ? LIMIT 1");
                    $sample_query->bind_param("i", $order['orderID']);
                    $sample_query->execute();
                    $sample_result = $sample_query->get_result();
                    $sample = $sample_result->fetch_assoc();
                    $thumbnail = "";
                    
                    if ($sample) {
                        $thumbnail = getProductImage($conn, $sample['productID']);
                    }
                    
                    $statusClass = '';
                    switch ($order['orderStatus']) {
                        case 'delivered':
                            $statusClass = 'status-delivered';
                            break;
                        case 'to be delivered':
                            $statusClass = 'status-to-be-delivered';
                            break;
                        case 'failed':
                            $statusClass = 'status-failed';
                            break;
                    }
                ?>
                <div class="order-summary">
                    <div class="order-summary-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['orderID']; ?></div>
                            <div class="small text-muted">Placed on <?php echo date('F j, Y', strtotime($order['orderDate'])); ?></div>
                        </div>
                        <div class="order-status <?php echo $statusClass; ?>">
                            <?php echo ucwords(str_replace('-', ' ', $order['orderStatus'])); ?>
                        </div>
                    </div>
                    
                    <div class="order-summary-details">
                        <?php if ($thumbnail): ?>
                        <div class="order-thumbnail">
                            <img src="<?php echo htmlspecialchars($thumbnail); ?>" alt="Order thumbnail">
                            <?php if ($order['item_count'] > 1): ?>
                            <span class="item-count">+<?php echo $order['item_count'] - 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="order-summary-info">
                            <div class="summary-row">
                                <span>Total:</span>
                                <span class="fw-bold">₱<?php echo number_format($order['totalAmount'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Items:</span>
                                <span><?php echo $order['item_count']; ?></span>
                            </div>
                        </div>
                        
                        <div class="order-summary-actions">
                            <a href="order.php?order_id=<?php echo $order['orderID']; ?>" class="btn btn-primary">View Details</a>
                            
                            <?php if ($order['orderStatus'] == 'delivered'): ?>
                            <button class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#leaveReviewModal<?php echo $order['orderID']; ?>">
                                Leave Review
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($order['orderStatus'] == 'delivered'): ?>
                <div class="modal fade" id="leaveReviewModal<?php echo $order['orderID']; ?>" tabindex="-1" aria-labelledby="leaveReviewModalLabel<?php echo $order['orderID']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="leaveReviewModalLabel<?php echo $order['orderID']; ?>">Leave a Review for Order #<?php echo $order['orderID']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="reviewForm<?php echo $order['orderID']; ?>" action="order.php" method="post">
                                    <input type="hidden" name="orderID" value="<?php echo $order['orderID']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="productSelect<?php echo $order['orderID']; ?>" class="form-label">Select Product</label>
                                        <select class="form-select" id="productSelect<?php echo $order['orderID']; ?>" name="productID" required>
                                            <option value="" selected disabled>Choose a product</option>
                                            <?php 
                                            // Get products for this order
                                            $products_query = $conn->prepare("SELECT od.productID, p.prodName 
                                                                          FROM order_details od 
                                                                          JOIN product p ON od.productID = p.productID 
                                                                          WHERE od.orderID = ?");
                                            $products_query->bind_param("i", $order['orderID']);
                                            $products_query->execute();
                                            $products_result = $products_query->get_result();
                                            
                                            while ($product = $products_result->fetch_assoc()): 
                                            ?>
                                            <option value="<?php echo $product['productID']; ?>"><?php echo htmlspecialchars($product['prodName']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating">
                                            <div class="btn-group" role="group">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <input type="radio" class="btn-check" name="rating" id="rating<?php echo $order['orderID']; ?>-<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($i == 5) ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="rating<?php echo $order['orderID']; ?>-<?php echo $i; ?>"><?php echo $i; ?> ★</label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reviewContent<?php echo $order['orderID']; ?>" class="form-label">Your Review</label>
                                        <textarea class="form-control" id="reviewContent<?php echo $order['orderID']; ?>" name="revcontent" rows="4" placeholder="Share your experience with this product..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="reviewForm<?php echo $order['orderID']; ?>" class="btn btn-primary">Submit Review</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<footer class="py-4 w-100" style="background-color: #527787; margin-top: 50px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <a href="mainpage.php" class="navbar-brand fs-3 mb-3 d-block">AutoAssembly</a>
                    <p class="text-muted">Your one-stop shop for auto parts and accessories.</p>
                    <div class="social-links">
                        <a href="#" class="me-3 text-dark"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="me-3 text-dark"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="me-3 text-dark"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2"><a href="mainpage.php" class="nav-link p-0 text-muted">Store</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Profile</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">About</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Support</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Lighting & Electronics</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Wheels</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Exterior Accessories</a></li>
                        <li class="nav-item mb-2"><a href="#" class="nav-link p-0 text-muted">Interior Accessories</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Contact Us</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2"><i class="bi bi-geo-alt me-2"></i>Negra Arroyo Lane, Albaquerque, New Mexico, 87615</li>
                        <li class="nav-item mb-2"><i class="bi bi-telephone me-2"></i>+63102023928320</li>
                        <li class="nav-item mb-2"><i class="bi bi-envelope me-2"></i>vroom@gmail.com</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
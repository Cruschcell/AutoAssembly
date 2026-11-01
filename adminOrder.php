<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <title>Jannie Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php
    include 'dbconnection.php';
    include 'janniecode.php';

    function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
    }

    function requireAdmin() {
        if (!isAdmin()) {
            $_SESSION['error'] = "You must be an administrator to access this page.";
            header("Location: log-in.php");
            exit;
        }
    }
    requireAdmin();
    ?>
    <div class="dashboard-header navbar navbar-expand-md py-4">
            <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-boxes"></i>  Jannie Dashboard</h1>
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <li class="nav-item"><a href="admin.php" class="nav-link fs-4 px-3">Product List</a></li>
                        <li class="nav-item"><a href="adminOrder.php" class="nav-link fs-4 px-3">Order List</a></li>
                        <li class="nav-item"><a href="adminPayment.php" class="nav-link fs-4 px-3">Payment List</a></li>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>
        <div id="orderManagementTab" class="active">
            <?php if ($selectedOrder): ?>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-primary back-to-list">
                    <i class="fas fa-arrow-left"></i> Back to Order List
                </a>
                
                <div class="order-details">
                    <h3>Order #<?php echo $selectedOrder['orderID']; ?> Details</h3>
                    
                    <div class="customer-info">
                        <div>
                            <strong>Customer:</strong><br>
                            <?php echo htmlspecialchars($selectedOrder['fname'] . ' ' . $selectedOrder['lname']); ?>
                        </div>
                        <div>
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($selectedOrder['email']); ?>
                        </div>
                        <div>
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($selectedOrder['phone'] ?? 'N/A'); ?>
                        </div>
                        <div>
                            <strong>Order Date:</strong><br>
                            <?php echo date('M d, Y h:i A', strtotime($selectedOrder['orderDate'])); ?>
                        </div>
                        <div>
                            <strong>Total Amount:</strong><br>
                            ₱<?php echo number_format($selectedOrder['totalAmount'], 2); ?>
                        </div>
                        <div>
                            <strong>Status:</strong><br>
                            <span class="order-status status-<?php echo str_replace(' ', '-', $selectedOrder['orderStatus']); ?>">
                                <?php echo ucwords($selectedOrder['orderStatus']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <h4>Order Items</h4>
        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orderDetails && $orderDetails->num_rows > 0): ?>
                    <?php while ($item = $orderDetails->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['prodName']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No items found for this order.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="order-status-form">
            <h4>Update Order Status</h4>
            <form method="POST" action="">
                <input type="hidden" name="orderID" value="<?php echo $selectedOrder['orderID']; ?>">
                
                <div class="form-group">
                    <label for="orderStatus" class="form-label">Status:</label>
                    <select id="orderStatus" name="orderStatus" class="form-control" required>
                        <option value="to be delivered" <?php echo ($selectedOrder['orderStatus'] == 'to be delivered') ? 'selected' : ''; ?>>
                            To Be Delivered
                        </option>
                        <option value="delivered" <?php echo ($selectedOrder['orderStatus'] == 'delivered') ? 'selected' : ''; ?>>
                            Delivered
                        </option>
                        <option value="failed" <?php echo ($selectedOrder['orderStatus'] == 'failed') ? 'selected' : ''; ?>>
                            Failed
                        </option>
                    </select>
                </div>
                
                <button type="submit" name="update_order_status" class="btn btn-success">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="search-container">
        <form method="POST" action="" class="search-form">
            <input type="text" name="orderSearchTerm" placeholder="Search orders by ID, customer name, or email..." 
                   value="<?php echo htmlspecialchars($_GET['orderSearchTerm'] ?? $_POST['orderSearchTerm'] ?? ''); ?>">
            <button type="submit" name="searchOrder" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($_GET['orderSearchTerm'] ?? $_POST['orderSearchTerm'] ?? '')): ?>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-danger">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
        <h2><i class="fas fa-shopping-cart"></i> Orders
        <?php 
            $orderSearchDisplay = $_GET['orderSearchTerm'] ?? $_POST['orderSearchTerm'] ?? '';
            if (!empty($orderSearchDisplay)) {
                echo "- Search results for: \"".htmlspecialchars($orderSearchDisplay)."\"";
            }
        ?>
        </h2>
        
        <?php if ($orderResult && $orderResult->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('orderID', $orderSortColumn, $orderSortOrder); ?>'">
                            Order ID <?php echo getOrderSortIcon('orderID', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('orderID', $orderSortColumn, $orderSortOrder); ?>'">
                            Username <?php echo getOrderSortIcon('username', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('fname', $orderSortColumn, $orderSortOrder); ?>'">
                            Customer <?php echo getOrderSortIcon('fname', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('email', $orderSortColumn, $orderSortOrder); ?>'">
                            Email <?php echo getOrderSortIcon('email', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('orderDate', $orderSortColumn, $orderSortOrder); ?>'">
                            Date <?php echo getOrderSortIcon('orderDate', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('totalAmount', $orderSortColumn, $orderSortOrder); ?>'">
                            Total <?php echo getOrderSortIcon('totalAmount', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th class="sortable" onclick="window.location='<?php echo getOrderSortUrl('orderStatus', $orderSortColumn, $orderSortOrder); ?>'">
                            Status <?php echo getOrderSortIcon('orderStatus', $orderSortColumn, $orderSortOrder); ?>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orderResult->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['orderID']; ?></td>
                            <td><?= htmlspecialchars($order['username'] ?? 'N/A') ?></td>
                            <td><?php echo htmlspecialchars($order['fname'] . ' ' . $order['lname']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['orderDate'])); ?></td>
                            <td>₱<?php echo number_format($order['totalAmount'], 2); ?></td>
                            <td>
                                <span class="order-status status-<?php echo str_replace(' ', '-', $order['orderStatus']); ?>">
                                    <?php echo ucwords($order['orderStatus']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?viewOrder=' . $order['orderID']; ?>" 
                                   class="view-details-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($orderTotalPages > 1): ?>
                <ul class="pagination">
                    <?php if ($orderPage > 1): ?>
                        <li><a href="?orderPage=1<?php echo (!empty($orderSearchDisplay)) ? '&searchOrder=1&orderSearchTerm='.urlencode($orderSearchDisplay) : ''; ?><?php echo isset($_GET['orderSort']) ? '&orderSort='.$orderSortColumn.'&orderOrder='.$orderSortOrder : ''; ?>">&laquo; First</a></li>
                        <li><a href="?orderPage=<?php echo $orderPage-1; ?><?php echo (!empty($orderSearchDisplay)) ? '&searchOrder=1&orderSearchTerm='.urlencode($orderSearchDisplay) : ''; ?><?php echo isset($_GET['orderSort']) ? '&orderSort='.$orderSortColumn.'&orderOrder='.$orderSortOrder : ''; ?>">&lsaquo; Prev</a></li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $orderPage - 2);
                    $endPage = min($orderTotalPages, $orderPage + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li>
                            <a href="?orderPage=<?php echo $i; ?><?php echo (!empty($orderSearchDisplay)) ? '&searchOrder=1&orderSearchTerm='.urlencode($orderSearchDisplay) : ''; ?><?php echo isset($_GET['orderSort']) ? '&orderSort='.$orderSortColumn.'&orderOrder='.$orderSortOrder : ''; ?>" 
                               <?php echo ($i == $orderPage) ? 'class="active"' : ''; ?>>
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($orderPage < $orderTotalPages): ?>
                        <li><a href="?orderPage=<?php echo $orderPage+1; ?><?php echo (!empty($orderSearchDisplay)) ? '&searchOrder=1&orderSearchTerm='.urlencode($orderSearchDisplay) : ''; ?><?php echo isset($_GET['orderSort']) ? '&orderSort='.$orderSortColumn.'&orderOrder='.$orderSortOrder : ''; ?>">Next &rsaquo;</a></li>
                        <li><a href="?orderPage=<?php echo $orderTotalPages; ?><?php echo (!empty($orderSearchDisplay)) ? '&searchOrder=1&orderSearchTerm='.urlencode($orderSearchDisplay) : ''; ?><?php echo isset($_GET['orderSort']) ? '&orderSort='.$orderSortColumn.'&orderOrder='.$orderSortOrder : ''; ?>">Last &raquo;</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> No orders found.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
        </div>
</body>
</html>
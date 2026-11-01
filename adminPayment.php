<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="adminPayment.css">
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
    <div class="payment-pane">
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
    <div id="paymentManagementTab" class="active">
        <?php if ($selectedPayment): ?>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-primary back-to-list">
                <i class="fas fa-arrow-left"></i> Back to Payment List
            </a>
            
            <div class="payment-details">
                <h3>Payment #<?php echo $selectedPayment['paymentID']; ?> Details</h3>
                
                <div class="payment-info">
                    <div>
                        <strong>Order ID:</strong><br>
                        #<?php echo $selectedPayment['orderID']; ?>
                    </div>
                    <div>
                        <strong>Customer:</strong><br>
                        <?php echo htmlspecialchars($selectedPayment['fname'] . ' ' . $selectedPayment['lname']); ?>
                    </div>
                    <div>
                        <strong>Username:</strong><br>
                        <?php echo htmlspecialchars($selectedPayment['username'] ?? 'N/A'); ?>
                    </div>
                    <div>
                        <strong>Payment Type:</strong><br>
                        <?php echo htmlspecialchars($selectedPayment['paymentType']); ?>
                    </div>
                    <div>
                        <strong>Billing Address:</strong><br>
                        <?php echo htmlspecialchars($selectedPayment['billAddress']); ?>
                    </div>
                    <div>
                        <strong>Amount:</strong><br>
                        ₱<?php echo number_format($selectedPayment['amount'], 2); ?>
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span class="payment-status status-<?php echo $selectedPayment['paymentStatus']; ?>">
                            <?php echo ucwords($selectedPayment['paymentStatus']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="payment-status-form">
                    <h4>Update Payment Status</h4>
                    <form method="POST" action="">
                        <input type="hidden" name="paymentID" value="<?php echo $selectedPayment['paymentID']; ?>">
                        
                        <div class="form-group">
                            <label for="paymentStatus" class="form-label">Status:</label>
                            <select id="paymentStatus" name="paymentStatus" class="form-control" required>
                                <option value="pending" <?php echo ($selectedPayment['paymentStatus'] == 'pending') ? 'selected' : ''; ?>>
                                    Pending
                                </option>
                                <option value="completed" <?php echo ($selectedPayment['paymentStatus'] == 'completed') ? 'selected' : ''; ?>>
                                    Completed
                                </option>
                                <option value="failed" <?php echo ($selectedPayment['paymentStatus'] == 'failed') ? 'selected' : ''; ?>>
                                    Failed
                                </option>
                            </select>
                        </div>
                        
                        <button type="submit" name="update_payment_status" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="search-container">
                <form method="POST" action="" class="search-form">
                    <input type="text" name="paymentSearchTerm" placeholder="Search payments by ID, customer name, or payment type..." 
                           value="<?php echo htmlspecialchars($_GET['paymentSearchTerm'] ?? $_POST['paymentSearchTerm'] ?? ''); ?>">
                    <button type="submit" name="searchPayment" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($_GET['paymentSearchTerm'] ?? $_POST['paymentSearchTerm'] ?? '')): ?>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-danger">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <h2><i class="fas fa-credit-card"></i> Payments
                <?php 
                    $paymentSearchDisplay = $_GET['paymentSearchTerm'] ?? $_POST['paymentSearchTerm'] ?? '';
                    if (!empty($paymentSearchDisplay)) {
                        echo "- Search results for: \"".htmlspecialchars($paymentSearchDisplay)."\"";
                    }
                ?>
                </h2>
                
                <?php if ($paymentResult && $paymentResult->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('paymentID', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Payment ID <?php echo getPaymentSortIcon('paymentID', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('orderID', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Order ID <?php echo getPaymentSortIcon('orderID', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('paymentType', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Payment Type <?php echo getPaymentSortIcon('paymentType', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('billAddress', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Billing Address <?php echo getPaymentSortIcon('billAddress', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('username', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Username <?php echo getPaymentSortIcon('username', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('fname', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Customer <?php echo getPaymentSortIcon('fname', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('amount', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Amount <?php echo getPaymentSortIcon('amount', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getPaymentSortUrl('paymentStatus', $paymentSortColumn, $paymentSortOrder); ?>'">
                                    Status <?php echo getPaymentSortIcon('paymentStatus', $paymentSortColumn, $paymentSortOrder); ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $paymentResult->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $payment['paymentID']; ?></td>
                                    <td>#<?php echo $payment['orderID']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['paymentType']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($payment['billAddress'], 0, 30)) . (strlen($payment['billAddress']) > 30 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($payment['username'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['fname'] . ' ' . $payment['lname']); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td>
                                        <span class="payment-status status-<?php echo $payment['paymentStatus']; ?>">
                                            <?php echo ucwords($payment['paymentStatus']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?viewPayment=' . $payment['paymentID']; ?>" 
                                           class="view-details-btn">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($paymentTotalPages > 1): ?>
                        <ul class="pagination">
                            <?php if ($paymentPage > 1): ?>
                                <li><a href="?paymentPage=1<?php echo (!empty($paymentSearchDisplay)) ? '&searchPayment=1&paymentSearchTerm='.urlencode($paymentSearchDisplay) : ''; ?><?php echo isset($_GET['paymentSort']) ? '&paymentSort='.$paymentSortColumn.'&paymentOrder='.$paymentSortOrder : ''; ?>">&laquo; First</a></li>
                                <li><a href="?paymentPage=<?php echo $paymentPage-1; ?><?php echo (!empty($paymentSearchDisplay)) ? '&searchPayment=1&paymentSearchTerm='.urlencode($paymentSearchDisplay) : ''; ?><?php echo isset($_GET['paymentSort']) ? '&paymentSort='.$paymentSortColumn.'&paymentOrder='.$paymentSortOrder : ''; ?>">&lsaquo; Prev</a></li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $paymentPage - 2);
                            $endPage = min($paymentTotalPages, $paymentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li>
                                    <a href="?paymentPage=<?php echo $i; ?><?php echo (!empty($paymentSearchDisplay)) ? '&searchPayment=1&paymentSearchTerm='.urlencode($paymentSearchDisplay) : ''; ?><?php echo isset($_GET['paymentSort']) ? '&paymentSort='.$paymentSortColumn.'&paymentOrder='.$paymentSortOrder : ''; ?>" 
                                       <?php echo ($i == $paymentPage) ? 'class="active"' : ''; ?>>
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($paymentPage < $paymentTotalPages): ?>
                                <li><a href="?paymentPage=<?php echo $paymentPage+1; ?><?php echo (!empty($paymentSearchDisplay)) ? '&searchPayment=1&paymentSearchTerm='.urlencode($paymentSearchDisplay) : ''; ?><?php echo isset($_GET['paymentSort']) ? '&paymentSort='.$paymentSortColumn.'&paymentOrder='.$paymentSortOrder : ''; ?>">Next &rsaquo;</a></li>
                                <li><a href="?paymentPage=<?php echo $paymentTotalPages; ?><?php echo (!empty($paymentSearchDisplay)) ? '&searchPayment=1&paymentSearchTerm='.urlencode($paymentSearchDisplay) : ''; ?><?php echo isset($_GET['paymentSort']) ? '&paymentSort='.$paymentSortColumn.'&paymentOrder='.$paymentSortOrder : ''; ?>">Last &raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> No payments found.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="sales-pane">
    <h2><i class="fas fa-chart-line"></i> Sales Overview</h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Total Sales</h3>
                </div>
                <div class="stat-card-body">
                    <?php
                    // Get total completed sales
                    $totalSalesQuery = "SELECT SUM(amount) AS total FROM payment WHERE paymentStatus = 'completed'";
                    $totalSalesResult = $conn->query($totalSalesQuery);
                    $totalSales = $totalSalesResult->fetch_assoc()['total'] ?? 0;
                    ?>
                    <span class="stat-value">₱<?php echo number_format($totalSales, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card-header">
                    <i class="fas fa-calendar-check"></i>
                    <h3>This Month</h3>
                </div>
                <div class="stat-card-body">
                    <?php
                    // Get current month sales based on order date since payment doesn't have date field
                    $currentMonth = date('Y-m');
                    $monthlySalesQuery = "SELECT SUM(p.amount) AS total 
                                         FROM payment p
                                         JOIN orders o ON p.orderID = o.orderID
                                         WHERE p.paymentStatus = 'completed' 
                                         AND DATE_FORMAT(o.orderDate, '%Y-%m') = '$currentMonth'";
                    $monthlySalesResult = $conn->query($monthlySalesQuery);
                    $monthlySales = $monthlySalesResult->fetch_assoc()['total'] ?? 0;
                    ?>
                    <span class="stat-value">₱<?php echo number_format($monthlySales, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-card-header">
                    <i class="fas fa-credit-card"></i>
                    <h3>Pending Payments</h3>
                </div>
                <div class="stat-card-body">
                    <?php
                    // Get total pending payments
                    $pendingQuery = "SELECT COUNT(*) AS count, SUM(amount) AS total FROM payment WHERE paymentStatus = 'pending'";
                    $pendingResult = $conn->query($pendingQuery);
                    $pending = $pendingResult->fetch_assoc();
                    $pendingCount = $pending['count'] ?? 0;
                    $pendingTotal = $pending['total'] ?? 0;
                    ?>
                    <span class="stat-value"><?php echo $pendingCount; ?> (₱<?php echo number_format($pendingTotal, 2); ?>)</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Sales Chart -->
    <div class="monthly-sales">
        <h3><i class="fas fa-calendar-alt"></i> Monthly Sales</h3>
        
        <table class="data-table monthly-sales-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Sales</th>
                    <th>Transactions</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get monthly sales for the past 6 months using order date
                $monthlySalesQuery = "SELECT 
                    DATE_FORMAT(o.orderDate, '%Y-%m') AS month,
                    DATE_FORMAT(o.orderDate, '%M %Y') AS month_name,
                    COUNT(p.paymentID) AS transactions,
                    SUM(p.amount) AS total
                    FROM payment p
                    JOIN orders o ON p.orderID = o.orderID 
                    WHERE p.paymentStatus = 'completed'
                    GROUP BY DATE_FORMAT(o.orderDate, '%Y-%m')
                    ORDER BY month DESC
                    LIMIT 6";
                
                $monthlySalesResult = $conn->query($monthlySalesQuery);
                
                $months = [];
                $monthlyTotals = [];
                
                if ($monthlySalesResult && $monthlySalesResult->num_rows > 0) {
                    $prevTotal = null;
                    
                    while ($monthData = $monthlySalesResult->fetch_assoc()) {
                        $months[] = $monthData;
                        $monthlyTotals[] = $monthData['total'];
                        
                        $performance = '';
                        $performanceClass = '';
                        
                        if ($prevTotal !== null) {
                            $change = $monthData['total'] - $prevTotal;
                            $percentChange = ($prevTotal > 0) ? ($change / $prevTotal) * 100 : 0;
                            
                            if ($percentChange > 0) {
                                $performance = '<i class="fas fa-arrow-up text-success"></i> ' . number_format(abs($percentChange), 1) . '%';
                                $performanceClass = 'text-success';
                            } elseif ($percentChange < 0) {
                                $performance = '<i class="fas fa-arrow-down text-danger"></i> ' . number_format(abs($percentChange), 1) . '%';
                                $performanceClass = 'text-danger';
                            } else {
                                $performance = '<i class="fas fa-minus"></i> 0%';
                                $performanceClass = 'text-secondary';
                            }
                        }
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($monthData['month_name']) . '</td>';
                        echo '<td>₱' . number_format($monthData['total'], 2) . '</td>';
                        echo '<td>' . $monthData['transactions'] . '</td>';
                        echo '<td class="' . $performanceClass . '">' . $performance . '</td>';
                        echo '</tr>';
                        
                        $prevTotal = $monthData['total'];
                    }
                } else {
                    echo '<tr><td colspan="4" class="text-center">No sales data available</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="row">
        <!-- Payment Method Distribution -->
        <div class="col-md-6">
            <div class="payment-methods">
                <h3><i class="fas fa-wallet"></i> Payment Methods</h3>
                
                <div class="payment-methods-chart">
                    <?php
                    // Get payment method distribution
                    $paymentMethodQuery = "SELECT 
                        paymentType,
                        COUNT(*) AS count,
                        SUM(amount) AS total,
                        (COUNT(*) / (SELECT COUNT(*) FROM payment WHERE paymentStatus = 'completed')) * 100 AS percentage
                        FROM payment 
                        WHERE paymentStatus = 'completed'
                        GROUP BY paymentType
                        ORDER BY count DESC";
                    
                    $paymentMethodResult = $conn->query($paymentMethodQuery);
                    
                   if ($paymentMethodResult && $paymentMethodResult->num_rows > 0) {
                        while ($method = $paymentMethodResult->fetch_assoc()) {
                            $percentage = number_format($method['percentage'], 1);
                            $barWidth = max(5, $percentage); // minimum width of 5% for visibility
                            
                            echo '<div class="payment-method-item mb-3">';
                            echo '  <div class="d-flex justify-content-between align-items-center mb-1">';
                            echo '    <span class="fw-bold">' . htmlspecialchars($method['paymentType']) . '</span>';
                            echo '    <span class="text-muted small ms-2">' . $method['count'] . ' transactions · ₱' . number_format($method['total'], 2) . '</span>';
                            echo '  </div>';
                            echo '  <div class="progress" style="height: 20px;">';
                            echo '    <div class="progress-bar" role="progressbar" style="width: ' . $barWidth . '%;" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100"></div>';
                            echo '  </div>';
                            echo '  <div class="text-end mt-1">';
                            echo '    <small class="text-muted">' . $percentage . '% of total</small>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">No payment method data available</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="col-md-6">
            <div class="top-products">
                <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
                
                <table class="data-table top-products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get top selling products
                        $topProductsQuery = "SELECT 
                            p.productID,
                            p.prodName,
                            c.ctgName,
                            SUM(od.quantity) AS units_sold,
                            SUM(od.quantity * od.price) AS revenue
                            FROM product p
                            JOIN category c ON p.ctgID = c.ctgID
                            JOIN order_details od ON p.productID = od.productID
                            JOIN orders o ON od.orderID = o.orderID
                            JOIN payment pm ON o.orderID = pm.orderID
                            WHERE pm.paymentStatus = 'completed'
                            GROUP BY p.productID
                            ORDER BY units_sold DESC
                            LIMIT 5";
                        
                        $topProductsResult = $conn->query($topProductsQuery);
                        
                        if ($topProductsResult && $topProductsResult->num_rows > 0) {
                            while ($product = $topProductsResult->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($product['prodName']) . '</td>';
                                echo '<td>' . htmlspecialchars($product['ctgName']) . '</td>';
                                echo '<td>' . $product['units_sold'] . '</td>';
                                echo '<td>₱' . number_format($product['revenue'], 2) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center">No product data available</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
    </body>
    </html>
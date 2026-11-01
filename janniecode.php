<?php
$errorMsg = $successMsg = '';
$searchTerm = '';
$filters = [];

// Product sorting variables
$sortColumn = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'prodName';
$sortOrder = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'ASC';

$allowedColumns = ['prodName', 'prodDesc', 'prodPrice', 'prodInv', 'brandName', 'address', 'ctgName'];
if (!in_array($sortColumn, $allowedColumns)) {
    $sortColumn = 'prodName';
}

function sanitizeInput($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Create Product
if (isset($_POST['create'])) {
    // Sanitize inputs
    $prodName = sanitizeInput($_POST['prodName']);
    $prodDesc = sanitizeInput($_POST['prodDesc']);
    $prodPrice = filter_var($_POST['prodPrice'], FILTER_VALIDATE_FLOAT);
    $prodInv = filter_var($_POST['prodInv'], FILTER_VALIDATE_INT);
    $brandID = filter_var($_POST['brandID'], FILTER_VALIDATE_INT);
    $shipped_from = filter_var($_POST['shipped_from'], FILTER_VALIDATE_INT);
    $ctgID = filter_var($_POST['ctgID'], FILTER_VALIDATE_INT);

    // Validate inputs
    if (!$prodName || !$prodDesc || $prodPrice === false || $prodInv === false || 
        $brandID === false || $shipped_from === false || $ctgID === false) {
        $errorMsg = "Invalid input data. Please check your values.";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO product (prodName, prodDesc, prodPrice, prodInv, brandID, shipped_from, ctgID) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiiii", $prodName, $prodDesc, $prodPrice, $prodInv, $brandID, $shipped_from, $ctgID);
        
        if ($stmt->execute()) {
            $successMsg = "New product created successfully!";
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Update Product
if (isset($_POST['update'])) {
    // Sanitize inputs
    $productID = filter_var($_POST['productID'], FILTER_VALIDATE_INT);
    $prodName = sanitizeInput($_POST['prodName']);
    $prodDesc = sanitizeInput($_POST['prodDesc']);
    $prodPrice = filter_var($_POST['prodPrice'], FILTER_VALIDATE_FLOAT);
    $prodInv = filter_var($_POST['prodInv'], FILTER_VALIDATE_INT);
    $brandID = filter_var($_POST['brandID'], FILTER_VALIDATE_INT);
    $shipped_from = filter_var($_POST['shipped_from'], FILTER_VALIDATE_INT);
    $ctgID = filter_var($_POST['ctgID'], FILTER_VALIDATE_INT);

    // Validate inputs
    if ($productID === false || !$prodName || !$prodDesc || $prodPrice === false || $prodInv === false || 
        $brandID === false || $shipped_from === false || $ctgID === false) {
        $errorMsg = "Invalid input data. Please check your values.";
    } else {
        // Use prepared statement
        $stmt = $conn->prepare("UPDATE product SET prodName=?, prodDesc=?, prodPrice=?, prodInv=?, brandID=?, shipped_from=?, ctgID=? WHERE productID=?");
        $stmt->bind_param("ssdiiiii", $prodName, $prodDesc, $prodPrice, $prodInv, $brandID, $shipped_from, $ctgID, $productID);
        
        if ($stmt->execute()) {
            $successMsg = "Product updated successfully!";
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete Product
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $productID = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($productID !== false) {
        $stmt = $conn->prepare("DELETE FROM product WHERE productID=?");
        $stmt->bind_param("i", $productID);
        
        if ($stmt->execute()) {
            $successMsg = "Product deleted successfully!";
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errorMsg = "Invalid product ID";
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// PRODUCT SEARCH FUNCTIONALITY
$searchQuery = "";
$whereConditions = [];
$queryParams = [];
$paramTypes = "";

if (isset($_POST['search']) || isset($_GET['search'])) {
    // Get search term from either POST or GET
    $searchTerm = isset($_POST['searchTerm']) ? sanitizeInput($_POST['searchTerm']) : 
                 (isset($_GET['searchTerm']) ? sanitizeInput($_GET['searchTerm']) : '');
    
    if (!empty($searchTerm)) {
        $quotePattern = '/(ctg|prodname|price|brand|inv|warehouse):"([^"]+)"/i';
        preg_match_all($quotePattern, $searchTerm, $quoteMatches, PREG_SET_ORDER);
        
        foreach ($quoteMatches as $match) {
            $filterType = strtolower($match[1]);
            $filterValue = $match[2]; 
            $filters[$filterType] = $filterValue;
            
            $searchTerm = str_replace($match[0], '', $searchTerm);
        }
        
        $pattern = '/(ctg|prodname|price|brand|inv|warehouse):([^\s"]+)/i';
        preg_match_all($pattern, $searchTerm, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $filterType = strtolower($match[1]);
            $filterValue = $match[2];
            $filters[$filterType] = $filterValue;
            
            $searchTerm = str_replace($match[0], '', $searchTerm);
        }
        
        $searchTerm = trim($searchTerm);

        if (isset($filters['ctg'])) {
            $whereConditions[] = "c.ctgName LIKE ?";
            $queryParams[] = "%" . $filters['ctg'] . "%";
            $paramTypes .= "s";
        }
        if (isset($filters['prodname'])) {
            $whereConditions[] = "p.prodName LIKE ?";
            $queryParams[] = "%" . $filters['prodname'] . "%";
            $paramTypes .= "s";
        }
        
        if (isset($filters['price'])) {
            if (strpos($filters['price'], '-') !== false) {
                list($minPrice, $maxPrice) = explode('-', $filters['price']);
                if (is_numeric($minPrice) && is_numeric($maxPrice)) {
                    $whereConditions[] = "p.prodPrice BETWEEN ? AND ?";
                    $queryParams[] = (float)$minPrice;
                    $queryParams[] = (float)$maxPrice;
                    $paramTypes .= "dd";
                }
            } else if (is_numeric($filters['price'])) {
                $whereConditions[] = "p.prodPrice = ?";
                $queryParams[] = (float)$filters['price'];
                $paramTypes .= "d";
            }
        }
        
        if (isset($filters['brand'])) {
            $whereConditions[] = "b.brandName LIKE ?";
            $queryParams[] = "%" . $filters['brand'] . "%";
            $paramTypes .= "s";
        }
        
        if (isset($filters['inv'])) {
            if (is_numeric($filters['inv'])) {
                $whereConditions[] = "p.prodInv >= ?";
                $queryParams[] = (int)$filters['inv'];
                $paramTypes .= "i";
            }
        }
        
        if (isset($filters['warehouse'])) {
            $whereConditions[] = "w.address LIKE ?";
            $queryParams[] = "%" . $filters['warehouse'] . "%";
            $paramTypes .= "s";
        }
        
        if (!empty($searchTerm)) {
            $whereConditions[] = "(p.prodName LIKE ? OR p.prodDesc LIKE ?)";
            $queryParams[] = "%" . $searchTerm . "%";
            $queryParams[] = "%" . $searchTerm . "%";
            $paramTypes .= "ss";
        }
    }
}

if (!empty($whereConditions)) {
    $searchQuery = "WHERE " . implode(" AND ", $whereConditions);
}

// Fetch lookup data for dropdowns
$brandsQuery = "SELECT brandID, brandName FROM brand ORDER BY brandName";
$brandsResult = $conn->query($brandsQuery);
$brands = [];
if ($brandsResult) {
    while ($row = $brandsResult->fetch_assoc()) {
        $brands[$row['brandID']] = $row['brandName'];
    }
}

$warehouseQuery = "SELECT warehouseID, address FROM warehouse ORDER BY address";
$warehouseResult = $conn->query($warehouseQuery);
$warehouses = [];
if ($warehouseResult) {
    while ($row = $warehouseResult->fetch_assoc()) {
        $warehouses[$row['warehouseID']] = $row['address'];
    }
}

$categoryQuery = "SELECT ctgID, ctgName FROM category ORDER BY ctgName";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[$row['ctgID']] = $row['ctgName'];
    }
}

// PRODUCT PAGINATION
$itemsPerPage = 10;
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
if ($page === false || $page < 1) $page = 1;
$offset = ($page - 1) * $itemsPerPage;

// Count total products for pagination
if (!empty($searchQuery)) {
    $countSql = "SELECT COUNT(*) as total 
                FROM product p 
                LEFT JOIN brand b ON p.brandID = b.brandID 
                LEFT JOIN category c ON p.ctgID = c.ctgID 
                LEFT JOIN warehouse w ON p.shipped_from = w.warehouseID 
                $searchQuery";
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($paramTypes)) {
        $countStmt->bind_param($paramTypes, ...$queryParams);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalRows = $totalResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query("SELECT COUNT(*) as total FROM product");
    $totalRows = $countResult->fetch_assoc()['total'];
}

// Main product query
$sql = "SELECT p.*, b.brandName, c.ctgName, w.address 
        FROM product p 
        LEFT JOIN brand b ON p.brandID = b.brandID 
        LEFT JOIN category c ON p.ctgID = c.ctgID 
        LEFT JOIN warehouse w ON p.shipped_from = w.warehouseID 
        $searchQuery 
        ORDER BY $sortColumn $sortOrder 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Add pagination params to the query params array
$productQueryParams = $queryParams; // Create a copy to avoid issues
$productQueryParams[] = $itemsPerPage;
$productQueryParams[] = $offset;
$productParamTypes = $paramTypes . "ii";

if (!empty($productParamTypes)) {
    $stmt->bind_param($productParamTypes, ...$productQueryParams);
}

$stmt->execute();
$result = $stmt->get_result(); // This is your product result
$totalPages = ceil($totalRows / $itemsPerPage);

// Get product for editing if requested
$editProduct = null;
if (isset($_GET['edit'])) {
    $productID = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($productID !== false) {
        $editStmt = $conn->prepare("SELECT * FROM product WHERE productID = ?");
        $editStmt->bind_param("i", $productID);
        $editStmt->execute();
        $editResult = $editStmt->get_result();
        $editProduct = $editResult->fetch_assoc();
        $editStmt->close();
    }
}

// Helper functions for product list
function getSortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $baseUrl = htmlspecialchars($_SERVER['PHP_SELF']) . "?sort=" . $column . "&order=" . $newOrder;
    
    global $searchTerm, $filters;
    if (!empty($searchTerm) || !empty($filters)) {
        $baseUrl .= "&search=1&searchTerm=" . urlencode($_GET['searchTerm'] ?? $_POST['searchTerm'] ?? '');
    }
    
    if (isset($_GET['page'])) {
        $baseUrl .= "&page=" . $_GET['page'];
    }
    
    return $baseUrl;
}

function getSortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($currentOrder === 'ASC') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

// ORDER MANAGEMENT SECTION
if (isset($_POST['update_order_status'])) {
    $orderID = filter_var($_POST['orderID'], FILTER_VALIDATE_INT);
    $newStatus = sanitizeInput($_POST['orderStatus']);
    
    if ($orderID === false) {
        $errorMsg = "Invalid order ID.";
    } else if (!in_array($newStatus, ['to be delivered', 'delivered', 'failed'])) {
        $errorMsg = "Invalid order status.";
    } else {
        $stmt = $conn->prepare("UPDATE orders SET orderStatus=? WHERE orderID=?");
        $stmt->bind_param("si", $newStatus, $orderID);
        
        if ($stmt->execute()) {
            $successMsg = "Order status updated successfully!";
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

function getOrders($conn, $searchTerm = '', $sortColumn = 'orderDate', $sortOrder = 'DESC', $page = 1, $itemsPerPage = 10) {
    $whereConditions = [];
    $queryParams = [];
    $paramTypes = "";
    $searchQuery = "";
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(o.orderID LIKE ? OR c.fname LIKE ? OR c.lname LIKE ? OR c.email LIKE ? OR c.username LIKE ?)";
        $searchTermParam = "%" . $searchTerm . "%";
        $queryParams[] = $searchTermParam;
        $queryParams[] = $searchTermParam;
        $queryParams[] = $searchTermParam;
        $queryParams[] = $searchTermParam;
        $queryParams[] = $searchTermParam;
        $paramTypes .= "sssss";
    }
    
    if (!empty($whereConditions)) {
        $searchQuery = "WHERE " . implode(" AND ", $whereConditions);
    }
    
    $allowedColumns = ['orderID', 'orderDate', 'orderStatus', 'totalAmount', 'fname', 'lname', 'email','username'];
    if (!in_array($sortColumn, $allowedColumns)) {
        $sortColumn = 'orderDate';
    }
    
    $offset = ($page - 1) * $itemsPerPage;
    
    if (!empty($searchQuery)) {
        $countSql = "SELECT COUNT(*) as total 
                    FROM orders o 
                    LEFT JOIN customer c ON o.customerID = c.customerID 
                    $searchQuery";
        
        $countStmt = $conn->prepare($countSql);
        if (!empty($paramTypes)) {
            $countStmt->bind_param($paramTypes, ...$queryParams);
        }
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $totalRows = $totalResult->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $countResult = $conn->query("SELECT COUNT(*) as total FROM orders");
        $totalRows = $countResult->fetch_assoc()['total'];
    }
    
    $sql = "SELECT o.*, c.fname, c.lname, c.email,c.username
            FROM orders o 
            LEFT JOIN customer c ON o.customerID = c.customerID 
            $searchQuery 
            ORDER BY $sortColumn $sortOrder 
            LIMIT ? OFFSET ?";
    
    $queryParams[] = $itemsPerPage;
    $queryParams[] = $offset;
    $paramTypes .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($paramTypes)) {
        $stmt->bind_param($paramTypes, ...$queryParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $totalPages = ceil($totalRows / $itemsPerPage);
    
    return [
        'result' => $result,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages
    ];
}

function getOrderDetails($conn, $orderID) {
    $stmt = $conn->prepare("
        SELECT od.*, p.prodName 
        FROM order_details od
        JOIN product p ON od.productID = p.productID
        WHERE od.orderID = ?
    ");
    $stmt->bind_param("i", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result;
}

// Order search and pagination
$orderSearchTerm = '';
$orderSortColumn = isset($_GET['orderSort']) ? sanitizeInput($_GET['orderSort']) : 'orderDate';
$orderSortOrder = isset($_GET['orderOrder']) ? sanitizeInput($_GET['orderOrder']) : 'DESC';
$orderPage = isset($_GET['orderPage']) ? filter_var($_GET['orderPage'], FILTER_VALIDATE_INT) : 1;
if ($orderPage === false || $orderPage < 1) $orderPage = 1;

// Handle order search
if (isset($_POST['searchOrder']) || isset($_GET['searchOrder'])) {
    $orderSearchTerm = isset($_POST['orderSearchTerm']) ? sanitizeInput($_POST['orderSearchTerm']) : 
                     (isset($_GET['orderSearchTerm']) ? sanitizeInput($_GET['orderSearchTerm']) : '');
}

$orderData = getOrders($conn, $orderSearchTerm, $orderSortColumn, $orderSortOrder, $orderPage, 10);
$orderResult = $orderData['result'];
$orderTotalRows = $orderData['totalRows'];
$orderTotalPages = $orderData['totalPages'];

$selectedOrder = null;
$orderDetails = null;
if (isset($_GET['viewOrder'])) {
    $selectedOrderID = filter_var($_GET['viewOrder'], FILTER_VALIDATE_INT);
    if ($selectedOrderID !== false) {
        $detailsStmt = $conn->prepare("
            SELECT o.*, c.fname, c.lname, c.email, c.phone
            FROM orders o
            LEFT JOIN customer c ON o.customerID = c.customerID
            WHERE o.orderID = ?
        ");
        $detailsStmt->bind_param("i", $selectedOrderID);
        $detailsStmt->execute();
        $detailsResult = $detailsStmt->get_result();
        $selectedOrder = $detailsResult->fetch_assoc();
        $detailsStmt->close();
        
        if ($selectedOrder) {
            $orderDetails = getOrderDetails($conn, $selectedOrderID);
        }
    }
}

function getOrderSortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $column && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $baseUrl = htmlspecialchars($_SERVER['PHP_SELF']) . "?orderSort=" . $column . "&orderOrder=" . $newOrder;
    
    global $orderSearchTerm;
    if (!empty($orderSearchTerm)) {
        $baseUrl .= "&searchOrder=1&orderSearchTerm=" . urlencode($orderSearchTerm);
    }
    
    if (isset($_GET['orderPage'])) {
        $baseUrl .= "&orderPage=" . $_GET['orderPage'];
    }
    
    return $baseUrl;
}

function getOrderSortIcon($column, $currentSort, $currentOrder) {
    if ($currentSort !== $column) {
        return '<i class="fas fa-sort"></i>';
    }
    return ($currentOrder === 'DESC') ? '<i class="fas fa-sort-down"></i>' : '<i class="fas fa-sort-up"></i>';
}

// PAYMENT MANAGEMENT SECTION - KEEP SEPARATE FROM PRODUCT CODE
$paymentSortColumn = isset($_GET['paymentSort']) ? sanitizeInput($_GET['paymentSort']) : 'paymentID';
$paymentSortOrder = isset($_GET['paymentOrder']) ? sanitizeInput($_GET['paymentOrder']) : 'DESC';
$paymentPage = isset($_GET['paymentPage']) ? (int)$_GET['paymentPage'] : 1;
$paymentPerPage = 10;
$paymentOffset = ($paymentPage - 1) * $paymentPerPage;
$selectedPayment = null;
$paymentSearchTerm = isset($_GET['paymentSearchTerm']) ? sanitizeInput($_GET['paymentSearchTerm']) : 
                    (isset($_POST['paymentSearchTerm']) ? sanitizeInput($_POST['paymentSearchTerm']) : '');

// View Payment Details
if (isset($_GET['viewPayment'])) {
    $paymentID = (int)$_GET['viewPayment'];
    $stmt = $conn->prepare("
        SELECT p.*, o.orderID, c.username, c.fname, c.lname
        FROM payment p
        JOIN orders o ON p.orderID = o.orderID
        JOIN customer c ON o.customerID = c.customerID
        WHERE p.paymentID = ?
    ");
    $stmt->bind_param("i", $paymentID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedPayment = $result->fetch_assoc();
    } else {
        $errorMsg = "Payment not found.";
    }
    $stmt->close();
}

// Update Payment Status
if (isset($_POST['update_payment_status'])) {
    $paymentID = (int)$_POST['paymentID'];
    $status = sanitizeInput($_POST['paymentStatus']);
    
    $stmt = $conn->prepare("UPDATE payment SET paymentStatus = ? WHERE paymentID = ?");
    $stmt->bind_param("si", $status, $paymentID);
    
    if ($stmt->execute()) {
        $successMsg = "Payment status updated successfully.";
        // Refresh payment data
        $stmt = $conn->prepare("
            SELECT p.*, o.orderID, c.username, c.fname, c.lname
            FROM payment p
            JOIN orders o ON p.orderID = o.orderID
            JOIN customer c ON o.customerID = c.customerID
            WHERE p.paymentID = ?
        ");
        $stmt->bind_param("i", $paymentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $selectedPayment = $result->fetch_assoc();
        }
    } else {
        $errorMsg = "Error updating payment status: " . $stmt->error;
    }
    $stmt->close();
}

// Search Payments
$paymentResult = null;
$paymentTotalItems = 0;
$paymentTotalPages = 0;

if (isset($_POST['searchPayment']) || isset($_GET['searchPayment'])) {
    $searchQuery = "
        SELECT p.*, o.orderID, c.username, c.fname, c.lname
        FROM payment p
        JOIN orders o ON p.orderID = o.orderID
        JOIN customer c ON o.customerID = c.customerID
        WHERE p.paymentID LIKE ? OR p.paymentType LIKE ? OR p.billAddress LIKE ? OR 
              c.fname LIKE ? OR c.lname LIKE ? OR CONCAT(c.fname, ' ', c.lname) LIKE ? OR
              c.username LIKE ?
        ORDER BY $paymentSortColumn $paymentSortOrder
        LIMIT ?, ?
    ";
    
    $countQuery = "
        SELECT COUNT(*) as total
        FROM payment p
        JOIN orders o ON p.orderID = o.orderID
        JOIN customer c ON o.customerID = c.customerID
        WHERE p.paymentID LIKE ? OR p.paymentType LIKE ? OR p.billAddress LIKE ? OR 
              c.fname LIKE ? OR c.lname LIKE ? OR CONCAT(c.fname, ' ', c.lname) LIKE ? OR
              c.username LIKE ?
    ";
    
    $searchParam = "%$paymentSearchTerm%";
    
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("sssssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $paymentTotalItems = $row['total'];
    $paymentTotalPages = ceil($paymentTotalItems / $paymentPerPage);
    $stmt->close();
    
    $stmt = $conn->prepare($searchQuery);
    $stmt->bind_param("sssssssii", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $paymentOffset, $paymentPerPage);
    $stmt->execute();
    $paymentResult = $stmt->get_result();
    $stmt->close();
} else {
    // Get All Payments
    $countQuery = "SELECT COUNT(*) as total FROM payment";
    $paymentCountResult = $conn->query($countQuery);
    $row = $paymentCountResult->fetch_assoc();
    $paymentTotalItems = $row['total'];
    $paymentTotalPages = ceil($paymentTotalItems / $paymentPerPage);
    
    $stmt = $conn->prepare("
        SELECT p.*, o.orderID, c.username, c.fname, c.lname
        FROM payment p
        JOIN orders o ON p.orderID = o.orderID
        JOIN customer c ON o.customerID = c.customerID
        ORDER BY $paymentSortColumn $paymentSortOrder
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $paymentOffset, $paymentPerPage);
    $stmt->execute();
    $paymentResult = $stmt->get_result();
    $stmt->close();
}

// Helper Functions
function getPaymentSortUrl($column, $currentSortColumn, $currentSortOrder) {
    $url = htmlspecialchars($_SERVER['PHP_SELF']) . '?paymentSort=' . $column;
    
    if ($currentSortColumn == $column) {
        $newOrder = ($currentSortOrder == 'ASC') ? 'DESC' : 'ASC';
    } else {
        $newOrder = 'ASC';
    }
    
    $url .= '&paymentOrder=' . $newOrder;
    
    if (isset($_GET['paymentPage'])) {
        $url .= '&paymentPage=' . (int)$_GET['paymentPage'];
    }
    
    if (isset($_GET['searchPayment']) && isset($_GET['paymentSearchTerm'])) {
        $url .= '&searchPayment=1&paymentSearchTerm=' . urlencode($_GET['paymentSearchTerm']);
    }
    
    return $url;
}

function getPaymentSortIcon($column, $currentSortColumn, $currentSortOrder) {
    if ($currentSortColumn == $column) {
        return ($currentSortOrder == 'ASC') ? '<i class="fa fa-sort-up"></i>' : '<i class="fa fa-sort-down"></i>';
    }
    return '<i class="fa fa-sort"></i>';
}
?>
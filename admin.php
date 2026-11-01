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
        
        <!-- Search Section -->
        <div class="search-container">
            <form method="POST" action="" class="search-form">
                <input type="text" name="searchTerm" placeholder="Search products or use filters like 'ctg:electronics price:100-200'..." 
                       value="<?php echo htmlspecialchars($_GET['searchTerm'] ?? $_POST['searchTerm'] ?? ''); ?>">
                <button type="submit" name="search" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($_GET['searchTerm'] ?? $_POST['searchTerm'] ?? '')): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-danger">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
            
            <div class="search-tips">
                <span class="search-tips-toggle">Show search tips</span>
                <div class="search-tips-content">
                    <p><strong>Advanced Search Examples:</strong></p>
                    <ul>
                        <li>ctg:</li>
                        <li>prodname:</li>
                        <li>price:</li>
                        <li>brand:</li>
                        <li>inv:</li>
                        <li>warehouse:</li>
                        <li>You can combine filters</code></li>
                    </ul>
                </div>
            </div>
            
            <?php if (!empty($filters)): ?>
                <div class="active-filters">
                    <strong>Active filters:</strong>
                    <?php foreach ($filters as $type => $value): ?>
                        <span class="filter-tag">
                            <?php echo htmlspecialchars("$type:$value"); ?>
                            <i class="fas fa-times" onclick="removeFilter('<?php echo $type; ?>')"></i>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="grid-container">
            <!-- Form Section -->
            <div class="form-container">
                <h2>
                    <?php echo $editProduct ? '<i class="fas fa-edit"></i> Edit Product' : '<i class="fas fa-plus-circle"></i> Add New Product'; ?>
                </h2>
                
                <form method="POST" action="">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="productID" value="<?php echo $editProduct['productID']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="prodName" class="form-label">Product Name:</label>
                        <input type="text" id="prodName" name="prodName" class="form-control" 
                               value="<?php echo $editProduct ? htmlspecialchars($editProduct['prodName']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prodDesc" class="form-label">Product Description:</label>
                        <textarea id="prodDesc" name="prodDesc" class="form-control" rows="3" required><?php echo $editProduct ? htmlspecialchars($editProduct['prodDesc']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="prodPrice" class="form-label">Product Price:</label>
                        <input type="number" id="prodPrice" name="prodPrice" class="form-control" step="0.01" min="0" 
                               value="<?php echo $editProduct ? htmlspecialchars($editProduct['prodPrice']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prodInv" class="form-label">Product Inventory:</label>
                        <input type="number" id="prodInv" name="prodInv" class="form-control" min="0" 
                               value="<?php echo $editProduct ? htmlspecialchars($editProduct['prodInv']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="brandID" class="form-label">Brand:</label>
                        <select id="brandID" name="brandID" class="form-control" required>
                            <option value="">-- Select Brand --</option>
                            <?php foreach ($brands as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($editProduct && $editProduct['brandID'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipped_from" class="form-label">Shipped From Warehouse:</label>
                        <select id="shipped_from" name="shipped_from" class="form-control" required>
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($editProduct && $editProduct['shipped_from'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ctgID" class="form-label">Category:</label>
                        <select id="ctgID" name="ctgID" class="form-control" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($editProduct && $editProduct['ctgID'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($editProduct): ?>
                        <button type="submit" name="update" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-danger">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="create" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Create Product
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-container">
                <h2><i class="fas fa-list"></i> Product List 
                <?php 
                    $searchDisplay = $_GET['searchTerm'] ?? $_POST['searchTerm'] ?? '';
                    if (!empty($searchDisplay)) {
                        echo "- Search results for: \"".htmlspecialchars($searchDisplay)."\"";
                    }
                ?>
                </h2>
                
                <?php if ($result->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('prodName', $sortColumn, $sortOrder); ?>'">
                                    Product Name <?php echo getSortIcon('prodName', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('prodDesc', $sortColumn, $sortOrder); ?>'">
                                    Description <?php echo getSortIcon('prodDesc', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('prodPrice', $sortColumn, $sortOrder); ?>'">
                                    Price <?php echo getSortIcon('prodPrice', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('prodInv', $sortColumn, $sortOrder); ?>'">
                                    Inventory <?php echo getSortIcon('prodInv', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('brandName', $sortColumn, $sortOrder); ?>'">
                                    Brand <?php echo getSortIcon('brandName', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('address', $sortColumn, $sortOrder); ?>'">
                                    Warehouse <?php echo getSortIcon('address', $sortColumn, $sortOrder); ?>
                                </th>
                                <th class="sortable" onclick="window.location='<?php echo getSortUrl('ctgName', $sortColumn, $sortOrder); ?>'">
                                    Category <?php echo getSortIcon('ctgName', $sortColumn, $sortOrder); ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['prodName']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['prodDesc'], 0, 50)) . (strlen($row['prodDesc']) > 50 ? '...' : ''); ?></td>
                                    <td>₱<?php echo number_format($row['prodPrice'], 2); ?></td>
                                    <td><?php echo $row['prodInv']; ?></td>
                                    <td><?php echo htmlspecialchars($row['brandName'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['ctgName'] ?? 'N/A'); ?></td>
                                    <td class="action-buttons">
                                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?edit=' . $row['productID']; ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?delete=' . $row['productID'] . '&csrf_token=' . $csrf_token; ?>" 
                                           class="action-btn delete-btn"
                                           onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li><a href="?page=1<?php echo (!empty($searchDisplay)) ? '&search=1&searchTerm='.urlencode($searchDisplay) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.$sortColumn.'&order='.$sortOrder : ''; ?>">&laquo; First</a></li>
                                <li><a href="?page=<?php echo $page-1; ?><?php echo (!empty($searchDisplay)) ? '&search=1&searchTerm='.urlencode($searchDisplay) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.$sortColumn.'&order='.$sortOrder : ''; ?>">&lsaquo; Prev</a></li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?><?php echo (!empty($searchDisplay)) ? '&search=1&searchTerm='.urlencode($searchDisplay) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.$sortColumn.'&order='.$sortOrder : ''; ?>" 
                                       <?php echo ($i == $page) ? 'class="active"' : ''; ?>>
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li><a href="?page=<?php echo $page+1; ?><?php echo (!empty($searchDisplay)) ? '&search=1&searchTerm='.urlencode($searchDisplay) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.$sortColumn.'&order='.$sortOrder : ''; ?>">Next &rsaquo;</a></li>
                                <li><a href="?page=<?php echo $totalPages; ?><?php echo (!empty($searchDisplay)) ? '&search=1&searchTerm='.urlencode($searchDisplay) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.$sortColumn.'&order='.$sortOrder : ''; ?>">Last &raquo;</a></li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> No products found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(function() {
                alerts.forEach(function(alert) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 1s';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 1000);
                });
            }, 5000);
        }
        
        const tabLinks = document.querySelectorAll('.tab-link');
        tabLinks.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabLinks.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('#productManagementTab, #orderManagementTab').forEach(content => {
                    content.classList.remove('active');
                });
                
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab')).classList.add('active');
            });
        });
        
        const searchTipsToggle = document.querySelector('.search-tips-toggle');
        const searchTipsContent = document.querySelector('.search-tips-content');
        
        if (searchTipsToggle && searchTipsContent) {
            searchTipsToggle.addEventListener('click', function() {
                if (searchTipsContent.style.display === 'block') {
                    searchTipsContent.style.display = 'none';
                    searchTipsToggle.textContent = 'Show search tips';
                } else {
                    searchTipsContent.style.display = 'block';
                    searchTipsToggle.textContent = 'Hide search tips';
                }
            });
        }
    });
    
    function removeFilter(filterType) {
        const urlParams = new URLSearchParams(window.location.search);
        let searchTerm = urlParams.get('searchTerm') || '';
        
        const pattern = new RegExp(`${filterType}:[^\\s]+\\s*`, 'i');
        searchTerm = searchTerm.replace(pattern, '').trim();
        
        let newUrl = '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>';
        if (searchTerm) {
            newUrl += '?search=1&searchTerm=' + encodeURIComponent(searchTerm);
            
            if (urlParams.has('sort') && urlParams.has('order')) {
                newUrl += '&sort=' + urlParams.get('sort') + '&order=' + urlParams.get('order');
            }
            
            if (urlParams.has('page')) {
                newUrl += '&page=' + urlParams.get('page');
            }
        } else {
            const params = [];
            if (urlParams.has('sort') && urlParams.has('order')) {
                params.push('sort=' + urlParams.get('sort'));
                params.push('order=' + urlParams.get('order'));
            }
            if (urlParams.has('page')) {
                params.push('page=' + urlParams.get('page'));
            }
            
            if (params.length > 0) {
                newUrl += '?' + params.join('&');
            }
        }
        
        window.location.href = newUrl;
    }

</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
</body>
</html>
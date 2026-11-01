<!DOCTYPE html>
<html>
    <head>
        <title>Product Page</title>
        <link rel="stylesheet" type="text/css" href="generalstyle.css">
        <link rel="stylesheet" type="text/css" href="productpage.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>

    </head>
    <body>
    <?php
        include 'dbconnection.php';
        if (isset($_SESSION['customerID']) && isset($_GET['productID'])) {
            $customerID = $_SESSION['customerID'];
            $productID = intval($_GET['productID']);
            trackProductView($conn, $customerID, $productID);
        }
        $productID = isset($_GET['productID']) ? intval($_GET['productID']) : 0;
    
        if ($productID > 0) {
            $stmt = $conn->prepare("
                SELECT p.prodName, p.prodPrice, p.prodInv, p.thumbnail, b.brandName AS prodBrand, w.address AS prodShip
                FROM product p
                JOIN brand b ON p.brandID = b.brandID
                JOIN warehouse w ON p.shipped_from = w.warehouseID
                WHERE p.productID = ?
            ");
            $stmt->bind_param("i", $productID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
            } else {
                die("Product not found.");
            }
            $stmt->close();
        } else {
            die("Invalid product ID.");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'wishlist') {
            if (!isset($_SESSION['customerID'])) {
                echo "<script>alert('Please log in to add items to your wishlist'); window.location.href='log-in.php';</script>";
                exit;
            }
            
            $customerID = $_SESSION['customerID'];
            $productID = isset($_POST['productID']) ? intval($_POST['productID']) : 0;
            
            if ($productID > 0) {
                $check = $conn->prepare("SELECT wishlistID FROM wishlist WHERE customerID = ? AND productID = ?");
                $check->bind_param("ii", $customerID, $productID);
                $check->execute();
                $result = $check->get_result();
                
                if ($result->num_rows == 0) {
                    $insert = $conn->prepare("INSERT INTO wishlist (customerID, productID) VALUES (?, ?)");
                    $insert->bind_param("ii", $customerID, $productID);
                    
                    if ($insert->execute()) {
                        echo "<script>alert('Product added to wishlist'); window.location.href=window.location.href;</script>";
                    } else {
                        echo "<script>alert('Failed to add product to wishlist: " . $insert->error . "'); window.location.href=window.location.href;</script>";
                    }
                } else {
                    $delete = $conn->prepare("DELETE FROM wishlist WHERE customerID = ? AND productID = ?");
                    $delete->bind_param("ii", $customerID, $productID);
                    
                    if ($delete->execute()) {
                        echo "<script>alert('Product removed from wishlist'); window.location.href=window.location.href;</script>";
                    } else {
                        echo "<script>alert('Failed to remove product from wishlist: " . $delete->error . "'); window.location.href=window.location.href;</script>";
                    }
                }
            } else {
                echo "<script>alert('Invalid product ID'); window.location.href=window.location.href;</script>";
            }
            
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_purchase']) && $_POST['direct_purchase'] == 1) {
            // Check if user is logged in
            if (!isset($_SESSION['customerID'])) {
                echo "<script>alert('Please log in to make a purchase'); window.location.href='log-in.php';</script>";
                exit;
            }
            
            // Validate inputs
            $productID = filter_var($_POST['productID'], FILTER_VALIDATE_INT);
            $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
            
            if ($productID === false || $quantity === false || $quantity < 1) {
                echo "<script>alert('Invalid product or quantity'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            // Check product exists and has enough stock
            $stmt = $conn->prepare("SELECT * FROM product WHERE productID = ?");
            $stmt->bind_param("i", $productID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                echo "<script>alert('Product not found'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            $product = $result->fetch_assoc();
            
            if ($product['prodInv'] < $quantity) {
                echo "<script>alert('Not enough stock available'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            // Create a temporary order
            $orderTotal = $product['prodPrice'] * $quantity;
            $orderStmt = $conn->prepare("INSERT INTO orders (customerID, totalAmount, orderStatus) VALUES (?, ?, 'pending')");
            $orderStmt->bind_param("id", $_SESSION['customerID'], $orderTotal);
            
            if (!$orderStmt->execute()) {
                echo "<script>alert('Error creating order: " . $orderStmt->error . "'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            $orderID = $orderStmt->insert_id;
            
            // Add order details
            $detailStmt = $conn->prepare("INSERT INTO order_details (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)");
            $detailStmt->bind_param("iiid", $orderID, $productID, $quantity, $product['prodPrice']);
            
            if (!$detailStmt->execute()) {
                // If details insertion fails, delete the order to avoid orphaned records
                $deleteOrder = $conn->prepare("DELETE FROM orders WHERE orderID = ?");
                $deleteOrder->bind_param("i", $orderID);
                $deleteOrder->execute();
                
                echo "<script>alert('Error adding order details: " . $detailStmt->error . "'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            // Redirect to payment page with the order ID
            header("Location: payment.php?order_id=" . $orderID);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addToCart') {
            // Check if user is logged in
            if (!isset($_SESSION['customerID'])) {
                echo "<script>alert('Please log in to add items to your cart'); window.location.href='log-in.php';</script>";
                exit;
            }
            $customerID = $_SESSION['customerID'];
            $productID = isset($_POST['productID']) ? intval($_POST['productID']) : 0;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
            if ($quantity < 1) {
                echo "<script>alert('Invalid quantity'); window.location.href=window.location.href;</script>";
                exit;
            }
            $checkProduct = $conn->prepare("SELECT prodInv FROM product WHERE productID = ?");
            $checkProduct->bind_param("i", $productID);
            $checkProduct->execute();
            $productResult = $checkProduct->get_result();
            
            if ($productResult->num_rows == 0) {
                echo "<script>alert('Product not found'); window.location.href=window.location.href;</script>";
                exit;
            }
            
            $productData = $productResult->fetch_assoc();
            
            if ($productData['prodInv'] < $quantity) {
                echo "<script>alert('Not enough stock available'); window.location.href=window.location.href;</script>";
                exit;
            }
            $checkCart = $conn->prepare("SELECT cartID, quantity FROM cart WHERE customerID = ? AND productID = ?");
            $checkCart->bind_param("ii", $customerID, $productID);
            $checkCart->execute();
            $cartResult = $checkCart->get_result();
            
            if ($cartResult->num_rows > 0) {
                $cartData = $cartResult->fetch_assoc();
                $newQuantity = $cartData['quantity'] + $quantity;
                
                if ($newQuantity > $productData['prodInv']) {
                    echo "<script>alert('Cannot add more of this item - would exceed available stock'); window.location.href=window.location.href;</script>";
                    exit;
                }
                
                $updateCart = $conn->prepare("UPDATE cart SET quantity = ? WHERE cartID = ?");
                $updateCart->bind_param("ii", $newQuantity, $cartData['cartID']);
                
                if ($updateCart->execute()) {
                    echo "<script>alert('Cart updated successfully'); window.location.href=window.location.href;</script>";
                } else {
                    echo "<script>alert('Failed to update cart: " . $updateCart->error . "'); window.location.href=window.location.href;</script>";
                }
            } else {
                $insertCart = $conn->prepare("INSERT INTO cart (customerID, productID, quantity) VALUES (?, ?, ?)");
                $insertCart->bind_param("iii", $customerID, $productID, $quantity);
                
                if ($insertCart->execute()) {
                    echo "<script>alert('Product added to cart'); window.location.href=window.location.href;</script>";
                } else {
                    echo "<script>alert('Failed to add product to cart: " . $insertCart->error . "'); window.location.href=window.location.href;</script>";
                }
            }
            
            exit;
        }
        $descStmt = $conn->prepare("SELECT prodImg, prodDesc, specs FROM prod_desc WHERE productID = ? ORDER BY imgPos ASC");
        $descStmt->bind_param("i", $productID);
        $descStmt->execute();
        $descResult = $descStmt->get_result();

        $descItems = [];
        while ($row = $descResult->fetch_assoc()) {
            $descItems[] = $row;
        }
        $descStmt->close();
        $mainImg = '';
        $additionalImgs = [];
        $description = '';
        $specs = '';

        if (!empty($descItems)) {
            foreach ($descItems as $index => $item) {
                if ($index === 0) {
                    $mainImg = $item['prodImg'];
                    $description = $item['prodDesc'];
                    $specs = $item['specs'];
                } else {
                    $additionalImgs[] = $item['prodImg'];
                }
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
            <nav>
                <div class="container-1g mx-auto" style="max-width:1200px">
                <div class="storenav container-md">
                    <nav class="navbar navbar-expand-md my-5" id="storenavbar">
                        <div class="navbar-collapse">
                            <ul class="navbar-nav me-auto">
                                <li class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle" id="storeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Your Store</a>
                                    <ul class="dropdown-menu" aria-labelledby="storeDropdown">
                                        <li><a class="dropdown-item" href="profile.php">Your Profile</a></li>
                                        <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="mainpage.php">Discovery Queue</a></li>
                                        <li><a class="dropdown-item" href="mainpage.php">New Releases</a></li>
                                        <li><a class="dropdown-item" href="mainpage.php">Specials</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle" id="storeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                                    <ul class="dropdown-menu" aria-labelledby="storeDropdown">
                                        <?php
                                        $sql = "SELECT ctgID, ctgName FROM category ORDER BY ctgName";
                                        $result = $conn->query($sql);
                                        if ($result && $result->num_rows > 0):
                                            $count = 0;
                                            while ($row = $result->fetch_assoc()):
                                                echo '<li><a class="dropdown-item" href="category.php?categoryID=' . $row['ctgID'] . '">' . htmlspecialchars($row['ctgName']) . '</a></li>';
                                                $count++;
                                                if ($count % 4 == 0 && $count < $result->num_rows) {
                                                    echo '<li><hr class="dropdown-divider"></li>';
                                                }
                                            endwhile;
                                        else:
                                            echo '<li><span class="dropdown-item text-muted">No categories available</span></li>';
                                        endif;
                                        ?>
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="#" class="nav-link">Points Shop</a></li>
                                <li class="nav-item"><a href="#" class="nav-link">News and Update</a></li>
                            </ul>
                            <div class="searchbar" style="width:35%">
                                <form action="allproducts.php" method="GET" class="d-flex align-items-center position-relative w-100">
                                    <input type="search" name="search" class="form-control me-3" placeholder="Search for products" style="padding-right:50px;">
                                    <button class="btn btn-search position-absolute end-0 me-3" type="submit" 
                                    style="top: 50%; transform: translateY(-50%); border: none; background: none; padding: 8px 15px;">
                                        <i class="bi bi-search"></i>
                                    </button> 
                                </form>
                            </div>
                </div>
                </div>
                </div>
            </nav>
            <?php
            function trackProductView($conn, $customerID, $productID) {
                $check = $conn->prepare("SELECT viewID FROM viewed_product WHERE customerID = ? AND productID = ?");
                $check->bind_param("ii", $customerID, $productID);
                $check->execute();
                
                if ($check->get_result()->num_rows == 0) {
                    $insert = $conn->prepare("INSERT INTO viewed_product (customerID, productID) VALUES (?, ?)");
                    $insert->bind_param("ii", $customerID, $productID);
                    $insert->execute();
                } else {
                    $update = $conn->prepare("UPDATE viewed_product SET viewed_at = CURRENT_TIMESTAMP WHERE customerID = ? AND productID = ?");
                    $update->bind_param("ii", $customerID, $productID);
                    $update->execute();
                }
            }
            ?>
            <div class="product-pane container-sm container-2g mx-auto" style="max-width:1200px;background-color:#F5F5F5;border:5px solid #527787;">
                <div class="container-md my-5">
                    <div class="row gx-5 align-items-stretch">
                        <div class="col-md-6">
                            <div class="h-100" style="border:5px solid #527787;">
                                <?php if(!empty($product['thumbnail'])):?>
                                <img src="<?php echo htmlspecialchars($product['thumbnail']);?>" 
                                    alt="<?php echo htmlspecialchars($product['prodName']);?>" 
                                    class="img-fluid w-100 h-100" 
                                    style="object-fit: cover;">
                                <?php else:?>
                                    <img src="https://jolstatic.fr/www/captures/4949/2/144312-800.jpg"
                                    alt=""<?php echo htmlspecialchars($product['prodName']);?>
                                    class="img=fluid w-100 h100"
                                    style="object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex flex-column h-100">
                                <div class="mb-3">
                                    <h2 class="product-name mb-1"><?php echo htmlspecialchars($product['prodName']); ?></h2>
                                    <h4 class="brand text-muted"><?php echo htmlspecialchars($product['prodBrand']); ?></h4>
                                </div>
                                
                                <div class="mb-3 p-3" style="background-color:#A3BFD0;border:solid 3px #527787;border-radius:10px">
                                    <h2 class="price mb-0" style="font-size:50px">₱ <?php echo number_format($product['prodPrice'], 2); ?></h2>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="shipping mb-2"><i class="bi bi-truck me-2"></i>Shipping From: <?php echo htmlspecialchars($product['prodShip']); ?></h5>
                                    <h5 class="stock mb-0"><i class="bi bi-box-seam me-2"></i>Stock: <?php echo $product['prodInv'] > 0 ? $product['prodInv'] : 'Out of Stock'; ?></h5>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label mb-2">Quantity:</label>
                                    <div class="input-group" style="width:150px">
                                        <button type="button" class="btn btn-danger btn-number" data-type="minus" data-field="quant[2]">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="text" name="quant[2]" 
                                            class="form-control input-number text-center" 
                                            value="1" 
                                            min="1" 
                                            max="<?php echo $product['prodInv']; ?>" 
                                            id="quantity-input">
                                        <button type="button" class="btn btn-success btn-number" data-type="plus" data-field="quant[2]">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mt-auto">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <form method="POST" action="" class="h-100">
                                                <input type="hidden" name="action" value="addToCart">
                                                <input type="hidden" name="productID" value="<?php echo $productID; ?>">
                                                <input type="hidden" name="quantity" value="1" id="cart-quantity-input">
                                                <button type="submit" class="btn fw-bold w-100 py-2" 
                                                        style="background-color:#7FA3BA" 
                                                        onmouseover="this.style.backgroundColor='#66c0f4'" 
                                                        onmouseout="this.style.backgroundColor='#7FA3BA'">
                                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <form method="POST" action="" class="h-100">
                                                <input type="hidden" name="direct_purchase" value="1">
                                                <input type="hidden" name="productID" value="<?php echo $productID; ?>">
                                                <input type="hidden" name="quantity" id="buy-quantity-input">
                                                <button type="submit" class="btn fw-bold w-100 py-2" 
                                                        style="background-color:#7FA3BA" 
                                                        onmouseover="this.style.backgroundColor='#66c0f4'" 
                                                        onmouseout="this.style.backgroundColor='#7FA3BA'">
                                                    <i class="bi bi-bag-check me-2"></i>Buy Now
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <form method="POST" action="" class="h-100">
                                                <input type="hidden" name="action" value="wishlist">
                                                <input type="hidden" name="productID" value="<?php echo $productID; ?>">
                                                <button type="submit" class="btn fw-bold w-100 py-2" 
                                                        style="background-color:#7FA3BA" 
                                                        onmouseover="this.style.backgroundColor='#66c0f4'" 
                                                        onmouseout="this.style.backgroundColor='#7FA3BA'">
                                                    <i class="bi bi-heart<?php 
                                                        if (isset($_SESSION['customerID'])) {
                                                            $checkWishlist = $conn->prepare("SELECT wishlistID FROM wishlist WHERE customerID = ? AND productID = ?");
                                                            $checkWishlist->bind_param("ii", $_SESSION['customerID'], $productID);
                                                            $checkWishlist->execute();
                                                            $inWishlist = $checkWishlist->get_result()->num_rows > 0;
                                                            echo $inWishlist ? '-fill' : '';
                                                        } else {
                                                            echo '';
                                                        }
                                                    ?> me-2"></i>
                                                    <?php
                                                        if (isset($_SESSION['customerID'])) {
                                                            echo $inWishlist ? 'Remove' : 'Wishlist';
                                                        } else {
                                                            echo 'Wishlist';
                                                        }
                                                    ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Star rating functionality
                const stars = document.querySelectorAll('.rating-star');
                const ratingInput = document.getElementById('rating-value');
                
                if (ratingInput) {
                    let currentRating = parseInt(ratingInput.value) || 0;
                    
                    function updateStars(rating) {
                        stars.forEach(s => {
                            const starValue = parseInt(s.getAttribute('data-value'));
                            if (starValue <= rating) {
                                s.classList.add('active');
                            } else {
                                s.classList.remove('active');
                            }
                        });
                    }
                    
                    function previewStars(rating) {
                        stars.forEach(s => {
                            const starValue = parseInt(s.getAttribute('data-value'));
                            if (starValue <= rating) {
                                s.classList.add('hover');
                            } else {
                                s.classList.remove('hover');
                            }
                        });
                    }
                    
                    function resetStars() {
                        stars.forEach(s => {
                            s.classList.remove('hover');
                        });
                        updateStars(currentRating);
                    }
                    
                    stars.forEach(star => {
                        star.addEventListener('click', function() {
                            currentRating = parseInt(this.getAttribute('data-value'));
                            ratingInput.value = currentRating;
                            updateStars(currentRating);
                        });
                        
                        star.addEventListener('mouseover', function() {
                            const value = parseInt(this.getAttribute('data-value'));
                            previewStars(value);
                        });
                        
                        star.addEventListener('mouseout', function() {
                            resetStars();
                        });
                    });
                }
                
                $(document).ready(function() {
                    function syncAllQuantities(value) {
                        $('#quantity-input').val(value);
                        $('#cart-quantity-input').val(value);
                        $('#buy-quantity-input').val(value);
                        
                        // Update button states
                        const minValue = parseInt($('#quantity-input').attr('min')) || 1;
                        const maxValue = parseInt($('#quantity-input').attr('max')) || 999;
                        
                        $(".btn-number[data-type='minus']").prop('disabled', value <= minValue);
                        $(".btn-number[data-type='plus']").prop('disabled', value >= maxValue);
                    }
                    
                    syncAllQuantities(1);
                    
                    $('.input-number').on('input change', function() {
                        var currentVal = parseInt($(this).val()) || 0;
                        var minValue = parseInt($(this).attr('min')) || 1;
                        var maxValue = parseInt($(this).attr('max')) || 999;
                        
                        // Ensure value is within bounds
                        if (currentVal < minValue) currentVal = minValue;
                        if (currentVal > maxValue) currentVal = maxValue;
                        if (isNaN(currentVal)) currentVal = minValue;
                        
                        syncAllQuantities(currentVal);
                    });
                    
                    // Handle plus/minus buttons
                    $('.btn-number').click(function(e) {
                        e.preventDefault();
                        
                        const type = $(this).attr('data-type');
                        const fieldName = $(this).attr('data-field');
                        const input = $("input[name='" + fieldName + "']");
                        let currentVal = parseInt(input.val()) || 1;
                        const minValue = parseInt(input.attr('min')) || 1;
                        const maxValue = parseInt(input.attr('max')) || 999;
                        
                        if (type == 'minus' && currentVal > minValue) {
                            currentVal -= 1;
                        } else if (type == 'plus' && currentVal < maxValue) {
                            currentVal += 1;
                        }
                        
                        syncAllQuantities(currentVal);
                    });
                });
            });
            </script>
                <div class="description-pane">
                    <div class="container-md my-5" style="max-width:1200px;background-color:#F5F5F5;border:5px solid #527787;">
                        <h2 class="description-header my-3">Product Description (TEMP DO NOT REMOVE)</h2>
                        <div class="description-content">
                            <div class="description-preview">
                                <pre>
                                <p class="general-description"style="font-size:20px">
                                General Decription goes here.
                                General Decription goes here.
                                General Decription goes here.
                                General Decription goes here.
                                General Decription goes here.
                                General Decription goes here.
                                </p></pre>
                                <img src="https://jolstatic.fr/www/captures/4949/2/144312-800.jpg" alt="" class="img-fluid">
                            </div>
                            <div class="collapse" id="descriptionCollapse">
                                <div class="additional-content">
                                    <img src="https://jolstatic.fr/www/captures/4949/2/144312-800.jpg" alt="" class="img-fluid">
                                    <img src="https://jolstatic.fr/www/captures/4949/2/144312-800.jpg" alt="" class="img-fluid">
                                    <img src="https://jolstatic.fr/www/captures/4949/2/144312-800.jpg" alt="" class="img-fluid">
                                    <pre><p class="specifications"style="font-size:20px">
                                        Product specs from database goes here
                                        Product specs from database goes here
                                        Product specs from database goes here
                                        Product specs from database goes here
                                        Product specs from database goes here
                                    </p></pre>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#descriptionCollapse" aria-expanded="false" aria-controls="descriptionCollapse" id="toggleButton">
                                    Expand
                                </button>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const collapseElement = document.getElementById('descriptionCollapse');
                                const toggleButton = document.getElementById('toggleButton');
                                
                                collapseElement.addEventListener('show.bs.collapse', function() {
                                    toggleButton.textContent = 'Collapse';
                                });
                                
                                collapseElement.addEventListener('hidden.bs.collapse', function() {
                                    toggleButton.textContent = 'Expand';
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
                <div class="description-pane">
                    <div class="container-md my-5" style="max-width:1200px;background-color:#F5F5F5;border:5px solid #527787;">
                        <h2 class="description-header my-3">Product Description</h2>
                        <div class="description-content">
                            <div class="description-preview">
                            <pre><p class="general-description" style="font-size:20px"><?php echo htmlspecialchars($description); ?></p></pre>
                            <?php if (!empty($mainImg)): ?>
                                <img src="<?php echo htmlspecialchars($mainImg); ?>" alt="" class="img-fluid">
                            <?php else: ?>
                                <p>No image available.</p>
                            <?php endif; ?>
                            </div>

                            <div class="collapse" id="descriptionCollapse">
                                <div class="additional-content">
                                    <?php if (!empty($additionalImgs)): ?>
                                        <div class="additional-images">
                                            <?php foreach ($additionalImgs as $img): ?>
                                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Additional product view" class="img-fluid mb-3">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($specs)): ?>
                                        <div class="product-specifications">
                                            <h4>Product Specifications</h4>
                                            <pre><p class="specifications"><?php echo htmlspecialchars($specs); ?></p></pre>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#descriptionCollapse" aria-expanded="false" aria-controls="descriptionCollapse" id="toggleButton">
                                    Expand
                                </button>
                            </div>

                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const collapseElement = document.getElementById('descriptionCollapse');
                                const toggleButton = document.getElementById('toggleButton');

                                collapseElement.addEventListener('show.bs.collapse', function() {
                                    toggleButton.textContent = 'Collapse';
                                });

                                collapseElement.addEventListener('hidden.bs.collapse', function() {
                                    toggleButton.textContent = 'Expand';
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>

                <?php
                $loggedIn = isset($_SESSION['customerID']);
                $customerID = $loggedIn ? $_SESSION['customerID'] : 0;
                $userReview = $loggedIn ? getUserReview($productID, $customerID) : null;
                $averageRating = getProductAverageRating($productID);
                function getProductReviews($productID, $page = 1, $sortBy = 'newest', $purchaseType = 'all', $dateRange = 'all') {
                    global $conn;
                    
                    $itemsPerPage = 4;
                    $offset = ($page - 1) * $itemsPerPage;
                    
                    // Base query
                    $baseQuery = "
                        SELECT r.revID, r.revcontent, r.rating, r.revDate, 
                            c.fname, c.lname, c.username,
                            (SELECT COUNT(*) FROM order_details od 
                                JOIN orders o ON od.orderID = o.orderID 
                                WHERE o.customerID = r.customerID AND od.productID = r.productID) AS verified_purchase
                        FROM reviews r
                        JOIN customer c ON r.customerID = c.customerID
                        WHERE r.productID = ?
                    ";
                    
                    // Apply filters
                    $params = [$productID];
                    $types = "i";
                    
                    if ($purchaseType == 'verified') {
                        $baseQuery .= " AND (SELECT COUNT(*) FROM order_details od 
                                        JOIN orders o ON od.orderID = o.orderID 
                                        WHERE o.customerID = r.customerID AND od.productID = r.productID) > 0";
                    }
                    
                    if ($dateRange != 'all') {
                        $dateLimit = date('Y-m-d H:i:s', strtotime('-' . str_replace('last_', '', $dateRange)));
                        $baseQuery .= " AND r.revDate >= ?";
                        $params[] = $dateLimit;
                        $types .= "s";
                    }
                    
                    // Sorting
                    switch ($sortBy) {
                        case 'highest':
                            $baseQuery .= " ORDER BY r.rating DESC";
                            break;
                        case 'lowest':
                            $baseQuery .= " ORDER BY r.rating ASC";
                            break;
                        default: // newest
                            $baseQuery .= " ORDER BY r.revDate DESC";
                    }
                    
                    $countQuery = str_replace("SELECT r.revID, r.revcontent, r.rating, r.revDate, 
                            c.fname, c.lname, c.username,
                            (SELECT COUNT(*) FROM order_details od 
                                JOIN orders o ON od.orderID = o.orderID 
                                WHERE o.customerID = r.customerID AND od.productID = r.productID) AS verified_purchase", "SELECT COUNT(*)", $baseQuery);
                    $countQuery = preg_replace('/ORDER BY.*$/i', '', $countQuery);
                    
                    $stmt = $conn->prepare($countQuery);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $totalReviews = 0;
                        $stmt->bind_result($totalReviews);
                        $stmt->fetch();
                        $stmt->close();
                    } else {
                        return ['error' => "Error preparing statement: " . $conn->error];
                    }                    
                    $baseQuery .= " LIMIT ? OFFSET ?";
                    $params[] = $itemsPerPage;
                    $params[] = $offset;
                    $types .= "ii";
                    
                    $stmt = $conn->prepare($baseQuery);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $reviews = [];
                        
                        while ($row = $result->fetch_assoc()) {
                            $reviews[] = $row;
                        }
                        
                        $stmt->close();
                        $totalPages = ceil($totalReviews / $itemsPerPage);
                        
                        return [
                            'reviews' => $reviews,
                            'total' => $totalReviews,
                            'pages' => $totalPages,
                            'current_page' => $page
                        ];
                    } else {
                        return ['error' => "Error preparing statement: " . $conn->error];
                    }
                }

                function getProductAverageRating($productID) {
                    global $conn;
                    
                    $query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE productID = ?";
                    $stmt = $conn->prepare($query);
                    
                    if ($stmt) {
                        $stmt->bind_param("i", $productID);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stmt->close();
                        
                        return $row['avg_rating'] ? round($row['avg_rating'], 1) : 0;
                    }
                    
                    return 0;
                }

                function submitReview($productID, $customerID, $rating, $content) {
                    global $conn;
                    
                    $checkQuery = "SELECT revID FROM reviews WHERE customerID = ? AND productID = ?";
                    $stmt = $conn->prepare($checkQuery);
                    $stmt->bind_param("ii", $customerID, $productID);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                    
                    if ($result->num_rows > 0) {
                        // Update
                        $row = $result->fetch_assoc();
                        $revID = $row['revID'];
                        
                        $query = "UPDATE reviews SET revcontent = ?, rating = ?, revDate = CURRENT_TIMESTAMP WHERE revID = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssi", $content, $rating, $revID);
                    } else {
                        // Insert
                        $query = "INSERT INTO reviews (revcontent, rating, customerID, productID) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssii", $content, $rating, $customerID, $productID);
                    }
                    
                    if ($stmt->execute()) {
                        $stmt->close();
                        return true;
                    }
                    
                    $stmt->close();
                    return false;
                }

                function getUserReview($productID, $customerID) {
                    global $conn;
                    
                    $query = "SELECT revID, revcontent, rating FROM reviews WHERE customerID = ? AND productID = ?";
                    $stmt = $conn->prepare($query);
                    
                    if ($stmt) {
                        $stmt->bind_param("ii", $customerID, $productID);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $stmt->close();
                            return $row;
                        }
                        
                        $stmt->close();
                    }
                    
                    return false;
                }

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                    if (!$loggedIn) {
                        $_SESSION['redirect_after_login'] = "productpage.php?productID=$productID";
                        header("Location: login.php");
                        exit;
                    }
                    
                    $rating = $_POST['rating'];
                    $content = htmlspecialchars($_POST['review_content']);
                    
                    if (!$rating || $rating < 1 || $rating > 5) {
                        $reviewError = "Please select a rating";
                    } elseif (strlen($content) < 5) {
                        $reviewError = "Please enter a review with at least 5 characters";
                    } else {
                        // Submit 
                        if (submitReview($productID, $customerID, $rating, $content)) {
                            $reviewSuccess = "Your review has been submitted successfully!";
                            // Refresh user review
                            $userReview = getUserReview($productID, $customerID);
                            $averageRating = getProductAverageRating($productID);
                        } else {
                            $reviewError = "Failed to submit review. Please try again.";
                        }
                    }
                }

                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
                $purchaseType = isset($_GET['purchase']) ? $_GET['purchase'] : 'all';
                $dateRange = isset($_GET['date']) ? $_GET['date'] : 'all';
                
                $reviewData = getProductReviews($productID, $page, $sortBy, $purchaseType, $dateRange);
                ?>

                <div class="rating-section container-md my-5" style="max-width:1200px;background-color:#F5F5F5;border:5px solid #527787;">
                    <div class="rating-container p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold">Your Rating</h2>
                            <div class="overall-rating">
                                <h2 class="fw-bold">Overall: <span class="average-rating"><?php echo $averageRating; ?></span></h2>
                            </div>
                        </div>
                        
                        
                        <form method="post" action="productpage.php?productID=<?php echo $productID; ?>">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="star-rating user-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star rating-star <?php echo ($userReview && $userReview['rating'] >= $i) ? 'bi-star-fill active' : ''; ?>" data-value="<?php echo $i; ?>"></i>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" id="rating-value" value="<?php echo $userReview ? $userReview['rating'] : '0'; ?>">
                                </div>
                                
                                <div class="star-rating overall-stars">
                                    <?php
                                    $fullStars = floor($averageRating);
                                    $hasHalfStar = ($averageRating - $fullStars) >= 0.5;
                                    
                                    for ($i = 1; $i <= $fullStars; $i++) {
                                        echo '<i class="bi bi-star-fill overall-star filled"></i>';
                                    }
                                    
                                    if ($hasHalfStar) {
                                        echo '<i class="bi bi-star-half overall-star filled"></i>';
                                    }
                                    
                                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                    for ($i = 1; $i <= $emptyStars; $i++) {
                                        echo '<i class="bi bi-star overall-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php if (isset($reviewError)): ?>
                        <div class="alert alert-danger"><?php echo $reviewError; ?></div>
                        <?php endif; ?> 
                        <?php if (isset($reviewSuccess)): ?>
                            <div class="alert alert-success"><?php echo $reviewSuccess; ?></div>
                        <?php endif; ?>
                            
                            <div class="mb-4">
                                <h3 class="fw-bold">Your Comment</h3>
                                <textarea class="form-control comment-textarea mb-2" name="review_content" rows="4"><?php echo $userReview ? htmlspecialchars($userReview['revcontent']) : ''; ?></textarea>
                                <button type="submit" name="submit_review" class="btn btn-primary"><?php echo $userReview ? 'Update Review' : 'Post Review'; ?></button>
                                <?php if (!$loggedIn): ?>
                                    <small class="text-danger ms-2">You must be logged in to post a review</small>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <form method="get" action="productpage.php" id="filter-form">
                            <input type="hidden" name="productID" value="<?php echo $productID; ?>">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <h4 class="fw-bold">Sort by rating</h4>
                                    <select class="form-select" name="sort" id="sort-select">
                                        <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Most recent</option>
                                        <option value="highest" <?php echo $sortBy === 'highest' ? 'selected' : ''; ?>>Highest first</option>
                                        <option value="lowest" <?php echo $sortBy === 'lowest' ? 'selected' : ''; ?>>Lowest first</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h4 class="fw-bold">Purchase Type</h4>
                                    <select class="form-select" name="purchase" id="purchase-select">
                                        <option value="all" <?php echo $purchaseType === 'all' ? 'selected' : ''; ?>>All purchases</option>
                                        <option value="verified" <?php echo $purchaseType === 'verified' ? 'selected' : ''; ?>>Verified purchases</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <h4 class="fw-bold">Date Range</h4>
                                    <select class="form-select" name="date" id="date-select">
                                        <option value="all" <?php echo $dateRange === 'all' ? 'selected' : ''; ?>>All time</option>
                                        <option value="last_1_month" <?php echo $dateRange === 'last_1_month' ? 'selected' : ''; ?>>Last month</option>
                                        <option value="last_3_months" <?php echo $dateRange === 'last_3_months' ? 'selected' : ''; ?>>Last 3 months</option>
                                        <option value="last_12_months" <?php echo $dateRange === 'last_12_months' ? 'selected' : ''; ?>>Last year</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reviews section -->
                <div class="reviews-section container-md my-5" style="max-width:1200px; background-color:#F5F5F5; border:5px solid #527787;">
                    <div class="p-4">
                        <h2 class="fw-bold mb-4">Customer Reviews</h2>
                        <div class="reviews-container">
                            <?php if (isset($reviewData['error'])): ?>
                                <div class="alert alert-danger"><?php echo $reviewData['error']; ?></div>
                            <?php elseif (empty($reviewData['reviews'])): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-chat-square-text" style="font-size: 3rem;"></i>
                                    <h3 class="mt-3">No reviews at this time</h3>
                                    <p>Be the first to share your thoughts about this product</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reviewData['reviews'] as $review): ?>
                                    <div class="review-card mb-4 p-3" style="background-color:white; border-radius:8px;">
                                        <div class="d-flex justify-content-between mb-2">
                                            <div class="user-info">
                                                <span class="fw-bold"><?php echo htmlspecialchars($review['username'] ?: ($review['fname'] . ' ' . $review['lname'])); ?></span>
                                                <?php if ($review['verified_purchase'] > 0): ?>
                                                    <span class="badge bg-success ms-2">Verified Purchase</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="review-date text-muted"><?php echo date('M d, Y', strtotime($review['revDate'])); ?></div>
                                        </div>
                                        <div class="star-rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?> text-primary"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="review-text"><?php echo nl2br(htmlspecialchars($review['revcontent'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ($reviewData['pages'] > 1): ?>
                                    <nav aria-label="Review pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="productpage.php?productID=<?php echo $productID; ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sortBy; ?>&purchase=<?php echo $purchaseType; ?>&date=<?php echo $dateRange; ?>">Previous</a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">Previous</span>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $startPage = max(1, min($page - 2, $reviewData['pages'] - 4));
                                            $endPage = min($reviewData['pages'], $startPage + 4);
                                            
                                            for ($i = $startPage; $i <= $endPage; $i++):
                                            ?>
                                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="productpage.php?productID=<?php echo $productID; ?>&page=<?php echo $i; ?>&sort=<?php echo $sortBy; ?>&purchase=<?php echo $purchaseType; ?>&date=<?php echo $dateRange; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $reviewData['pages']): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="productpage.php?productID=<?php echo $productID; ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sortBy; ?>&purchase=<?php echo $purchaseType; ?>&date=<?php echo $dateRange; ?>">Next</a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">Next</span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const stars = document.querySelectorAll('.rating-star');
                    const ratingInput = document.getElementById('rating-value');
                    let currentRating = parseInt(ratingInput.value) || 0;
                    
                    stars.forEach(star => {
                        star.addEventListener('click', function() {
                            currentRating = parseInt(this.getAttribute('data-value'));
                            ratingInput.value = currentRating;
                            updateStars(currentRating);
                        });
                        
                        star.addEventListener('mouseover', function() {
                            const value = parseInt(this.getAttribute('data-value'));
                            previewStars(value);
                        });
                        
                        star.addEventListener('mouseout', function() {
                            resetStars();
                        });
                    });

                    function updateStars(value) {
                        stars.forEach((star, index) => {
                            if (index < value) {
                                star.classList.remove('bi-star');
                                star.classList.add('bi-star-fill', 'active');
                            } else {
                                star.classList.remove('bi-star-fill', 'active');
                                star.classList.add('bi-star');
                            }
                        });
                    }

                    function previewStars(value) {
                        stars.forEach((star, index) => {
                            if (index < value) {
                                star.classList.remove('bi-star');
                                star.classList.add('bi-star-fill');
                            } else {
                                star.classList.remove('bi-star-fill');
                                star.classList.add('bi-star');
                            }
                        });
                    }

                    function resetStars() {
                        updateStars(currentRating);
                    }
                    
                    const filterSelects = document.querySelectorAll('#sort-select, #purchase-select, #date-select');
                    filterSelects.forEach(select => {
                        select.addEventListener('change', function() {
                            document.getElementById('filter-form').submit();
                        });
                    });
                });
                </script>               
                </div>
                </div>
                <div class="related-items container my-5" style="background-color:#F5F5F5;border:solid 5px #527787">
                    <h2 class="related-header my-3">Related Items</h2>
                    <div class="row">
                        <?php
                        $currentProductID = $_GET['productID'];
                        $categoryQuery = "SELECT ctgID FROM product WHERE productID = $currentProductID";
                        $categoryResult = $conn->query($categoryQuery);
                        $ctgID = 0;

                        if ($categoryResult->num_rows > 0) {
                            $ctgID = $categoryResult->fetch_assoc()['ctgID'];
                        }

                        $sql = "SELECT  p.productID, p.prodName, p.prodPrice, b.brandName,p.thumbnail
                                FROM product p
                                LEFT JOIN brand b ON p.brandID = b.brandID
                                WHERE p.ctgID = $ctgID AND p.productID != $currentProductID
                                ORDER BY RAND() LIMIT 4";

                        $result = $conn->query($sql);

                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $prodID = $row['productID'];
                                $prodName = $row['prodName'];
                                $prodPrice = $row['prodPrice'];
                                $brandName = $row['brandName'];
                                $thumbnail = !empty($row['thumbnail']) ? $row['thumbnail'] : 'https://jolstatic.fr/www/captures/4949/2/144312-800.jpg';

                                $ratingSQL = "SELECT AVG(rating) as avgRating FROM reviews WHERE productID = $prodID";
                                $ratingResult = $conn->query($ratingSQL);
                                $ratingRow = $ratingResult->fetch_assoc();
                                $rating = isset($ratingRow['avgRating']) ? round(floatval($ratingRow['avgRating']), 1) : 0;

                                $starsHTML = '';
                                $fullStars = floor($rating);
                                $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                                $emptyStars = 5 - $fullStars - $halfStar;

                                for ($i = 0; $i < $fullStars; $i++) {
                                    $starsHTML .= '<i class="bi bi-star-fill" style="color: #ffc107;"></i>';
                                }
                                if ($halfStar) {
                                    $starsHTML .= '<i class="bi bi-star-half" style="color: #ffc107;"></i>';
                                }
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    $starsHTML .= '<i class="bi bi-star" style="color: #ffc107;"></i>';
                                }
                        ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <a href="productpage.php?productID=<?php echo $prodID; ?>" class="text-decoration-none text-reset">
                                    <img src="<?php echo htmlspecialchars($thumbnail); ?>" 
                                    alt="<?php echo htmlspecialchars($prodName); ?>"
                                    class="card-img-top">
                                    <div class="card-body">
                                        <h5 class="product-title"><?php echo $prodName; ?></h5>
                                        <p class="small text-muted"><?php echo $brandName; ?></p>
                                        <div class="rating-stars">
                                            <?php echo $starsHTML; ?>
                                            <?php if ($rating > 0): ?>
                                                <span class="text-muted small ms-1">(<?php echo number_format($rating, 1); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="product-price">₱<?php echo number_format($prodPrice, 2); ?></p>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php
                            endwhile;
                        else:
                            echo "<p class='text-center'>No related products available at the moment.</p>";
                        endif;
                        ?>
                    </div>
                </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    </body>
    <footer class="py-4 w-100" style="background-color: #527787; margin-top: 50px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <a href="#" class="navbar-brand fs-3 mb-3 d-block">AutoAssembly</a>
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
</html>
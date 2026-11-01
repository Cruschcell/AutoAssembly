<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shopping Cart</title>
        <link rel="stylesheet" href="generalstyle.css">
        <link rel="stylesheet" href="cart.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
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
    
    if (isset($_POST['update_quantity'])) {
        $cartID = $_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cartID = ? AND customerID = ?");
            $stmt->bind_param("iii", $quantity, $cartID, $customerID);
            $stmt->execute();
        }
        header("Location: cart.php");
        exit();
    }
    
    if (isset($_GET['remove'])) {
        $cartID = $_GET['remove'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE cartID = ? AND customerID = ?");
        $stmt->bind_param("ii", $cartID, $customerID);
        $stmt->execute();
        header("Location: cart.php");
        exit();
    }
    
    if (isset($_POST['checkout'])) {
        $cart_query = $conn->prepare("SELECT c.*, p.prodPrice, p.prodName, p.prodInv FROM cart c 
                                    JOIN product p ON c.productID = p.productID 
                                    WHERE c.customerID = ?");
        $cart_query->bind_param("i", $customerID);
        $cart_query->execute();
        $cart_result = $cart_query->get_result();
        
        $inventory_check_passed = true;
        while ($item = $cart_result->fetch_assoc()) {
            if ($item['quantity'] > $item['prodInv']) {
                $_SESSION['error'] = "Not enough inventory for " . $item['prodName'];
                $inventory_check_passed = false;
                break;
            }
        }
        
        if (!$inventory_check_passed) {
            header("Location: cart.php");
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO orders (customerID, totalAmount, orderStatus) VALUES (?, ?, 'to be delivered')");
        $stmt->bind_param("id", $customerID, $_POST['total_amount']);
        
        if (!$stmt->execute()) {
            $_SESSION['error'] = "Error creating order: " . $stmt->error;
            header("Location: cart.php");
            exit();
        }
        
        $orderID = $stmt->insert_id;
        
        $cart_query->execute();
        $cart_result = $cart_query->get_result();
        
        while ($item = $cart_result->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO order_details (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $orderID, $item['productID'], $item['quantity'], $item['prodPrice']);
            $stmt->execute();
        }
        
        $_SESSION['order_items'] = [];
        $cart_query->execute();
        $cart_result = $cart_query->get_result();
        
        while ($item = $cart_result->fetch_assoc()) {
            $_SESSION['order_items'][] = [
                'productID' => $item['productID'],
                'quantity' => $item['quantity']
            ];
        }
        
        $clear_cart = $conn->prepare("DELETE FROM cart WHERE customerID = ?");
        $clear_cart->bind_param("i", $customerID);
        $clear_cart->execute();
        
        header("Location: payment.php?order_id=" . $orderID);
        exit();
    }
    
    $cart_query = $conn->prepare("SELECT c.cartID, c.productID, c.quantity, p.prodName, p.prodDesc, p.prodPrice, 
                                p.prodInv, p.ctgID, p.brandID, b.brandName, cat.ctgName 
                                FROM cart c 
                                JOIN product p ON c.productID = p.productID
                                JOIN brand b ON p.brandID = b.brandID
                                JOIN category cat ON p.ctgID = cat.ctgID
                                WHERE c.customerID = ?");
    $cart_query->bind_param("i", $customerID);
    $cart_query->execute();
    $cart_result = $cart_query->get_result();
    
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
    
    $total = 0;
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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message mt-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($cart_result->num_rows > 0): ?>
            <div class="cart-container">
                <div class="cart-header">
                    <h1 class="fs-2 fw-bold"><i class="bi bi-cart-fill me-2"></i>Your Shopping Cart</h1>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <?php 
                        while ($item = $cart_result->fetch_assoc()): 
                            $subtotal = $item['quantity'] * $item['prodPrice'];
                            $total += $subtotal;
                            $productImage = getProductImage($conn, $item['productID']);
                        ?>
                        <div class="cart-item row align-items-center">
                            <div class="col-md-2 text-center">
                                <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($item['prodName']); ?>" class="product-image">
                            </div>
                            <div class="col-md-5">
                                <h5 class="product-name"><?php echo htmlspecialchars($item['prodName']); ?></h5>
                                <p class="product-category">
                                    <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($item['ctgName']); ?></span>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($item['brandName']); ?></span>
                                </p>
                                <p class="product-price">₱<?php echo number_format($item['prodPrice'], 2); ?></p>
                            </div>
                            <div class="col-md-5">
                                <div class="quantity-control mb-3">
                                    <form method="post" action="" class="d-flex align-items-center justify-content-end w-100">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cartID']; ?>">
                                        <button type="button" class="decrement-btn" onclick="decrementQuantity(this)">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['prodInv']; ?>">
                                        <button type="button" class="increment-btn" onclick="incrementQuantity(this, <?php echo $item['prodInv']; ?>)">+</button>
                                    </form>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="post" action="">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cartID']; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>" id="quantity_<?php echo $item['cartID']; ?>">
                                        <button type="submit" name="update_quantity" class="btn btn-primary update-btn">
                                            <i class="bi bi-arrow-repeat me-1"></i> Update
                                        </button>
                                    </form>
                                    <a href="?remove=<?php echo $item['cartID']; ?>" class="btn btn-outline-danger remove-btn">
                                        <i class="bi bi-trash me-1"></i> Remove
                                    </a>
                                </div>
                                <p class="subtotal">
                                    Subtotal: <span class="fw-bold">₱<?php echo number_format($subtotal, 2); ?></span>
                                </p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="order-summary">
                            <h5 class="summary-title">Order Summary</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-3 summary-item">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 summary-item">
                                <span>Shipping</span>
                                <span class="text-success">Free just for you xd</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4 summary-total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            <form method="post" action="">
                                <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                                <button type="submit" name="checkout" class="btn btn-success w-100 checkout-btn">
                                    <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                                </button>
                            </form>
                            <a href="mainpage.php" class="btn btn-outline-secondary w-100 mt-3 continue-btn">
                                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart cart-container">
                <i class="bi bi-cart-x empty-cart-icon"></i>
                <h3 class="empty-cart-title">Your cart is empty</h3>
                <p class="empty-cart-text">Looks like you haven't added any products to your cart yet.</p>
                <a href="mainpage.php" class="btn btn-primary shop-btn">
                    <i class="bi bi-shop me-2"></i>Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function incrementQuantity(button, maxInventory) {
        const form = button.closest('form');
        const input = form.querySelector('input[type="number"]');
        const cartID = form.querySelector('input[name="cart_id"]').value;
        const hiddenInput = document.getElementById('quantity_' + cartID);
        
        const currentValue = parseInt(input.value) || 0;
        if (currentValue < maxInventory) {
            input.value = currentValue + 1;
            if (hiddenInput) {
                hiddenInput.value = currentValue + 1;
            }
        }
    }
    
    function decrementQuantity(button) {
        const form = button.closest('form');
        const input = form.querySelector('input[type="number"]');
        const cartID = form.querySelector('input[name="cart_id"]').value;
        const hiddenInput = document.getElementById('quantity_' + cartID);
        
        const currentValue = parseInt(input.value) || 0;
        if (currentValue > 1) {
            input.value = currentValue - 1;
            if (hiddenInput) {
                hiddenInput.value = currentValue - 1;
            }
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInputs = document.querySelectorAll('input[name="quantity"]');
        
        quantityInputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateQuantityInput(this);
            });
            
            input.addEventListener('change', function() {
                validateQuantityInput(this);
            });
            
            input.addEventListener('input', function() {
                const form = this.closest('form');
                const cartID = form.querySelector('input[name="cart_id"]').value;
                const hiddenInput = document.getElementById('quantity_' + cartID);
                
                if (hiddenInput) {
                    hiddenInput.value = this.value;
                }
            });
        });
    });
    
    function validateQuantityInput(input) {
        const min = 1; // Minimum allowed quantity
        const max = parseInt(input.getAttribute('max')) || 9999; // Maximum (inventory)
        let value = parseInt(input.value) || 0;
        
        if (value < min) {
            input.value = min;
            value = min;
        }
        
        if (value > max) {
            input.value = max;
            value = max;
        }
        
        const form = input.closest('form');
        const cartID = form.querySelector('input[name="cart_id"]').value;
        const hiddenInput = document.getElementById('quantity_' + cartID);
        
        if (hiddenInput) {
            hiddenInput.value = value;
        }
    }
</script>
</body>
</html>
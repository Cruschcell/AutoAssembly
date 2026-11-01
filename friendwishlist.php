
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Friends Wishlist</title>
        <link rel="stylesheet" href="generalstyle.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>
      <?php
        include 'dbconnection.php';

        if (!isset($_SESSION['customerID'])) {
            header("Location: login.php");
            exit();
        }

        if (!isset($_GET['friendID']) || !is_numeric($_GET['friendID'])) {
            echo "Invalid friend ID.";
            exit();
        }

        $friendID = (int)$_GET['friendID'];
        $friendQuery = "SELECT username FROM customer WHERE customerID = $friendID";
        $friendResult = $conn->query($friendQuery);
        $friendName = "Friend";

        if ($friendResult && $friendResult->num_rows > 0) {
            $friendRow = $friendResult->fetch_assoc();
            $friendName = htmlspecialchars($friendRow['username']);
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
                        <li class="nav-item"><a href="log-in.php" class="nav-link small text-white">Log-in</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-lg mx-auto my-4" style="max-width:1300px;height:1000px;min-height:600px;">
        <h2 class="pt-3"><?= $friendName ?>'s Wishlist</h2>
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php
        $query = "SELECT p.*, b.brandName, c.ctgName 
          FROM wishlist w
          JOIN product p ON w.productID = p.productID
          LEFT JOIN brand b ON p.brandID = b.brandID
          LEFT JOIN category c ON p.ctgID = c.ctgID
          WHERE w.customerID = $friendID
          GROUP BY p.productID";
                
        $wishlist = $conn->query($query);
        
        if($wishlist && $wishlist->num_rows > 0): ?>
            <div class="products">
                <?php while($item = $wishlist->fetch_assoc()): ?>
                    <div class="wishlist-item">
                        <a href="productpage.php?productID=<?= $item['productID'] ?>" class="product-card-link">
                            <div class="product-container">
                                <div class="product-image-container">
                                    <img src="<?= $item['thumbnail'] ?: 'https://jolstatic.fr/www/captures/4949/2/144312-800.jpg' ?>" alt="<?= $item['prodName'] ?>">
                                </div>
                                <div class="product-info">
                                    <h3><?= $item['prodName'] ?></h3>
                                    <div class="product-tags">
                                        <?= $item['brandName'] . ', ' . $item['ctgName'] ?>
                                    </div>
                                    <div class="product-price">₱<?= number_format($item['prodPrice'], 2) ?></div>
                                    <div class="wishlist-actions">
                                        <a href="giftcheckout.php?product_id=<?= $item['productID'] ?>&recipient_id=<?= $friendID ?>" class="btn btn-success btn-sm">Buy as Gift</a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist p-5 text-center">
                <i class="empty-wishlist-icon bi bi-heart" style="font-size:3rem;"></i>
                <h4 class="empty-wishlist-title mt-3"><?= $friendName ?>'s Wishlist is empty</h4>
            </div>
        <?php endif; ?>
    </div>
     <!-- Erm yeah too lazy -->
    <style>
    .empty-wishlist {
        background:none;
        box-shadow:none;
        text-align: center;
        padding: 70px 0;
    }

    .empty-wishlist-icon {
        font-size: 6rem;
        color: #6c757d;
        margin-bottom: 20px;
    }

    .empty-wishlist-title {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .empty-wishlist-text {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 25px;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-D3AzJ6D5ow39GqT4KA4UXFX67LxrFyNrGnF6dH0TfO5E5O5snoXj3XsH/AQX4oxfO" crossorigin="anonymous"></script>
    <footer class="py-4 w-100" style="background-color: #527787; margin-top: auto;">
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
    <!--Too lazy to create another fucking css file, if you want you can fix it lah -->
    <style>
		.wishlist-item {
        background-color: #6490A3;
        border-radius: 5px;
        overflow: hidden;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    
    .wishlist-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .product-container {
        display: flex;
        align-items: stretch;
        width: 100%;
        cursor: pointer;
        position: relative;
    }
    
    .product-image-container {
        width: 250px;
        height: 200px;
        flex-shrink: 0;
        overflow: hidden;
    }
    
    .product-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .product-info {
        padding: 20px;
        flex-grow: 1;
        position: relative;
        color: white;
    }
    
    .product-info h3 {
        margin: 0 0 10px 0;
        font-size: 1.4rem;
        font-weight: 600;
    }
    
    .product-tags {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-bottom: 15px;
    }
    
    .product-price {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 40px;
    }
    
    .wishlist-actions {
        position: absolute;
        bottom: 15px;
        right: 15px;
        display: flex;
        gap: 10px;
    }
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .product-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
	</style>
</body>
</html>
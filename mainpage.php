<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="mainstyle.css">
    <title>Auto Assembly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php
    include 'dbconnection.php';
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
                                        <li><a class="dropdown-item" href="addfriends.php">Add a friend</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="mainpage.php">Discovery Queue</a></li>
                                        <li><a class="dropdown-item" href="mainpage.php">Featured</a></li>
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
        <div class="carousel-container mx-auto" style="max-width:1100px">
            <h4>Recommended Products</h4>
            <div id="recommendedCarousel" class="carousel slide carousel-fade carousel-outside-controls" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <button type="button" data-bs-target="#recommendedCarousel" data-bs-slide-to="<?php echo $i; ?>" 
                        <?php echo ($i == 0) ? 'class="active" aria-current="true"' : ''; ?> 
                        aria-label="Slide <?php echo $i + 1; ?>"></button>
                    <?php endfor; ?>
                </div>
                <div class="carousel-inner">
                    <?php
                    $sql = "SELECT p.productID, p.prodName, p.prodPrice, p.prodInv, b.brandName, w.address, c.ctgName, p.thumbnail
                            FROM product p
                            LEFT JOIN brand b ON p.brandID = b.brandID
                            LEFT JOIN category c ON p.ctgID = c.ctgID  
                            LEFT JOIN warehouse w ON p.shipped_from = w.warehouseID
                            ORDER BY RAND() LIMIT 5";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0):
                        $counter = 0; 
                        while ($row = $result->fetch_assoc()):
                            $activeClass = ($counter == 0) ? "active" : "";
                    ?>
                        <div class="carousel-item <?php echo $activeClass; ?>">
                            <a href="productpage.php?productID=<?php echo $row['productID']; ?>">
                                <img src="<?php echo !empty($row['thumbnail']) ? htmlspecialchars($row['thumbnail']) : 'images/placeholder.jpg'; ?>" 
                                    alt="<?php echo htmlspecialchars($row['prodName']); ?>" class="d-block w-100">
                            </a>
                        </div>
                    <?php
                            $counter++;
                        endwhile;
                    else:
                        echo "<p>No recommended products available at the moment.</p>";
                    endif;
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#recommendedCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#recommendedCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carouselControls = document.querySelectorAll('.carousel-control-prev, .carousel-control-next');
            
            carouselControls.forEach(control => {
                control.addEventListener('mouseout', function() {
                    this.blur(); 
                });
                
                control.addEventListener('click', function() {
                    setTimeout(() => {
                        this.blur();
                    }, 100);
                });
            });
        });
        </script>
        <div class="categories-carousel-container mx-auto mt-5" style="max-width:1100px">
            <h4>Categories</h4>
            <div id="categoriesCarousel" class="carousel slide carousel-outside-controls" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $sql = "SELECT c.ctgID, c.ctgName, ci.ctgThumbnail, ci.ctgOverlay 
                            FROM category c
                            LEFT JOIN category_img ci ON c.ctgID = ci.ctgID
                            ORDER BY c.ctgName";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0):
                        $counter = 0; 
                        $categoryCount = 0; 

                        echo '<div class="carousel-item active"><div class="row">';

                        while ($row = $result->fetch_assoc()):
                            $categoryCount++;
                            
                            if ($categoryCount > 4) {
                                $categoryCount = 1;
                                echo '</div></div><div class="carousel-item"><div class="row">';
                            }
                            
                            $thumbnailSrc = !empty($row['ctgThumbnail']) ? $row['ctgThumbnail'] : "images/placeholder.jpg";
                            
                            echo '
                            <a href="category.php?categoryID=' . $row['ctgID'] . '" class="col-3 text-center" style="text-decoration:none;color:black">
                                <div class="category-box">
                                    <img src="' . $thumbnailSrc . '" alt="' . $row['ctgName'] . '" class="img-fluid">
                                    <h5>' . $row['ctgName'] . '</h5>
                                </div>
                            </a>';
                            $counter++;
                        endwhile;
                        echo '</div></div>';
                    else:
                        echo "<p>No categories available at the moment.</p>";
                    endif;
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#categoriesCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#categoriesCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    <div class="browse container-fluid container my-5" style="max-width:1100px">
    <h4>Browse our products</h4>
    <div class="products-grid">
    <?php
    $sql = "SELECT p.productID, p.prodName, p.prodPrice, p.prodInv, b.brandName, w.address, c.ctgName, p.thumbnail
            FROM product p
            LEFT JOIN brand b ON p.brandID = b.brandID
            LEFT JOIN category c ON p.ctgID = c.ctgID  
            LEFT JOIN warehouse w ON p.shipped_from = w.warehouseID
            ORDER BY RAND() LIMIT 5";

    $result = $conn->query($sql);

    if ($result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
    ?>
        <div class="products">
            <a href="productpage.php?productID=<?php echo $row['productID']; ?>" class="product-card-link">
                <div class="product-container">
                    <div class="product-image-container">
                        <img src="<?php echo !empty($row['thumbnail']) ? htmlspecialchars($row['thumbnail']) : 'images/placeholder.jpg'; ?>" 
                            alt="<?php echo htmlspecialchars($row['prodName']); ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($row['prodName']); ?></h3>
                        <div class="product-tags">
                            <?php echo htmlspecialchars($row['brandName'] . ', ' . $row['ctgName']); ?>
                        </div>
                        <div class="product-price">₱<?php echo number_format($row['prodPrice'], 2); ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php
        endwhile;
    else:
        echo "<p>No products available at the moment.</p>";
    endif;
    ?>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <a href="allproducts.php"><button class="btn btn-light"> ▼ Browse all our products</button></a>
    </div>
    </div>
    <style>
    .carousel-outside-controls .carousel-control-prev,
    .carousel-outside-controls .carousel-control-next {
        width: 40px;
        height: 40px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 1;
        margin: 0;
        padding: 0;
        background: none;
        border: none;
        overflow: hidden;
        position: absolute;
    }

    .carousel-outside-controls .carousel-control-prev {
        left: -50px;
    }

    .carousel-outside-controls .carousel-control-next {
        right: -50px;
    }

    .carousel-outside-controls .carousel-control-prev-icon,
    .carousel-outside-controls .carousel-control-next-icon {
        width: 40px;
        height: 40px;
        background-color: rgba(82, 119, 135, 0.8); 
        border-radius: 50%;
        background-size: 50%;
        transition: background-color 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-outside-controls .carousel-control-prev:hover .carousel-control-prev-icon,
    .carousel-outside-controls .carousel-control-next:hover .carousel-control-next-icon {
        background-color: rgba(82, 119, 135, 1);
    }

    .carousel-outside-controls .carousel-control-prev:focus,
    .carousel-outside-controls .carousel-control-next:focus,
    .carousel-outside-controls .carousel-control-prev:active,
    .carousel-outside-controls .carousel-control-next:active {
        outline: none;
        box-shadow: none;
    }

    .carousel-outside-controls .carousel-control-prev:focus:not(:active) .carousel-control-prev-icon,
    .carousel-outside-controls .carousel-control-next:focus:not(:active) .carousel-control-next-icon {
        background-color: rgba(82, 119, 135, 0.9);
    }

    .carousel-container {
        position: relative;
        overflow: visible; 
        padding: 0 10px; 
    }

    @media (max-width: 991px) {
        .carousel-outside-controls .carousel-control-prev {
            left: -45px;
        }
        
        .carousel-outside-controls .carousel-control-next {
            right: -45px;
        }
    }

    @media (max-width: 767px) {
        .carousel-outside-controls .carousel-control-prev {
            left: 5px;
        }
        
        .carousel-outside-controls .carousel-control-next {
            right: 5px;
        }
    }
    </style>
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
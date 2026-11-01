<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
        Category
        </title>
        <link rel="stylesheet" href="generalstyle.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>
    <?php
    include 'dbconnection.php';
    if(isset($_GET['categoryID'])){
        $categoryID=$_GET['categoryID'];
        $categoryQuery = "SELECT c.ctgName, ci.ctgOverlay 
                FROM category c
                LEFT JOIN category_img ci ON c.ctgID = ci.ctgID
                WHERE c.ctgID = $categoryID";
        $categoryResult = $conn->query($categoryQuery);

        if($categoryResult->num_rows > 0) {
            $categoryRow = $categoryResult->fetch_assoc();
            $categoryName = $categoryRow['ctgName'];
            $overlayImage = !empty($categoryRow['ctgOverlay']) ? $categoryRow['ctgOverlay'] : 'https://c.pxhere.com/photos/05/cc/driving_drive_road_trip_car_road-173204.jpg!d';
        } else {
            $categoryName = "Category";
            $overlayImage = 'https://c.pxhere.com/photos/05/cc/driving_drive_road_trip_car_road-173204.jpg!d';
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
        <div class="image-overlay" style="
            background-image: url('<?php echo htmlspecialchars($overlayImage); ?>');
            background-blend-mode: overlay;
            background-size: cover;
            background-position: center;
            width: 100%;
            height: 1000px;
            padding: 60px 0;
            margin-bottom: 30px">
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
                                        <li><a class="dropdown-item" href="mainpage.php">Discovery Queue</a></li>
                                        <li><hr class="dropdown-divider"></li>
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
            <div class="container" style="color:white;d-flex;text-align:center;position:absolute;bottom:0;left:0;right:0;
            mix-blend-mode:screen;filter:blur(1px);font-size:150px!important;">
                <?php
                $categoryQuery= "SELECT ctgName FROM category WHERE ctgID=$categoryID";
                $categoryResult= $conn->query($categoryQuery);
                if($categoryResult->num_rows>0){
                    $categoryRow=$categoryResult->fetch_assoc();
                    $categoryName=$categoryRow['ctgName'];
                    echo "<p>$categoryName</p>";
                }
                ?>
            </div>
        </div>
        <div class="container mt-4">
            <div class="row align-items-center mb-4 g-3">
            <div class="col-md-6">
    <div class="dropdown me-3 mb-2">
        <button class="btn btn-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-sort-down-alt me-2"></i>Sort by
        </button>
        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
            <li><a class="dropdown-item" href="?categoryID=<?php echo $categoryID; ?>&sort=price_asc">Price: Low to High</a></li>
            <li><a class="dropdown-item" href="?categoryID=<?php echo $categoryID; ?>&sort=price_desc">Price: High to Low</a></li>
            <li><a class="dropdown-item" href="?categoryID=<?php echo $categoryID; ?>&sort=name_asc">Name: A to Z</a></li>
            <li><a class="dropdown-item" href="?categoryID=<?php echo $categoryID; ?>&sort=name_desc">Name: Z to A</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?categoryID=<?php echo $categoryID; ?>">Reset</a></li>
        </ul>
    </div>
    <?php
        $categoryName = $categoryRow['ctgName'];
        $search = $conn->real_escape_string($_GET['search'] ?? '');
        $sort = $_GET['sort'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $productsPerPage = 12;
        $activeFilters = [];
        if (!empty($search)) {
            $activeFilters[] = "Search: <strong>" . htmlspecialchars($search) . "</strong>";
        }
        switch ($sort) {
            case 'price_asc':
                $activeFilters[] = "Sorted by: <strong>Price: Low to High</strong>";
                break;
            case 'price_desc':
                $activeFilters[] = "Sorted by: <strong>Price: High to Low</strong>";
                break;
            case 'name_asc':
                $activeFilters[] = "Sorted by: <strong>Name: A to Z</strong>";
                break;
            case 'name_desc':
                $activeFilters[] = "Sorted by: <strong>Name: Z to A</strong>";
                break;
        }
        if (!empty($activeFilters)) {
            echo "<div class='mb-4'>Showing items filtered by: " . implode(" | ", $activeFilters) . "</div>";
        }
        ?>
        </div>
        <div class="col-md-6">
            <form class="d-flex position-relative w-100" method="GET" action="">
                <div class="input-group">
                    <input type="hidden" name="categoryID" value="<?php echo $categoryID; ?>">
                    <input type="search" name="search" class="form-control pe-4" placeholder="Search in category" 
                    value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-search position-absolute end-0 me-3" type="submit" 
                    style="top: 50%; transform: translateY(-50%); border: none; background: none; padding: 8px 15px;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        </div>
        <?php            
        if (isset($_GET['categoryID'])) {
            $categoryID = intval($_GET['categoryID']);
            $categoryQuery = "SELECT ctgName FROM category WHERE ctgID = $categoryID";
            $categoryResult = $conn->query($categoryQuery);
            if ($categoryResult->num_rows > 0) {
                $categoryRow = $categoryResult->fetch_assoc();
                $offset = ($page - 1) * $productsPerPage;
                $productQuery = "SELECT * FROM product WHERE ctgID = $categoryID";
                
                if (!empty($search)) {
                    $productQuery .= " AND (prodName LIKE '%$search%' OR prodDesc LIKE '%$search%')";
                }
                switch ($sort) {
                    case 'price_asc':
                        $productQuery .= " ORDER BY prodPrice ASC";
                        break;
                    case 'price_desc':
                        $productQuery .= " ORDER BY prodPrice DESC";
                        break;
                    case 'name_asc':
                        $productQuery .= " ORDER BY prodName ASC";
                        break;
                    case 'name_desc':
                        $productQuery .= " ORDER BY prodName DESC";
                        break;
                    default:
                        $productQuery .= " ORDER BY prodName ASC";
                }
                $productResult = $conn->query($productQuery);
                if ($productResult->num_rows > 0) {
                    echo "<div class='row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4'>";
                    while ($product = $productResult->fetch_assoc()) {
                        $prodID = $product['productID'];
                        $prodName = $product['prodName'];
                        $prodDesc = $product['prodDesc'];
                        $prodPrice = $product['prodPrice'];    
                        $thumbnail = !empty($product['thumbnail']) ? $product['thumbnail'] : 'images/placeholder.jpg';     
                        
                        $ratingSQL = "SELECT AVG(rating) as avgRating FROM reviews WHERE productID = $prodID";
                        $ratingResult = $conn->query($ratingSQL);
                        $avgRating = 0;
                        if ($ratingResult && $ratingRow = $ratingResult->fetch_assoc()) {
                            $avgRating = floatval($ratingRow['avgRating']);
                        }

                        $starsHTML = '';
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
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
                        echo "
                            <div class='col'>
                                <div class='card product-card h-100'>
                                    <a href='productpage.php?productID=$prodID' class='text-decoration-none text-reset'>
                                        <img src='" . htmlspecialchars($thumbnail) . "' 
                                            alt='" . htmlspecialchars($prodName) . "' 
                                            class='card-img-top'>
                                        <div class='card-body'>
                                            <h5 class='product-title'>" . htmlspecialchars($prodName) . "</h5>
                                            <p class='small text-muted'>" . htmlspecialchars($prodDesc) . "</p>
                                            <div class='rating-stars'>
                                                $starsHTML
                                                <span class='text-muted small ms-1'>(" . round($avgRating, 1) . ")</span>
                                            </div>
                                            <p class='product-price'>₱" . number_format($prodPrice, 2) . "</p>
                                        </div>
                                    </a>
                                </div>
                            </div>";
                        }
                    echo "</div>";
                } else {
                    echo "<p class='text-center'>No products found in this category.</p>";
                }
            } else {
                echo "<p class='text-center'>Category not found.</p>";
            }
        } else {
            echo "<p class='text-center'>No category selected.</p>";
        }
        ?>
        </div>

        <nav aria-label="Category navigation" class="mt-5 my-5">
        <?php
        $countQuery = "SELECT COUNT(*) as total FROM product WHERE ctgID = $categoryID";
        if (!empty($search)) {
            $countQuery .= " AND (prodName LIKE '%$search%' OR prodDesc LIKE '%$search%')";
        }
        $countResult = $conn->query($countQuery);
        $totalProducts = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalProducts / $productsPerPage);
        $productQuery .= " LIMIT $productsPerPage OFFSET $offset";
        $productResult = $conn->query($productQuery);
        echo "<nav aria-label='Category navigation' class='mt-5 my-5'>";
        echo "<ul class='pagination justify-content-center'>";
        $prevDisabled = ($page <= 1) ? 'disabled' : '';
        $prevPage = $page - 1;
        echo "<li class='page-item $prevDisabled'><a class='page-link' href='?categoryID=$categoryID&search=$search&sort=$sort&page=$prevPage'>&laquo;</a></li>";
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='?categoryID=$categoryID&search=$search&sort=$sort&page=$i'>$i</a></li>";
        }
        $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
        $nextPage = $page + 1;
        echo "<li class='page-item $nextDisabled'><a class='page-link' href='?categoryID=$categoryID&search=$search&sort=$sort&page=$nextPage'>&raquo;</a></li>";
        echo "</ul>";
        echo "</nav>";
        ?>
        </nav>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <!-- I fucking hate css-->
    <style>
        .card.product-card {
            transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out;
        }

        .card.product-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transform: translateY(-4px);
        }
    </style>
    </body>
    
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
</html>
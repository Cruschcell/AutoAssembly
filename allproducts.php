<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoAssembly - Products</title>
    <link rel="stylesheet" href="allproducts.css">
    <link rel="stylesheet" href="generalstyle.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<?php
include 'dbconnection.php';
$search = '';
$whereParts = [];
$params = [];
$paramTypes = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $whereParts[] = "productName LIKE ?";
    $params[] = "%$search%";
    $paramTypes .= "s";
}

if (isset($_GET['categoryID']) && !empty($_GET['categoryID'])) {
    $categoryID = $_GET['categoryID'];
    $whereParts[] = "categoryID = ?";
    $params[] = $categoryID;
    $paramTypes .= "i";
}

$brandIDs = isset($_GET['brands']) ? $_GET['brands'] : [];
if (!empty($brandIDs)) {
    $placeholders = implode(',', array_fill(0, count($brandIDs), '?'));
    $whereParts[] = "brandID IN ($placeholders)";
    $params = array_merge($params, $brandIDs);
    $paramTypes .= str_repeat('i', count($brandIDs));
}

$whereClause = '';
if (!empty($whereParts)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
}

$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'default';
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

<div class="container mt-4">
    <?php if (!empty($search)): ?>
    <div class="alert alert-info mb-4">
        Search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="float-end" style="text-decoration:none">Clear search</a>
    </div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-3">
            <form action="" method="get" class="filters-sidebar">
                <h5 class="mb-4">Filters</h5>
                
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>

                <div class="filter-section mb-4">
                    <div class="filter-title mb-2">Sort By</div>
                    <select name="sortBy" class="form-select" onchange="this.form.submit()">
                        <option value="default" <?php echo $sortBy == 'default' ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_asc" <?php echo $sortBy == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sortBy == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sortBy == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name_desc" <?php echo $sortBy == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                    </select>
                </div>

                <hr>
                
                <div class="filter-section mb-4">
                    <div class="filter-title">Categories</div>
                    <?php
                    $categoryQuery = "SELECT * FROM category";
                    $categoryResult = $conn->query($categoryQuery);
                    $selectedCategories = isset($_GET['categories']) ? $_GET['categories'] : [];

                    $counter = 0;
                    $totalCategories = $categoryResult->num_rows;
                    
                    if ($categoryResult->num_rows > 0) {
                        while($category = $categoryResult->fetch_assoc()) {
                            $counter++;
                            $isChecked = in_array($category["ctgID"], $selectedCategories) ? 'checked' : '';
                            
                            if ($counter <= 5) {
                                echo '<div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" id="cat'.$category["ctgID"].'" value="'.$category["ctgID"].'" '.$isChecked.'>
                                    <label class="form-check-label" for="cat'.$category["ctgID"].'">
                                        <i>'.$category["ctgName"].'</i>
                                    </label>
                                </div>';
                            } else {
                                $remainingCategories[] = $category;
                            }
                        }
                    }
                    
                    if (isset($remainingCategories) && count($remainingCategories) > 0) {
                        echo '<div class="collapse" id="moreCategories">';
                        foreach ($remainingCategories as $category) {
                            $isChecked = in_array($category["ctgID"], $selectedCategories) ? 'checked' : '';
                            echo '<div class="form-check">
                                <input class="form-check-input" type="checkbox" name="categories[]" id="cat'.$category["ctgID"].'" value="'.$category["ctgID"].'" '.$isChecked.'>
                                <label class="form-check-label" for="cat'.$category["ctgID"].'">
                                    <i>'.$category["ctgName"].'</i>
                                </label>
                            </div>';
                        }
                        echo '</div>
                        <button class="btn btn-link p-0 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#moreCategories" aria-expanded="false" aria-controls="moreCategories">
                            Show more...
                        </button>';
                    }
                    ?>
                </div>

                <hr>
                <div class="filter-section mb-4">
                    <div class="filter-title">Brands</div>
                    <?php
                    $brandQuery = "SELECT * FROM brand";
                    $brandResult = $conn->query($brandQuery);
                    $selectedBrands = isset($_GET['brands']) ? $_GET['brands'] : [];

                    $counter = 0;
                    $totalBrands = $brandResult->num_rows;
                    
                    if ($brandResult->num_rows > 0) {
                        while($brand = $brandResult->fetch_assoc()) {
                            $counter++;
                            $isChecked = in_array($brand["brandID"], $selectedBrands) ? 'checked' : '';
                            
                            if ($counter <= 5) {
                                echo '<div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="brands[]" id="bra'.$brand["brandID"].'" value="'.$brand["brandID"].'" '.$isChecked.'>
                                    <label class="form-check-label" for="bra'.$brand["brandID"].'">
                                        <i>'.$brand["brandName"].'</i>
                                    </label>
                                </div>';
                            } else {
                                $remainingBrands[] = $brand;
                            }
                        }
                    }
                    
                    if (isset($remainingBrands) && count($remainingBrands) > 0) {
                        echo '<div class="collapse" id="moreBrands">';
                        foreach ($remainingBrands as $brand) {
                            $isChecked = in_array($brand["brandID"], $selectedBrands) ? 'checked' : '';
                            echo '<div class="form-check">
                                <input class="form-check-input" type="checkbox" name="brands[]" id="cat'.$brand["brandID"].'" value="'.$brand["brandID"].'" '.$isChecked.'>
                                <label class="form-check-label" for="bra'.$brand["brandID"].'">
                                    <i>'.$brand["brandName"].'</i>
                                </label>
                            </div>';
                        }
                        echo '</div>
                        <button class="btn btn-link p-0 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#moreBrands" aria-expanded="false" aria-controls="moreBrands">
                            Show more...
                        </button>';
                    }
                    ?>
                </div>

                <hr>
            
                <div class="filter-section mt-4">
                    <div class="filter-title">Price Range</div>
                    <div class="price-inputs">
                        <div class="price-input">
                            <label for="minPrice" class="form-label">Min</label>
                            <input type="number" class="form-control" id="minPrice" name="minPrice" placeholder="₱0" 
                                   value="<?php echo isset($_GET['minPrice']) ? htmlspecialchars($_GET['minPrice']) : '' ?>">
                        </div>
                        <div class="price-input">
                            <label for="maxPrice" class="form-label">Max</label>
                            <input type="number" class="form-control" id="maxPrice" name="maxPrice" placeholder="₱100" 
                                   value="<?php echo isset($_GET['maxPrice']) ? htmlspecialchars($_GET['maxPrice']) : '' ?>">
                        </div>
                    </div>
                </div>
                
                <hr>
                <div class="filter-section mb-4">
                    <div class="filter-title">Rating</div>
                    <div class="rating-filter">
                        <input type="hidden" name="minRating" id="minRating" value="<?= isset($_GET['minRating']) ? $_GET['minRating'] : '' ?>">
                        <div class="star-rating d-flex align-items-center">
                            <?php 
                            $selectedRating = isset($_GET['minRating']) ? (int)$_GET['minRating'] : 0;
                            for ($i = 1; $i <= 5; $i++) {
                                echo '<i class="bi ' . ($i <= $selectedRating ? 'bi-star-fill' : 'bi-star') . 
                                    ' text-warning fs-5 me-1" style="cursor:pointer" data-rating="' . $i . '"></i>';
                            }
                            ?>
                            <span class="ms-2 small"><?= $selectedRating > 0 ? $selectedRating . ' Stars' : 'Any Rating' ?></span>
                        </div>
                    </div>
                </div>

                <script>
                document.querySelectorAll('.star-rating i').forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        const container = this.closest('.rating-filter');
                        const hiddenInput = container.querySelector('#minRating');
                        const text = container.querySelector('span');
                        
                        if (parseInt(hiddenInput.value) === rating) {
                            // Deselect all stars
                            container.querySelectorAll('i').forEach(s => {
                                s.classList.remove('bi-star-fill');
                                s.classList.add('bi-star');
                            });
                            hiddenInput.value = '';
                            text.textContent = 'Any Rating';
                        } else {
                            container.querySelectorAll('i').forEach((s, i) => {
                                const starRating = parseInt(s.dataset.rating);
                                s.classList.toggle('bi-star-fill', starRating <= rating);
                                s.classList.toggle('bi-star', starRating > rating);
                            });
                            hiddenInput.value = rating;
                            text.textContent = rating + ' Star' + (rating > 1 ? 's' : '');
                        }
                    });
                });
                </script>


                <hr>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">Reset Filters</a>
                </div>
            </form>                    
        </div>
        
        <div class="col-md-9">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php
            $productsPerPage = 20;
            $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($currentPage - 1) * $productsPerPage;

            $countSql = "SELECT COUNT(*) AS totalProducts FROM product p
                        JOIN brand b ON p.brandID = b.brandID
                        JOIN category c ON p.ctgID = c.ctgID";

            $sql = "SELECT p.*, b.brandName, c.ctgName 
                    FROM product p
                    JOIN brand b ON p.brandID = b.brandID
                    JOIN category c ON p.ctgID = c.ctgID";

            $whereConditions = [];
            $params = [];
            $paramTypes = '';

            if (!empty($search)) {
                $whereConditions[] = "(p.prodName LIKE ? OR b.brandName LIKE ? OR c.ctgName LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $paramTypes .= 'sss'; 
            }

            if (isset($_GET['minPrice']) && is_numeric($_GET['minPrice'])) {
                $minPrice = floatval($_GET['minPrice']);
                $whereConditions[] = "p.prodPrice >= ?";
                $params[] = $minPrice;
                $paramTypes .= 'd'; 
            }
            if (isset($_GET['maxPrice']) && is_numeric($_GET['maxPrice'])) {
                $maxPrice = floatval($_GET['maxPrice']);
                $whereConditions[] = "p.prodPrice <= ?";
                $params[] = $maxPrice;
                $paramTypes .= 'd';
            }

            if (isset($categoryID)) {
                $whereConditions[] = "p.ctgID = ?";
                $params[] = $categoryID;
                $paramTypes .= 'i';
            } elseif (isset($_GET['categories']) && is_array($_GET['categories'])) {
                $categories = array_map('intval', $_GET['categories']);
                $categoryList = implode(",", $categories);
                $whereConditions[] = "p.ctgID IN ($categoryList)";
            }

            $brandIDs = isset($_GET['brands']) ? $_GET['brands'] : [];
            if (!empty($brandIDs)) {
                $placeholders = implode(',', array_fill(0, count($brandIDs), '?'));
                $whereConditions[] = "p.brandID IN ($placeholders)";
                $params = array_merge($params, $brandIDs);
                $paramTypes .= str_repeat('i', count($brandIDs)); 
            }
            if (isset($_GET['minRating']) && is_numeric($_GET['minRating'])) {
                $selectedRating = floatval($_GET['minRating']);
                $whereConditions[] = "(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE productID = p.productID) 
                                      BETWEEN ? AND ?";
                $params[] = $selectedRating - 0.5;
                $params[] = $selectedRating + 0.49;
                $paramTypes .= 'dd';
            }
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
                $countSql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'prodName';
            switch ($sortBy) {
                case 'price_asc':
                    $sql .= " ORDER BY p.prodPrice ASC";
                    break;
                case 'price_desc':
                    $sql .= " ORDER BY p.prodPrice DESC";
                    break;
                case 'name_asc':
                    $sql .= " ORDER BY p.prodName ASC";
                    break;
                case 'name_desc':
                    $sql .= " ORDER BY p.prodName DESC";
                    break;
                default:
                    $sql .= " ORDER BY p.prodName ASC";
            }

            $countStmt = $conn->prepare($countSql);
            if ($params) {
                $countStmt->bind_param($paramTypes, ...$params);
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $totalProductsRow = $countResult->fetch_assoc();
            $totalProducts = $totalProductsRow['totalProducts'];
            $totalPages = ceil($totalProducts / $productsPerPage);

            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $productsPerPage;
            $paramTypes .= 'ii';

            $stmt = $conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($paramTypes, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $prodID = $row['productID'];

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

                    echo '<div class="col">
                        <div class="card product-card">
                            <a href="productpage.php?productID=' . $prodID . '" class="text-decoration-none text-reset">
                                <img src="' . (!empty($row["thumbnail"]) ? htmlspecialchars($row["thumbnail"]) : 'images/placeholder.jpg') . '" 
                                    class="card-img-top product-img" 
                                    alt="' . htmlspecialchars($row["prodName"]) . '">
                                <div class="card-body">
                                    <h5 class="product-title">' . htmlspecialchars($row["prodName"]) . '</h5>
                                    <p class="small text-muted">' . htmlspecialchars($row["brandName"]) . '</p>
                                    <div class="rating-stars">
                                        ' . $starsHTML . '
                                        <span class="text-muted small ms-1">(' . round($avgRating, 1) . ')</span>
                                    </div>
                                    <p class="product-price">₱' . number_format($row["prodPrice"], 2) . '</p>
                                </div>
                            </a>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12"><p class="text-center">No products found matching your criteria.</p></div>';
            }
            ?>
        </div>
            <nav aria-label="Page navigation" class="my-5">
                <ul class="pagination justify-content-center">
                    <?php
                    $currentScript = $_SERVER['PHP_SELF'];
                    $params = $_GET;
                    unset($params['page']);
                    $queryString = http_build_query($params);
                    $baseUrl = $currentScript . ($queryString ? '?' . $queryString . '&' : '?');
                    
                    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
                    $prevPage = $currentPage - 1;
                    echo '<li class="page-item '.$prevDisabled.'">
                            <a class="page-link" href="'.$baseUrl.'page='.$prevPage.'" tabindex="-1">Previous</a>
                          </li>';
                    
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="'.$baseUrl.'page=1">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $active = ($i == $currentPage) ? 'active' : '';
                        echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$baseUrl.'page='.$i.'">'.$i.'</a></li>';
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="'.$baseUrl.'page='.$totalPages.'">'.$totalPages.'</a></li>';
                    }
                    
                    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
                    $nextPage = $currentPage + 1;
                    echo '<li class="page-item '.$nextDisabled.'">
                            <a class="page-link" href="'.$baseUrl.'page='.$nextPage.'">Next</a>
                          </li>';
                    ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');

            alert('Product ID: ' + productId + ' added to cart!');
            // fetch('add_to_cart.php', {
            //     method: 'POST',
            //     body: JSON.stringify({ productId: productId }),
            //     headers: { 'Content-Type': 'application/json' }
            // }).then(response => response.json())
            //   .then(data => {
            //     
            //   });
        });
    });
});
</script>

<!--fucker aint working when in css so it is what it is-->
<style>
    .product-link {
    color: inherit;
    text-decoration: none!important;
    display: block;
}

.product-link:hover {
    color: inherit;
    text-decoration: none!important;
}
.product-card {
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 20px;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>

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
                    <?php
                    $categoryQuery = "SELECT * FROM category LIMIT 5";
                    $categoryResult = $conn->query($categoryQuery);
                    
                    if ($categoryResult->num_rows > 0) {
                        while($category = $categoryResult->fetch_assoc()) {
                            echo '<li class="nav-item mb-2"><a href="'.$_SERVER['PHP_SELF'].'?categoryID='.$category["ctgID"].'" class="nav-link p-0 text-muted">'.$category["ctgName"].'</a></li>';
                        }
                    }
                    ?>
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
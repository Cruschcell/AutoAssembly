<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Profile Page</title>
	<link rel="stylesheet" type="text/css" href="generalstyle.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
	<?php
    include 'dbconnection.php';
	if(!isset($_SESSION['customerID'])){
		header("location:login.php");
		exit();
	}

	$customerID = $_SESSION['customerID'];
    $message = "";
    
    // Handle profile update
    if(isset($_POST['updateProfile'])) {
        $updateFields = [];
        $types = "";
        $values = [];
        $updateSuccessful = true;
        $passwordChanged = false;
        
        // Sanitize input for basic fields
        if(isset($_POST['username']) && !empty($_POST['username'])) {
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $updateFields[] = "username = ?";
            $types .= "s";
            $values[] = $username;
            // Also update session variable
            $_SESSION['username'] = $username;
        }
        
        if(isset($_POST['email']) && !empty($_POST['email'])) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $updateFields[] = "email = ?";
            $types .= "s";
            $values[] = $email;
        }
        
        if(isset($_POST['phone']) && !empty($_POST['phone'])) {
            $phone = mysqli_real_escape_string($conn, $_POST['phone']);
            $updateFields[] = "phone = ?";
            $types .= "s";
            $values[] = $phone;
        }
        
        if(isset($_POST['currentPassword']) && !empty($_POST['currentPassword']) && 
           isset($_POST['newPassword']) && !empty($_POST['newPassword']) && 
           isset($_POST['confirmPassword']) && !empty($_POST['confirmPassword'])) {
            
            $currentPassword = $_POST['currentPassword'];
            $newPassword = $_POST['newPassword'];
            $confirmPassword = $_POST['confirmPassword'];
            
            // Verify current password
            $passwordStmt = $conn->prepare("SELECT customerPW FROM customer WHERE customerID = ?");
            $passwordStmt->bind_param("i", $customerID);
            $passwordStmt->execute();
            $result = $passwordStmt->get_result();
            $passwordRow = $result->fetch_assoc();
            $passwordStmt->close();
            
            if(password_verify($currentPassword, $passwordRow['customerPW'])) {
                if($newPassword === $confirmPassword) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateFields[] = "customerPW = ?";
                    $types .= "s";
                    $values[] = $hashedPassword;
                    $passwordChanged = true;
                } else {
                    $message = "<div class='alert alert-danger'>New passwords do not match!</div>";
                    $updateSuccessful = false;
                }
            } else {
                $message = "<div class='alert alert-danger'>Current password is incorrect!</div>";
                $updateSuccessful = false;
            }
        }
        
        if(isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profilePicture']['type'];
            
            if(in_array($fileType, $allowedTypes)) {
                $uploadDir = 'uploads/profile_pictures/';
                
                // Create directory if it doesn't exist
                if(!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = $customerID . '_' . time() . '_' . basename($_FILES['profilePicture']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if(move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetFile)) {
                    $updateFields[] = "profile_picture = ?";
                    $types .= "s";
                    $values[] = $targetFile;
                } else {
                    $message = "<div class='alert alert-danger'>Failed to upload profile picture!</div>";
                    $updateSuccessful = false;
                }
            } else {
                $message = "<div class='alert alert-danger'>Invalid file type! Please upload JPG, PNG, or GIF files only.</div>";
                $updateSuccessful = false;
            }
        }
        
        if(count($updateFields) > 0 && $updateSuccessful) {
            $sql = "UPDATE customer SET " . implode(", ", $updateFields) . " WHERE customerID = ?";
            $types .= "i";
            $values[] = $customerID;
            
            $updateStmt = $conn->prepare($sql);
            
            $updateStmt->bind_param($types, ...$values);
            
            if($updateStmt->execute()) {
                $successMsg = "Profile updated successfully!";
                if($passwordChanged) {
                    $successMsg .= " Your password has been changed.";
                }
                $message = "<div class='alert alert-success'>" . $successMsg . "</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error updating profile: " . $updateStmt->error . "</div>";
            }
            $updateStmt->close();
        } else if($updateSuccessful && count($updateFields) === 0) {
            $message = "<div class='alert alert-info'>No changes were made to your profile.</div>";
        }
    }
    
	$stmt = $conn->prepare("SELECT * FROM customer WHERE customerID = ?");
	$stmt->bind_param("i", $customerID);
	$stmt->execute();
	$user = $stmt->get_result()->fetch_assoc();
	$stmt->close();
    ?>
	<nav class="navbar navbar-expand-md py-4">
		<div class="container-fluid">
			<a href="#" class="navbar-brand fs-3">AutoAssembly</a>
			<div class="navbar-collapse">
				<ul class="navbar-nav me-auto">
					<li class="nav-item">
						<a href="#" class="nav-link fs-4 px-3">
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
    
    <?php if(!empty($message)) echo $message; ?>
    
	<div class="container-lg mx-auto my-4" style="max-width:1300px;background-color:#7FA3BA;
	border-style:solid;border-radius:10px;border-width:5px;border-color:#527787">
    	<div class="left-column p-4">
            <div class="profile d-flex align-items-center gap-4 mb-4">
                <div class="profilePic">
                    <?php
                    $profilePicture = "https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png"; // Default image
                    if(!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                        $profilePicture = $user['profile_picture'];
                    }
                    ?>
                    <img src="<?php echo $profilePicture; ?>" width="150" height="150" class="img-fluid rounded-circle">
                </div>
                <div>
                    <h2><?php echo htmlspecialchars($_SESSION['username']);?></h2>
                    
                    <div class="profile-info mb-3">
                        <div class="profile-detail">
                            <strong>Email:</strong><span class="profile-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="profile-detail">
                            <strong>Phone:</strong><span class="profile-detail-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="profile-detail">
                            <strong>Member Since:</strong><span class="profile-detail-value"><?php echo date('F j, Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mb-3 me-4 gap-3">
                <a href="order.php" class="btn btn-light">Order Status</a>
                <a href="cart.php" class="btn btn-light">View Cart</a>
                <a href="wishlist.php" class="btn btn-light">View Wishlist</a>
                <button id="editProfileBtn" class="btn btn-light">Edit Profile</button>
                <a href="friendlist.php" class="btn btn-light">Friendlist</a>

            </div>
            
            <div id="editProfileForm">
                <h4 class="mb-3">Edit Your Profile</h4>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="profilePicture" class="form-label">Profile Picture</label>
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png" class="img-thumbnail" width="100" height="100" alt="Current profile picture">
                            <input type="file" class="form-control" id="profilePicture" name="profilePicture" accept="image/*">
                        </div>
                        <small class="text-muted">Upload a new profile picture (JPG, PNG, GIF)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <h5>Change Password (Optional)</h5>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword">
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="updateProfile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
	<div class="container-lg mx-auto my-5" style="max-width:1300px;background-color:#7FA3BA;
	border-style:solid;border-radius:10px;border-width:5px;border-color:#527787">
		<h4 class="my-3">Recently Viewed Products</h4>
		
		<div class="products-grid">
		<?php
			$itemsPerPage = 7;
			$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
			$offset = ($page - 1) * $itemsPerPage;
			
			$countSql = "SELECT COUNT(*) AS total FROM viewed_product WHERE customerID = ?";
			$countStmt = $conn->prepare($countSql);
			$countStmt->bind_param("i", $customerID);
			$countStmt->execute();
			$totalResult = $countStmt->get_result()->fetch_assoc();
			$totalProducts = $totalResult['total'];
			$totalPages = ceil($totalProducts / $itemsPerPage);
			
			$sql = "SELECT p.productID, p.prodName, p.prodPrice, p.prodInv, b.brandName, w.address, c.ctgName, p.thumbnail 
					FROM viewed_product vp
					JOIN product p ON vp.productID = p.productID
					LEFT JOIN brand b ON p.brandID = b.brandID
					LEFT JOIN category c ON p.ctgID = c.ctgID  
					LEFT JOIN warehouse w ON p.shipped_from = w.warehouseID
					WHERE vp.customerID = ?
					ORDER BY vp.viewed_at DESC
					LIMIT ? OFFSET ?";

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("iii", $customerID, $itemsPerPage, $offset);
			$stmt->execute();
			$result = $stmt->get_result();

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
							<h3><?php echo $row['prodName']; ?></h3>
							<div class="product-tags">
								<?php echo $row['brandName'] . ', ' . $row['ctgName']; ?>
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
		<?php if ($totalPages > 1): ?>
			<nav aria-label="Recently viewed pagination">
				<ul class="pagination justify-content-center">
					<?php if ($page > 1): ?>
						<li class="page-item">
							<a class="page-link" href="profile.php?page=<?= $page - 1 ?>">Previous</a>
						</li>
					<?php else: ?>
						<li class="page-item disabled">
							<span class="page-link">Previous</span>
						</li>
					<?php endif; ?>
					
					<?php
					$startPage = max(1, min($page - 2, $totalPages - 4));
					$endPage = min($totalPages, $startPage + 4);
					
					for ($i = $startPage; $i <= $endPage; $i++):
					?>
						<li class="page-item <?= $i === $page ? 'active' : '' ?>">
							<a class="page-link" href="profile.php?page=<?= $i ?>"><?= $i ?></a>
						</li>
					<?php endfor; ?>
					
					<?php if ($page < $totalPages): ?>
						<li class="page-item">
							<a class="page-link" href="profile.php?page=<?= $page + 1 ?>">Next</a>
						</li>
					<?php else: ?>
						<li class="page-item disabled">
							<span class="page-link">Next</span>
						</li>
					<?php endif; ?>
				</ul>
			</nav>
		<?php endif; ?>
		</div>
        
    <script>
        document.getElementById('editProfileBtn').addEventListener('click', function() {
            document.getElementById('editProfileForm').style.display = 'block';
        });
        
        document.getElementById('cancelEditBtn').addEventListener('click', function() {
            document.getElementById('editProfileForm').style.display = 'none';
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

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
	<style>
		.products div{
    		background-color:#6490A3 !important;
			}
			.products h3{
				color:white !important;
			}
			.product-container {
				display: flex;
				align-items: center;
				width: 100%;
				cursor: pointer;
				background: #f8f9fa;
				transition: all 0.3s ease-out;
			}

			.product-container:hover {
				transform: scale(1.02);
				box-shadow: 0 4px 6px #303030;
			}
			.browse button{
				border-color:#324750 !important;
				background-color:#324750 !important;
				color:white !important;
				transition: all 0.3s ease;
			}

			.product-card-link {
				text-decoration: none;
				color: inherit;
				display: block;
				margin-bottom: 15px;
			}


			.product-image-container {
				width: 250px;
				height: 180px;
				flex-shrink: 0;
				overflow: hidden;
			}

			.product-image-container img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			.product-info {
				padding: 15px;
				flex-grow: 1;
				position: relative;
			}

			.product-info h3 {
				margin: 0 0 15px 0;
				font-size: 1.4rem;
				color: white;
			}

			.product-tags {
				color: white;
				font-size: 0.9rem;
				margin-top: 20px;
			}

			.product-price {
				position: absolute;
				bottom: 15px;
				right: 15px;
				font-size: 1.5rem;
				font-weight: bold;
				color: white;
			}
			.products-grid {
			padding-left: 15px;
			padding-right: 15px;
		}
        
			#editProfileForm {
				display: none;
				padding: 20px;
				background-color: #f8f9fa;
				border-radius: 8px;
				margin-top: 20px;
				box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
				animation: fadeIn 0.3s ease-in-out;
			}
			
			@keyframes fadeIn {
				from { opacity: 0; transform: translateY(-10px); }
				to { opacity: 1; transform: translateY(0); }
			}
			
			.profile-info {
				display: flex;
				flex-direction: column;
				gap: 10px;
			}
			
			.profile-detail {
				display: flex;
				align-items: center;
			}
			
			.profile-detail strong {
				width: 120px;
				display: inline-block;
			}
	</style>
	
</body>

</html>
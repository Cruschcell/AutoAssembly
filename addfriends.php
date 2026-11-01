<?php
include "dbconnection.php";

// Ensure the user is logged in
if (!isset($_SESSION['customerID'])) {
    header("Location: login.php");
    exit();
}

$currentUserID = $_SESSION['customerID'];
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Search query
$sql = "SELECT customerID, username, email, profile_picture
        FROM customer
        WHERE customerID != ?
        AND (fname LIKE ? OR lname LIKE ? OR username LIKE ?)";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("isss", $currentUserID, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="mainstyle.css">
    <title>Add Friends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
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

<div class="container py-5">
    <h2 class="mb-4 text-center">Find Friends</h2>
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Friend request sent successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'self'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            You cannot send a friend request to yourself.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            A friend request already exists or you're already friends.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'request_sent'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            You have already sent a friend request to this user.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(function () {
                    alert.style.display = 'none';
                }, 5000); 
            }
        });
    </script>
    <form class="d-flex mb-4" method="get" action="">
        <input class="form-control me-2" type="search" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>

    <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <img src="<?= $row['profile_picture'] ? 'uploads/' . $row['profile_picture'] : 'https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png' ?>" class="rounded-circle mb-3" width="80" height="80" alt="Profile">
                        <h3 class="card-title"><?= htmlspecialchars($row['username'])  ?></h3>
                        <p class="text-muted"><?= htmlspecialchars($row['email'])  ?></p>
                        <form method="post" action="sendfriendrequest.php">
                            <input type="hidden" name="receiverID" value="<?= $row['customerID'] ?>">
                            <button class="btn btn-outline-success" type="submit">Add Friend</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($result->num_rows == 0): ?>
        <p class="text-center text-muted">No users found.</p>
    <?php endif; ?>
</div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
</body>
</html>
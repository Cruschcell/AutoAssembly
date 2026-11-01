<?php
include "dbconnection.php";

if (!isset($_SESSION['customerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customerID'];

// Fetch friend list
$friends_query = "SELECT c.customerID, c.username, c.profile_picture, f.status 
                  FROM friends f 
                  JOIN customer c ON (
                      (c.customerID = f.receiverID AND f.senderID = ?) OR 
                      (c.customerID = f.senderID AND f.receiverID = ?)
                  )
                  WHERE f.status = 'accepted'";
$friends_stmt = $conn->prepare($friends_query);
$friends_stmt->bind_param("ii", $customerID, $customerID);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();

// Fetch incoming friend requests
$incoming_requests_query = "SELECT c.customerID, c.username, c.profile_picture 
                            FROM friends f 
                            JOIN customer c ON f.senderID = c.customerID
                            WHERE f.receiverID = ? AND f.status = 'pending'";
$incoming_requests_stmt = $conn->prepare($incoming_requests_query);
$incoming_requests_stmt->bind_param("i", $customerID);
$incoming_requests_stmt->execute();
$incoming_requests_result = $incoming_requests_stmt->get_result();

// Fetch sent friend requests
$sent_requests_query = "SELECT c.customerID, c.username, c.profile_picture 
                        FROM friends f 
                        JOIN customer c ON f.receiverID = c.customerID
                        WHERE f.senderID = ? AND f.status = 'pending'";
$sent_requests_stmt = $conn->prepare($sent_requests_query);
$sent_requests_stmt->bind_param("i", $customerID);
$sent_requests_stmt->execute();
$sent_requests_result = $sent_requests_stmt->get_result();

// Fetch gifts (wishlist gifts)
$gifts_query = "SELECT wg.giftID, p.prodName, wg.message, wg.is_claimed, wg.claimed_at, c.username
                FROM wishlist_gifts wg 
                JOIN wishlist w ON wg.wishlistID = w.wishlistID 
                JOIN product p ON w.productID = p.productID 
                JOIN customer c ON wg.buyerID = c.customerID 
                WHERE w.customerID = ?";
$gifts_stmt = $conn->prepare($gifts_query);
$gifts_stmt->bind_param("i", $customerID);
$gifts_stmt->execute();
$gifts_result = $gifts_stmt->get_result();

$gift_notifications_query = "SELECT o.orderID, o.orderDate, o.giftStatus, p.prodName, c.username AS senderName
                             FROM orders o
                             JOIN order_details od ON o.orderID = od.orderID
                             JOIN product p ON od.productID = p.productID
                             JOIN customer c ON o.customerID = c.customerID
                             WHERE o.isGift = 1 AND o.recipientID = ?";
$gift_notifications_stmt = $conn->prepare($gift_notifications_query);
$gift_notifications_stmt->bind_param("i", $customerID);
$gift_notifications_stmt->execute();
$gift_notifications_result = $gift_notifications_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Friends Page</title>
	<link rel="stylesheet" type="text/css" href="generalstyle.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-md py-4">
		<div class="container-fluid">
			<a href="#" class="navbar-brand fs-3">AutoAssembly</a>
			<div class="navbar-collapse">
				<ul class="navbar-nav me-auto">
					<li class="nav-item">
						<a href="profile.php" class="nav-link fs-4 px-3">
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
   <div class="container my-5">
    <!-- Friend Requests -->
    <div class="card shadow mb-5">
        <div class="card-header friendrequest text-dark">
            <h4 class="mb-0"><i class="bi bi-person-plus"></i> Friend Requests</h4>
        </div>
        <div class="card-body">
            <h5 class="text-muted">Incoming Requests</h5>
            <?php if ($incoming_requests_result->num_rows > 0): ?>
                <?php while ($request = $incoming_requests_result->fetch_assoc()): ?>
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= !empty($request['profile_picture']) ? htmlspecialchars($request['profile_picture']) : 'https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png' ?>" 
                            class="rounded-circle" width="50" height="50"  style="object-fit: cover;">
                            <span><?= $request['username'] ?></span>
                        </div>
                        <form action="sendfriendrequest.php" method="post" class="d-flex gap-2">
                            <input type="hidden" name="senderID" value="<?= $request['customerID'] ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No incoming friend requests.</p>
            <?php endif; ?>

            <hr>
            <h5 class="text-muted">Sent Requests</h5>
            <?php if ($sent_requests_result->num_rows > 0): ?>
                <?php while ($request = $sent_requests_result->fetch_assoc()): ?>
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                       <img src="<?= !empty($request['profile_picture']) ? htmlspecialchars($request['profile_picture']) : 'https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png' ?>" 
                       class="rounded-circle" width="50" height="50"  style="object-fit: cover;">
                            <span><?= $request['username'] ?></span>
                        <span class="ms-auto text-muted"><i class="bi bi-clock"></i> Request sent</span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No sent friend requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Friends -->
    <div class="card shadow mb-5">
        <div class="card-header friendslist text-white">
            <h4 class="mb-0"><i class="bi bi-people-fill"></i> Friends</h4>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <?php if ($friends_result->num_rows > 0): ?>
                    <?php while ($friend = $friends_result->fetch_assoc()): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card h-100">
                            <img src="<?= !empty($request['profile_picture']) ? htmlspecialchars($request['profile_picture']) : 'https://cdn.donmai.us/original/9d/a1/__gawr_gura_gawr_gura_and_smol_gura_hololive_and_1_more_drawn_by_ausurachai__9da19f70d5cda6e123f1583bb4351737.png' ?>" 
                            class="rounded-circle" width="50" height="50"  style="object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $friend['username'] ?></h5>
                                    <span class="badge bg-success">Friend</span>
                                    <form action="friendwishlist.php" method="get" class="mt-2">
                                        <input type="hidden" name="friendID" value="<?=$friend['customerID']?>">
                                        <button type"submit" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-heart"> View Wishlist</i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">You have no friends yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gifts -->
    <div class="card shadow mb-5">
    <div class="card-header bg-info text-dark">
        <h4 class="mb-0"><i class="bi bi-bell-fill"></i> Gift Notifications</h4>
    </div>
    <div class="card-body">
        <?php if ($gift_notifications_result->num_rows > 0): ?>
            <ul class="list-group list-group-flush">
            <?php while ($gift = $gift_notifications_result->fetch_assoc()): ?>
                <li class="list-group-item">
                    🎁 <strong><?= htmlspecialchars($gift['prodName']) ?></strong> from 
                    <em><?= htmlspecialchars($gift['senderName']) ?></em> 
                    <br>
                    <small class="text-muted">
                         Sent on <?= date("M d, Y", strtotime($gift['orderDate'])) ?> — Status: <?= empty($gift['giftStatus']) ? 'Accepted' : $gift['giftStatus'] ?>
                    </small>

                    <?php if ($gift['giftStatus'] === 'pending'): ?>
                        <form action="handle_gift_response.php" method="post" class="mt-2 d-flex gap-2">
                            <input type="hidden" name="orderID" value="<?= $gift['orderID'] ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">No new gifts.</p>
        <?php endif; ?>
    </div>
</div>
<style>
.friendrequest {
    background-color: #8DCFFF !important;
    color: #004085;
}
.friendslist {
    background-color:#4cae4c !important;
    color: #155724;
}
.bg-soft-yellow {
    background-color: #fff3cd !important;
    color: #856404;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
</body>
</html>
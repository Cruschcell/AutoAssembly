<?php
include "dbconnection.php";
session_start();

if (!isset($_SESSION['customerID'])) {
    header("Location: login.php");
    exit();
}

$senderID = $_SESSION['customerID'];

// Accept or reject request if action is set
if (isset($_POST['action']) && isset($_POST['senderID'])) {
    $requestSenderID = intval($_POST['senderID']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE senderID = ? AND receiverID = ?");
        $stmt->bind_param("ii", $requestSenderID, $senderID);
        $stmt->execute();
        header("Location: profile.php?status=accepted");
        exit();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("DELETE FROM friends WHERE senderID = ? AND receiverID = ?");
        $stmt->bind_param("ii", $requestSenderID, $senderID);
        $stmt->execute();
        header("Location: profile.php?status=rejected");
        exit();
    } else {
        header("Location: profile.php?error=unknown_action");
        exit();
    }
}

// Sending a new friend request
if (isset($_POST['receiverID'])) {
    $receiverID = intval($_POST['receiverID']);

    // Prevent self-adding
    if ($senderID === $receiverID) {
        header("Location: addfriends.php?error=self");
        exit();
    }

    // Check if a friend request already exists
    $check = $conn->prepare("SELECT * FROM friends WHERE (senderID = ? AND receiverID = ?) OR (senderID = ? AND receiverID = ?)");
    $check->bind_param("iiii", $senderID, $receiverID, $receiverID, $senderID);
    $check->execute();
    $existing = $check->get_result();

    if ($existing->num_rows > 0) {
        header("Location: addfriends.php?error=request_sent");
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO friends (senderID, receiverID) VALUES (?, ?)");
        $stmt->bind_param("ii", $senderID, $receiverID);
        $stmt->execute();
        header("Location: addfriends.php?success=1");
        exit();
    }
}
?>
<?php
include "dbconnection.php";

if (!isset($_SESSION['customerID']) || !isset($_POST['receiverID'])) {
    header("Location: add_friend.php");
    exit();
}

$senderID = $_SESSION['customerID'];
$receiverID = intval($_POST['receiverID']);

// Prevent self-adding
if ($senderID === $receiverID) {
    header("Location: addfriends.php?error=self");
    exit();
}

// Check if a friend request already exists
$check = $conn->prepare("SELECT * FROM friends WHERE (senderID = ? AND receiverID = ?) OR (senderID = ? AND receiverID = ?)");
$check->bind_param("iiii", $senderID, $receiverID, $receiverID, $senderID);
$check->execute();
$existing = $check->get_result();

if ($existing->num_rows > 0) {
    // If the request already exists, redirect with an error
    header("Location: addfriends.php?error=request_sent");
    exit();
} else {
    // If no request exists, send a new request
    $stmt = $conn->prepare("INSERT INTO friends (senderID, receiverID) VALUES (?, ?)");
    $stmt->bind_param("ii", $senderID, $receiverID);
    $stmt->execute();
    header("Location: addfriends.php?success=1");
    exit();
}

// for processing friend requests

?>
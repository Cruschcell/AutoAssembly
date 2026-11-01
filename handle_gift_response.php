<?php
include "dbconnection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];
    $action = $_POST['action'];

    if ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE orders SET orderStatus = 'failed', giftStatus = 'cancelled' WHERE orderID = ?");
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        header("Location: friendlist.php");
        exit();
    }

    if ($action === 'accept') {
        // Redirect to billing address form
        header("Location: accept_gift.php?orderID=$orderID");
        exit();
    }
}
?>
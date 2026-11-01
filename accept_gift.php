<?php
include "dbconnection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];
    $billAddress = $_POST['billAddress'];

    // Update the billing address in the payment table
    $stmt = $conn->prepare("UPDATE payment SET billAddress = ? WHERE orderID = ?");
    $stmt->bind_param("si", $billAddress, $orderID);
    $stmt->execute();

    // Update both the gift status and order status
    $stmt2 = $conn->prepare("UPDATE orders SET giftStatus = 'accepted', orderStatus = 'to be delivered' WHERE orderID = ?");
    $stmt2->bind_param("i", $orderID);
    $stmt2->execute();

    // Redirect to friendlist.php after submission
    header("Location: friendlist.php");
    exit();
}

$orderID = $_GET['orderID'];
?>

<form method="post" action="accept_gift.php">
    <input type="hidden" name="orderID" value="<?= htmlspecialchars($orderID) ?>">
    <label>Enter Billing Address:</label>
    <textarea name="billAddress" class="form-control" required></textarea>
    <button type="submit" class="btn btn-primary mt-2">Submit</button>
</form>

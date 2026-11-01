<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "autoassemblyDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if(isset($_GET['categoryID'])){
    $categoryID=$_GET['categoryID'];
}
session_start();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: mainpage.php");
    exit;
}
?>
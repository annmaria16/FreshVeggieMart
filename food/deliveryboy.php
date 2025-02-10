<?php
require_once 'config.php';
session_start();
// Check if the delivery boy is logged in, if not then redirect him to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery_boy') {
    header("Location: login.php");
    exit();
}
?>
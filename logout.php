<?php
session_start();
$_SESSION = array();//unset()
session_destroy();
header("Location: login.php");
exit();
?>
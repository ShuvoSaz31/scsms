<?php session_start();
session_unset();
session_destroy();
header("Location: /scsms_V1/index.php");
exit();

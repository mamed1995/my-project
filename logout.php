<?php
// logout.php
session_start();
$_SESSION = array(); // تفريغ جميع متغيرات الجلسة
session_destroy(); // تدمير الجلسة
header("Location: login.php");
exit;
?>
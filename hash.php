<?php
// hash.php (ملف مؤقت للتشفير)
$password = '123456'; 
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
echo $hashed_password;
?>
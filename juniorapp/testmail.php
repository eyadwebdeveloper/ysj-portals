<?php
include 'mail_functions.php';
$result = sendVerificationEmail('eyad6ashraf@gmail.com', 'Test User', '123456');
var_dump($result);
?>
<?php
require_once '../config/auth.php';
requerir_login(['admin_hotel','admin']);
header('Location: conecting.php');
exit;

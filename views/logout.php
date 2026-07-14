<?php
require_once '../config/Database.php';
require_once '../config/auth.php';

$database = new Database();
$db = $database->connect();
if ($db) {
    cerrar_sesion_db($db);
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
?>

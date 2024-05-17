<?php
include_once '../config/config.php';
require_once '../vendor/autoload.php';
require_once './route.php';


/* Cors */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

/* Conecction to BD => MySQL */
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    routeApi($pdo);
} catch (PDOException $e) {
    $error = [
        "message" => "Error de conexión: " . $e->getMessage()
    ];
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode($error);
    exit;
}


?>
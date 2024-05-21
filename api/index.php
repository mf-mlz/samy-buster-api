<?php
require_once '../vendor/autoload.php';
require_once './route.php';
use Dotenv\Dotenv;

$rootPath = realpath(dirname(__DIR__));
$envPath = $rootPath . '/.env';
$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->load();

$DB_HOST = $_ENV['DB_HOST'];
$DB_USER = $_ENV['DB_USER'];
$DB_PASS = $_ENV['DB_PASS'];
$DB_NAME = $_ENV['DB_NAME'];

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
    $pdo = new PDO("mysql:host=" . $DB_HOST . ";dbname=" . $DB_NAME, $DB_USER, $DB_PASS);
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
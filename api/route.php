<?php
require_once './users/users.php';
require_once './movies/movies.php';
require_once './buys/buys.php';
require_once './jtw.php';
require_once './tcpdf.php';
require_once '../vendor/autoload.php';
use Dotenv\Dotenv;

function routeApi($pdo)
{
    
    $rootPath = realpath(dirname(__DIR__));
    $dotenv = Dotenv::createImmutable($rootPath);
    $dotenv->load();
    $SECRET = $_ENV['SECRET'];
    
    $rute = explode('/', $_SERVER['REQUEST_URI']);
    $routeGet = strstr($rute[3], '?', true);
    $jwtAuthorization = getTokenFromHeader();
    $route = $routeGet ? $routeGet : $rute[3];
    if ($route !== 'registerUser' && $route !== 'loginUser' && $route !== 'createTicketBuy') {

        if (!$jwtAuthorization) {
            http_response_code(401);
            echo json_encode(["message" => "No hay Cabecera de Autenticacion"]);
            exit;
        }

        $decodeJWT = verifyJWT($jwtAuthorization, $SECRET, ['HS256']);
        if ($decodeJWT === null) {
            http_response_code(401);
            echo json_encode(["message" => "Token Inválido"]);
            exit;
        }
    }
    switch ($route) {
        case 'registerUser':
            registerUser($pdo);
            break;
        case 'loginUser':
            loginUser($pdo);
            break;
        case 'getUsersRegisterMonth':
            getUsersRegisterMonth($pdo);
            break;
        case 'getMovies':
            getMovies($pdo);
            break;
        case 'getMovie':
            getMovie($pdo);
            break;
        case 'addMovie':
            addMovie($pdo);
            break;
        case 'editMovie':
            editMovie($pdo);
            break;
        case 'deleteMovie':
            deleteMovie($pdo);
            break;
        case 'incrementVisitMovie':
            incrementVisitMovie($pdo);
            break;
        case 'registerBuy':
            registerBuy($pdo);
            break;
        case 'getBuys':
            getBuys($pdo);
            break;
        case 'getMoviesMostBuy':
            getMoviesMostBuy($pdo);
            break;
        case 'getMoviesMostVisit':
            getMoviesMostVisit($pdo);
            break;
        case 'getMoviesMostCalification':
            getMoviesMostCalification($pdo);
            break;
        case 'calificateMovie':
            calificateMovie($pdo);
            break;
        case 'createTicketBuy':
            createTicketBuy($pdo);
            break;
        default:
            http_response_code(404);
            echo json_encode(["message" => "Ruta no encontrada"]);
            break;
    }

}
?>
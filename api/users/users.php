<?php
require_once './functions.php';
require_once '../vendor/autoload.php';
use Dotenv\Dotenv;

/* Method POST  */
function loginUser($pdo)
{

    $rootPath = dirname(__DIR__, 2); 
    $dotenv = Dotenv::createImmutable($rootPath);
    $dotenv->load();
    $SECRET = $_ENV['SECRET'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['email', 'password'];
        $email = $_POST['email'] ? $_POST['email'] : null;
        $password = $_POST['password'] ? $_POST['password'] : null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Insert Data => users */
        $stmt = $pdo->prepare("SELECT users.*, roles.type as role FROM users INNER JOIN roles ON users.id_role = roles.id WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($user)) {
            /* Verify Password */
            $verifyPassword = passwordVerify($password, $user['password']);
            if ($verifyPassword) {
                $token = generateSessionToken($user, $SECRET);
                $data = array();
                $data['token'] = $token;
                $data['id'] = $user['id'];
                $data['role'] = $user['role'];
                http_response_code(200);
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "La contraseña es incorrecta"]);
            }

        } else {
            http_response_code(404);
            echo json_encode(["message" => "El email $email no se encuentra registrado"]);
        }


    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function registerUser($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['name', 'lastname', 'lastnamep', 'age', 'phone', 'email', 'password', 'address', 'id_role'];

        $name = $_POST['name'] ? $_POST['name'] : null;
        $lastname = $_POST['lastname'] ? $_POST['lastname'] : null;
        $lastnamep = $_POST['lastnamep'] ? $_POST['lastnamep'] : null;
        $age = $_POST['age'] ? $_POST['age'] : null;
        $phone = $_POST['phone'] ? $_POST['phone'] : null;
        $email = $_POST['email'] ? $_POST['email'] : null;
        $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $address = $_POST['address'] ? $_POST['address'] : null;
        $id_role = $_POST['id_role'] ? $_POST['id_role'] : null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Insert Data => users */
        $stmt = $pdo->prepare("INSERT INTO users (name, lastname, lastnamep, age, phone, email, password, address, id_role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $lastname, $lastnamep, $age, $phone, $email, $password, $address, $id_role]);

        if ($stmt->rowCount()) {
            http_response_code(201);
            echo json_encode(["message" => "Usuario Registrado correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al registrar el Usuario"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}


/* Method => GET */
function getUsersRegisterMonth($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $currentYear = date('Y');
        $stmt = $pdo->prepare("SELECT MONTH(created_at) AS month, COUNT(*) AS total FROM users WHERE id_role = 1 AND YEAR(created_at) = :year GROUP BY MONTH(created_at)");
        $stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $array_clients = array();


        for ($i = 1; $i <= 12; $i++) {

            $total = array_reduce($clients, function ($carry, $item) use ($i) {
                return $item['month'] == $i ? intval($item['total']) : $carry;
            }, null);

            $total = $total ? $total : 0;
            array_push($array_clients, $total);

        }

        http_response_code(200);
        echo json_encode($array_clients);


    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}

/* Functions */
function verifyUser($pdo, $idUser)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$idUser]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
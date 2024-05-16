<?php

include_once '../config/database.php';

/* Conecction to BD => MySQL */
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error = [
        "message" => "Error de conexión: " . $e->getMessage()
    ];
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode($error);
    exit;
}

/* Basic Routing API */
$rute = explode('/', $_SERVER['REQUEST_URI']);
if (isset($rute[3])) {
    switch ($rute[3]) {
        case 'registerUser':
            registerUser($pdo);
            break;
        case 'loginUser':
            loginUser($pdo);
            break;
        default:
            http_response_code(404);
            echo json_encode(["message" => "Ruta no encontrada"]);
            break;
    }

} else {
    http_response_code(404);
    echo json_encode(["message" => "Ruta Inválida"]);
    exit;
}


/* Functions API REST */
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
            echo json_encode(["message" => "Usuario Registrado con correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al registrar el Usuario"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function loginUser($pdo)
{
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
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($user)) {
            /* Verify Password */
            $verifyPassword = passwordVerify($password, $user['password']);
            if ($verifyPassword) {
                http_response_code(200);
                echo json_encode($user);
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

/* Function for verify data */
function verifyData($required_fields, $data)
{
    /* Verify Data Complete */
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $data) || empty($data[$field])) {
            return $field;
        }
    }
}

function passwordVerify($password, $passwordHash)
{
    $response = password_verify($password, $passwordHash) ? 1 : 0;
    return $response;
}

?>
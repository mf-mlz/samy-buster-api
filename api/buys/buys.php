<?php

require_once './movies/movies.php';
require_once './users/users.php';
require_once './functions.php';
/* Method => POST */
function registerBuy($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['id_user', 'id_movie', 'card_number', 'name_card'];

        $idUser = $_POST['id_user'] ? $_POST['id_user'] : null;
        $idMovie = $_POST['id_movie'] ? $_POST['id_movie'] : null;
        $card_number = $_POST['card_number'] ? $_POST['card_number'] : null;
        $name_card = $_POST['name_card'] ? $_POST['name_card'] : null;
        
        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Verify => Exists User and Movie */
        $verifyUser = verifyUser($pdo, $idUser);
        $verifyMovie = verifyMovie($pdo, $idMovie);

        if (empty($verifyUser)) {
            http_response_code(404);
            echo json_encode(["message" => "El Usuario No Existe"]);
            exit;
        }

        if (empty($verifyMovie)) {
            http_response_code(404);
            echo json_encode(["message" => "La Pelicula No Existe"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT price, inventary FROM movies WHERE id = ?");
        $stmt->execute([$idMovie]);
        $dataMovie = $stmt->fetch(PDO::FETCH_ASSOC);
        $price = $dataMovie['price'];
        $inventary = $dataMovie['inventary'] ? (int) $dataMovie['inventary'] : 0;

        if (empty($price)) {
            http_response_code(404);
            echo json_encode(["message" => "El Precio no se encuentra disponible"]);
            exit;
        }

        if ($inventary === 0) {
            http_response_code(404);
            echo json_encode(["message" => "No hay inventario para realizar la compra"]);
            exit;
        }

        /* Register Buy */
        $stmtBuy = $pdo->prepare("INSERT INTO buys (id_user, id_movie, total, card_number, name_card ) VALUES (?, ?, ?, ?, ?)");
        $stmtBuy->execute([$idUser, $idMovie, $price, $card_number, $name_card]);
        $lastBuy = $pdo->lastInsertId();
        if ($stmtBuy->rowCount()) {
            /* Edit Inventary Movie */
            $currentllyInventary = $inventary - 1;
            $inventoryPut = putInventoryMovie($pdo, $idMovie, $currentllyInventary);

            if ($inventoryPut) {
                http_response_code(200);
                echo json_encode(["message" => "Compra Registrada Correctamente", "idBuy" => $lastBuy ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Ocurrió un Error al Registrar la Compra"]);
            }

        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrio un error al registrar la compra"]);
        }


    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

/* Method GET */
function getBuys($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        /* Verify key _GET */
        $required_fields = ['id'];

        $id = $_GET['id'] ? $_GET['id'] : null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_GET);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT DISTINCT id_movie FROM buys WHERE id_user = ?");
        $stmt->execute([$id]);

        $stmt->execute();
        $buys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($buys);

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function getDataBuy($pdo, $id_buy)
{
   
        $stmt = $pdo->prepare("SELECT card_number, name_card, total as monto, CONCAT(users.name, ' ', users.lastnamep, ' ', lastname) as nameUser, users.email as emailUser, users.phone as phoneUser, users.address as addressUser, movies.title as nameMovie, movies.duration as durationMovie, movies.genre as genreMovie FROM buys INNER JOIN users ON users.id = buys.id_user INNER JOIN movies ON movies.id = buys.id_movie  WHERE buys.id = ?");
        $stmt->execute([$id_buy]);

        $stmt->execute();
        $dataBuy = $stmt->fetch(PDO::FETCH_ASSOC);

        return $dataBuy;

}

?>
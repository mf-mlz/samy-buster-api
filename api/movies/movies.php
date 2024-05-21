<?php
require_once './functions.php';

/* Mthod => POST */
function addMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['title', 'description', 'duration', 'year', 'autor', 'genre', 'calification', 'link', 'photo', 'inventary', 'price'];

        $title = $_POST['title'] ? $_POST['title'] : null;
        $description = $_POST['description'] ? $_POST['description'] : null;
        $duration = $_POST['duration'] ? $_POST['duration'] : null;
        $year = $_POST['year'] ? $_POST['year'] : null;
        $autor = $_POST['autor'] ? $_POST['autor'] : null;
        $genre = $_POST['genre'] ? $_POST['genre'] : null;
        $calification = $_POST['calification'] ? $_POST['calification'] : null;
        $link = $_POST['link'] ? $_POST['link'] : null;
        $photo = $_POST['photo'] ? $_POST['photo'] : null;
        $inventary = $_POST['inventary'] ? $_POST['inventary'] : null;
        $price = $_POST['price'] ? $_POST['price'] : null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["message" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Insert Data => users */
        $stmt = $pdo->prepare("INSERT INTO movies (title, description, duration, year, autor, genre, visits, calification, link, photo, inventary, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $duration, $year, $autor, $genre, 0, $calification, $link, $photo, $inventary, $price]);

        if ($stmt->rowCount()) {
            http_response_code(201);
            echo json_encode(["message" => "Pelicula Registrada correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al registrar la Pelicula"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function calificateMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /* Verify key _POST */
        $required_fields = ['id_user', 'type', 'id_movie'];

        $id_user = $_POST['id_user'] ? $_POST['id_user'] : null;
        $type = $_POST['type'] ? $_POST['type'] : null;
        $id_movie = $_POST['id_movie'] ? $_POST['id_movie'] : null;


        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_POST);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["message" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Insert Data => users */
        $stmtSelect = $pdo->prepare("SELECT calification FROM movies WHERE id = ?");
        $stmtSelect->execute([$id_movie]);
        $base = $stmtSelect->fetch(PDO::FETCH_ASSOC);
        $newCalification = calculatePercent($type, $base['calification']);

        $stmtPut = $pdo->prepare("UPDATE movies SET calification = :calification WHERE id = :id");
        $stmtPut->bindParam(':id', $id_movie);
        $stmtPut->bindParam(':calification', $newCalification);
        $stmtPut->execute();


        if ($stmtPut->rowCount()) {

            /* Insert Calification Movie */
            $stmt = $pdo->prepare("INSERT INTO califications (id_user, id_movie, type) VALUES (?, ?, ?)");
            $stmt->execute([$id_user, $id_movie, $type]);


            if ($stmt->rowCount()) {
                http_response_code(200);
                echo json_encode(["message" => "Calificación registrada con éxito"]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Ocurrió un error al registrar la Calificación"]);
            }


        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al modificar la Calificación"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

/* Mthod => GET */

function getMovies($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $sentence = '';
        $whereSentence = '';

        if (count($_GET) > 0) {

            foreach ($_GET as $key => $value) {
                $whereSentence .= "$key = :$key AND ";
            }
            $whereSentence = rtrim($whereSentence, ' AND ');
            $sql = "SELECT * FROM movies WHERE " . $whereSentence;
            $stmt = $pdo->prepare($sql);
            foreach ($_GET as $key => &$value) {
                $stmt->bindParam($key, $value);
            }

        } else {
            $whereSentence = '';
            $stmt = $pdo->prepare("SELECT * FROM movies");
        }


        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);


        http_response_code(200);
        echo json_encode($movies);


    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}

function getMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        /* Verify key _POST */
        $required_fields = ['id_movie', 'id_user'];

        $id_movie = $_GET['id_movie'] ? $_GET['id_movie'] : null;
        $id_user = $_GET['id_user'] ? $_GET['id_user'] : null;

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $_GET);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["message" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Select Data  */
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$id_movie]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtCalification = $pdo->prepare("SELECT count(*) as total FROM califications WHERE id_user = ? AND id_movie = ?");
        $stmtCalification->execute([$id_user, $id_movie]);
        $califications = $stmtCalification->fetch(PDO::FETCH_ASSOC);
        $movie['califications'] = $califications['total'];

        http_response_code(200);
        echo json_encode($movie);


    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function getMoviesMostBuy($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $buysArray = array();
        $titlesArray = array();
        $colorsArray = array();
        $stmt = $pdo->prepare("SELECT count(*) as buys, id_movie, movies.title FROM buys INNER JOIN movies ON movies.id = buys.id_movie GROUP BY id_movie ORDER BY buys DESC");
        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;

        if (count($movies) > 0) {
            for ($i = 0; $i < count($movies); $i++) {

                if ($count == 3) {
                    break;
                }

                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);
                $color = '#' . dechex($red) . dechex($green) . dechex($blue);

                array_push($buysArray, $movies[$i]['buys']);
                array_push($titlesArray, $movies[$i]['title']);
                array_push($colorsArray, $color);

                $count++;

            }
        }

        $reportBuysArray = array($buysArray, $titlesArray, $colorsArray);
        http_response_code(200);
        echo json_encode($reportBuysArray);

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}

function getMoviesMostVisit($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $visitsArray = array();
        $titlesArray = array();
        $colorsArray = array();
        $stmt = $pdo->prepare("SELECT visits, title FROM movies ORDER BY visits DESC");
        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;

        if (count($movies) > 0) {
            for ($i = 0; $i < count($movies); $i++) {

                if ($count == 3) {
                    break;
                }

                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);
                $color = '#' . dechex($red) . dechex($green) . dechex($blue);

                array_push($visitsArray, $movies[$i]['visits']);
                array_push($titlesArray, $movies[$i]['title']);
                array_push($colorsArray, $color);

                $count++;

            }
        }

        $reportVisitsArray = array($visitsArray, $titlesArray, $colorsArray);
        http_response_code(200);
        echo json_encode($reportVisitsArray);

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}

function getMoviesMostCalification($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $calificationArray = array();
        $titlesArray = array();
        $colorsArray = array();
        $stmt = $pdo->prepare("SELECT calification, title FROM movies ORDER BY calification DESC");
        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;

        if (count($movies) > 0) {
            for ($i = 0; $i < count($movies); $i++) {

                if ($count == 3) {
                    break;
                }

                $red = rand(0, 255);
                $green = rand(0, 255);
                $blue = rand(0, 255);
                $color = '#' . dechex($red) . dechex($green) . dechex($blue);

                array_push($calificationArray, $movies[$i]['calification']);
                array_push($titlesArray, $movies[$i]['title']);
                array_push($colorsArray, $color);

                $count++;

            }
        }

        $reportCalificationArray = array($calificationArray, $titlesArray, $colorsArray);
        http_response_code(200);
        echo json_encode($reportCalificationArray);

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}


/* Mthod => PUT */
function editMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

        /* Verify key _PUT */
        $required_fields = ['id', 'description', 'title', 'duration', 'year', 'autor', 'genre', 'calification', 'link', 'photo', 'inventary', 'price'];

        $putData = file_get_contents("php://input");
        $data = array();
        parse_str($putData, $data);

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $data);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Insert Data => users */
        $stmt = $pdo->prepare("UPDATE movies SET title = :title, description = :description, duration = :duration, year = :year, autor = :autor, genre = :genre, calification = :calification, link = :link, photo = :photo, inventary = :inventary, price = :price WHERE id = :id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':duration', $data['duration']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':autor', $data['autor']);
        $stmt->bindParam(':genre', $data['genre']);
        $stmt->bindParam(':calification', $data['calification']);
        $stmt->bindParam(':link', $data['link']);
        $stmt->bindParam(':photo', $data['photo']);
        $stmt->bindParam(':inventary', $data['inventary']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->execute();


        if ($stmt->rowCount()) {
            http_response_code(201);
            echo json_encode(["message" => "Pelicula Modificada correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al modificar la Pelicula"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}

function incrementVisitMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

        /* Verify key _PUT */
        $required_fields = ['id'];

        $putData = file_get_contents("php://input");
        $data = array();
        parse_str($putData, $data);

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $data);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT visits FROM movies WHERE id = ?");
        $stmt->execute([$data['id']]);
        $visit = $stmt->fetch(PDO::FETCH_ASSOC);

        /* Increment visit */
        $countVisits = isset($visit['visits']) ? $visit['visits'] + 1 : 0;

        $stmt = $pdo->prepare("UPDATE movies SET visits = :visits WHERE id = :id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':visits', $countVisits);
        $stmt->execute();

        if ($stmt->rowCount()) {
            http_response_code(201);
            echo json_encode(["message" => "Visita Registrada Correctamente"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Ocurrió un error al Registrar la Visita"]);
        }



    } else {
        http_response_code(405);
        echo json_encode(["message" => "Metodo no permitido"]);
    }
}


/* Mthod => DELETE */
function deleteMovie($pdo)
{
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

        /* Verify key _DELETE */
        $required_fields = ['id'];

        parse_str(file_get_contents("php://input"), $data);

        /* Verify Data Complete */
        $verifyData = verifyData($required_fields, $data);

        if ($verifyData) {
            http_response_code(400);
            echo json_encode(["mensaje" => "El campo $verifyData es obligatorio"]);
            exit;
        }

        /* Delete Data => movies */
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = :id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->execute();

        if ($stmt->rowCount()) {
            http_response_code(200);
            echo json_encode(["message" => "Pelicula eliminada correctamente"]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "La película no fue encontrada"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido"]);
    }
}


/* Functions */
function putInventoryMovie($pdo, $idMovie, $inventary)
{
    $stmt = $pdo->prepare("UPDATE movies SET inventary = :inventary WHERE id = :id");
    $stmt->bindParam(':id', $idMovie);
    $stmt->bindParam(':inventary', $inventary);
    $stmt->execute();

    return $stmt->rowCount();
}

function verifyMovie($pdo, $idMovie)
{
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$idMovie]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculatePercent($type, $base)
{

    $percentBase = 0.05;
    $percentCalculateBase = $base * $percentBase;
    $newCalification = null;
    if ($type == 'down') {
        $newCalification = $base - $percentBase;
    } else if ($type == 'up') {
        $newCalification = $base + $percentBase;
    }

    return $newCalification;
}

?>
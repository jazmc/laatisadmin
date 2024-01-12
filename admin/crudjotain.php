<?php
session_start();
require 'tk_kredentiaalit.php';
$method = null;
$table = null;
$idname = null;
$id = null;
$imploded = array();
$keys = array();
$keyvals = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION) && $_SESSION['koodi'] === $koodi) {

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo $e->getMessage();
        die("<br>Tietokantayhteyden muodostus epäonnistui");
    }


    if (!empty($_POST['table']) && $conn) {
        if (!empty($_POST['method'])) {
            $method = htmlspecialchars($_POST['method']);
        } else {
            echo json_encode(array("poistettu" => false, "lisatty" => false, "muokattu" => false, "haettu" => false, "error" => "ei valtuuksia"));
            $conn = null;
            die();
        }
        if (!empty($_POST['table'])) {
            $table = htmlspecialchars($_POST['table']);
        }
        if (!empty($_POST['idname'])) {
            $idname = htmlspecialchars($_POST['idname']);
        }
        if (!empty($_POST['id'])) {
            $id = htmlspecialchars($_POST['id']);
        }
        if (!empty($_POST['keys'])) {
            $keys = $_POST['keys'];
        }
        if (!empty($_POST['keyvals'])) {
            $keyvals = $_POST['keyvals'];
        }

        if ($method != "delete" && $method != "select") {
            foreach ($keys as $key) {
                $newval = htmlspecialchars($key) . " = ";
                if ($keyvals[$key] == null || $keyvals[$key] == NULL || $keyvals[$key] == 'null') {
                    $newval .= "NULL";
                } else {
                    $newval .= "'" . htmlspecialchars($keyvals[$key]) . "'";
                }
                array_push($imploded, $newval);
            }
            $imploded = implode(", ", $imploded);
        }

        if ($method == 'select' && !empty($keys)) {
            foreach ($keys as $key) {
                $newval = htmlspecialchars($key);
                array_push($imploded, $newval);
            }
            $imploded = implode(", ", $imploded);
        }

        $stmt = null;

        try {

            if ($method == "update") {
                $stmt = $conn->prepare("UPDATE $table SET $imploded WHERE $idname = (?);");
                if ($stmt->execute([$id])) {
                    echo json_encode(array("muokattu" => true));
                } else {
                    echo json_encode(array("error" => $conn->errorInfo()));
                }
            } else if ($method == "delete") {
                $stmt = $conn->prepare("DELETE FROM $table WHERE $idname = (?);");
                if ($stmt->execute([$id])) {
                    echo json_encode(array("poistettu" => true));
                } else {
                    echo json_encode(array("error" => $conn->errorInfo()));
                }
            } else if ($method == "insert") {
                $stmt = $conn->prepare("INSERT INTO $table SET $imploded;");
                if ($stmt->execute()) {
                    echo json_encode(array("lisatty" => true));
                } else {
                    echo json_encode(array("error" => $conn->errorInfo()));
                }
            } else if ($method == "select") {
                $stmt = null;
                if (!empty($imploded) && $imploded != null) {
                    $stmt = $conn->prepare("SELECT $imploded FROM $table WHERE $idname = (?);");
                } else {
                    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idname = (?);");
                }

                if ($stmt->execute([$id])) {
                    $kaikkidata = $stmt->fetchAll();
                    echo json_encode(array("haettu" => true, "data" => $kaikkidata));
                } else {
                    echo json_encode(array("error" => $conn->errorInfo()));
                }
            }
        } catch (PDOException $e) {
            header('500 Internal Server Error', true, 500);
            echo json_encode(array("error" => $conn->errorInfo(), "info" => $e->getMessage()));
        }
    }
} else {
    echo json_encode(array("poistettu" => false, "lisatty" => false, "muokattu", false, "haettu" => false, "error" => "ei valtuuksia"));
}

$conn = null;

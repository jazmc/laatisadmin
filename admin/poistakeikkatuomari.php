<?php
session_start();
require 'tk_kredentiaalit.php';
$riviid = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo $e->getMessage();
        die("<br>Tietokantayhteyden muodostus epäonnistui");
    }


    if (!empty($_POST['riviid']) && $conn) {
        $riviid = htmlspecialchars($_POST['riviid']);

        $keikkikset = $conn->prepare("DELETE FROM Keikkatuomari WHERE Rivi_ID = (?)");
        if ($keikkikset->execute([$riviid])) {
            echo json_encode(array("poistettu" => true));
        } else {
            echo json_encode(array("poistettu" => false));
        }
    } else {
        echo json_encode(array("poistettu" => false));
    }
} else {
    echo json_encode(array("poistettu" => false));
}

$conn = null;

<?php
session_start();
require 'tk_kredentiaalit.php';
$textarea = $tilid = null;
$osallistujat = array();
$onnistuneet = array();
$epaonnistuneet = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION) && $_SESSION['koodi'] === $koodi) {

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo $e->getMessage();
        die("<br>Tietokantayhteyden muodostus epäonnistui");
    }


    if (!empty($_POST['palkinnotpisteet']) && $conn) {
        if (isset($_POST['palkinnotpisteet'])) {
            $textarea = trim($_POST['palkinnotpisteet']);
        } else {
            echo json_encode(array("error" => true));
            exit();
        }
        if (!empty($_POST['tilid'])) {
            $tilid = htmlspecialchars($_POST['tilid']);
        }

        $osallistujat = explode("\n", $textarea);
        $osallistujat = str_replace("\r", "", $osallistujat);

        $stmt = null;

        try {
            $stmt = $conn->prepare("UPDATE Osallistuminen SET Pisteet = (?), Palkinto = (?) WHERE Til_ID = (?) AND VH = (?);");

            foreach ($osallistujat as $str) {
                $rivi = explode(";", $str); // array VH;PISTEET;PALKINTO;NÖNÖNÖ

                $vhnro = htmlspecialchars(trim($rivi[0]));
                $raakapisteet = str_replace(",", ".", htmlspecialchars(trim($rivi[1])));
                $pisteet = number_format((float)$raakapisteet, 2, '.', '');
                $palkinto = htmlspecialchars(trim($rivi[2]));

                if ($stmt->execute([$pisteet, $palkinto, $tilid, $vhnro])) {
                    $onnistuneet[] = [$vhnro, $palkinto, $pisteet, $tilid];
                } else {
                    $epaonnistuneet[] = [$vhnro, $palkinto, $pisteet, $tilid];
                }
            }

            echo json_encode(array("onnistuneet" => $onnistuneet, "epäonnistuneet" => $epaonnistuneet, "error" => false));
        } catch (PDOException $e) {
            header('500 Internal Server Error', true, 500);
            echo json_encode(array("error" => $conn->errorInfo(), "info" => $e->getMessage()));
        }
    }
} else {
    echo json_encode(array("error" => true, "viesti" => "ei valtuuksia"));
}

$conn = null;

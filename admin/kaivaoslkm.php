<?php
require 'tk_kredentiaalit.php';

$vrl = $til_id = $polku = null;
$tuomari = false;

if (isset($_GET['VRL'])) {
    $vrl = $_GET['VRL'];
}
if (isset($_GET['Til_ID'])) {
    $til_id = $_GET['Til_ID'];
}
if (isset($_GET['Tuomari'])) {
    $tuomari = $_GET['Tuomari'];
}


try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT Varahevonen FROM Osallistuminen WHERE VRL = (?) AND Til_ID = (?) ORDER BY Varahevonen DESC");
    $stmt->execute([$vrl, $til_id]);
    $osumat = $stmt->fetchAll();

    $paa = 0;

    $vara = 0;

    $paahevosiaon = 0;
    $varahevosiaon = 0;
    foreach ($osumat as $o) {
        if ($o['Varahevonen'] == '1') {
            $varahevosiaon += 1;
        } else if ($o['Varahevonen'] == '0') {
            $paahevosiaon += 1;
        }
    }

    if (count($osumat) > 0) {
        // osallistumisia löytyi
        if ($tuomari != "false") {
            // on tuomari
            $paa = $tuomarienhevosmaara - count($osumat);
            $vara = 0; // tuomari ei tarvitse varahevosia
            $polku = "Tuomari, oli aiempia osallistumisia";
        } else {
            // ei ole tuomari
            $paa = $tavallistenhevosmaara - $paahevosiaon;
            $vara = $varahevosmaara - $varahevosiaon;
            $polku = "Ei tuomari, oli aiempia osallistumisia";
        }
    } else {
        // osallistumisia ei vielä ollut tietokannassa
        if ($tuomari != "false") {
            $paa = $tuomarienhevosmaara;
            $vara = 0;
            $polku = "Tuomari, ei aiempia osallistumisia";
        } else {
            $paa = $tavallistenhevosmaara;
            $vara = $varahevosmaara;
            $polku = "Ei tuomari, ei aiempia osallistumisia";
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo json_encode(array($paa, $vara, $polku));

$conn = null;

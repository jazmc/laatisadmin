<?php

if (empty($_POST)) {
    die("Väärä metodi");
}

require 'tk_kredentiaalit.php';

$alueet = [];
$vrl = null;
$til_id = null;
$email = null;

if (!empty($_POST['keikkaVRL'])) {
    $vrl = $_POST['keikkaVRL'];
}
if (!empty($_POST['email'])) {
    $email = $_POST['email'];
}
if (!empty($_POST['keikkatilaisuus'])) {
    $til_id = $_POST['keikkatilaisuus'];
}
if (!empty($_POST['alue'])) {
    $alueet = $_POST['alue'];
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $haealueet = $conn->prepare("SELECT * FROM Alue;");
    $haealueet->execute();

    $osiot = $haealueet->fetchAll();
    $osioluntti = array();

    foreach ($osiot as $o) {
        $osioluntti[$o['Alue_ID']] = $o['Otsikko'];
    }

    $stmt = $conn->prepare("INSERT INTO Keikkatuomari SET VRL = (?), Sahkoposti = (?), Til_ID = (?), Alueet = (?);");


    if ($stmt->execute([$vrl, $email, $til_id, json_encode($alueet)])) {
        echo "Keikkatuomariksi ilmoittautuminen onnistui. Kiitos aktiivisuudestasi! <b>Olemme sinuun yhteydessä sähköpostitse lähempänä tilaisuutta.</b><br><br>Siirrytään takaisin tilaisuussivulle...";

        $formcontent = "Ilmoittautuminen keikkatuomariksi:\n\n
            Tilaisuus_ID: " . $til_id . "\nSähköposti: " . $email
            . "\nVRL-" . $vrl . "\nValitut alueet: ";

        foreach ($alueet as $a) {
            $formcontent .= $osioluntti[$a] . " ";
        }

        $recipient = $laatisemail;
        $subject = "Keikkatuomari-ilmoittautuminen";
        mail($recipient, $subject, $formcontent) or die("Sähköposti-ilmoittautuminen epäonnistui. Ota yhteys ylläpitoon!");


        header("refresh:5;url=$tilaisuussivu");
    } else {
        echo "Keikkatuomariksi ilmoittautuminen epäonnistui! Ota yhteys ylläpitoon.<br><br>Siirrytään takaisin tilaisuussivulle...";
        header("refresh:5;url=$tilaisuussivu");
    };
} catch (PDOException $e) {
    echo $e->getMessage();
}

$conn = null;

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


    if (!empty($_POST['jalkiilmo']) && $conn) {
        if (isset($_POST['jalkiilmo'])) {
            $textarea = trim($_POST['jalkiilmo']);
        } else {
            echo json_encode(array("error" => true));
            exit();
        }
        if (!empty($_POST['tilid'])) {
            $tilid = htmlspecialchars($_POST['tilid']);
        }

        $osallistujat = explode("\n", $textarea);

        $stmt = null;

        try {
            $onkojo = $conn->prepare("SELECT Os_ID FROM Osallistuminen WHERE Til_ID = (?) AND VH = (?);");
            $onkoheppaa = $conn->prepare("SELECT Nimi FROM Hevonen WHERE VH = (?);");
            $paivitahevonen = $conn->prepare("UPDATE Hevonen SET Rotu = (?), Nimi = (?) WHERE VH = (?);");
            $paivitaosall = $conn->prepare("UPDATE Osallistuminen SET Poikkeukset = (?), Pisteet = (?), Palkinto = (?) WHERE Til_ID = (?) AND VH = (?);");
            $lisaahevonen = $conn->prepare("INSERT INTO Hevonen SET VH = (?), Nimi = (?), ROTU = (?);");
            $lisaaosall = $conn->prepare("INSERT INTO Osallistuminen SET VH = (?), VRL = '00000', Skp = (?), Til_ID = (?), Linkki = (?), Varahevonen = '0', Poikkeukset = (?), Pisteet = (?), Palkinto = (?);");

            foreach ($osallistujat as $str) {
                if (trim($str) == "") {
                    continue;
                }
                str_replace("\r", "", $rivi);
                $rivi = explode(";", $str); // array VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET

                $vhnro = htmlspecialchars(trim($rivi[0]));
                $raakapisteet = str_replace(",", ".", htmlspecialchars(trim($rivi[1])));
                $pisteet = number_format((float) $raakapisteet, 2, '.', '');
                $palkinto = htmlspecialchars(trim($rivi[2]));
                $rotuskp = htmlspecialchars(trim($rivi[3]));
                $rotuskparray = explode("-", $rotuskp);
                $rotu = trim($rotuskparray[0]);
                $skp = str_replace(".", "", trim($rotuskparray[1]));
                $nimi = htmlspecialchars(trim($rivi[4]));
                $linkki = htmlspecialchars(trim($rivi[5]));
                $poikkeukset = htmlspecialchars(trim($rivi[6]));

                if (substr_count($str, ';') != 6) {
                    $epaonnistuneet[] = [$str];
                    continue;
                }
                $onkojo->execute([$tilid, $vhnro]);
                if (!empty($onkojo->fetch())) {
                    // on jo tilaisuudessa: päivitä hevonen ja osallistuminen
                    if (!$paivitahevonen->execute([$rotu, $nimi, $vhnro])) {
                        $epaonnistuneet[] = [$str];
                        continue;
                    }
                    if (!$paivitaosall->execute([$poikkeukset, $pisteet, $palkinto, $tilid, $vhnro])) {
                        $epaonnistuneet[] = [$str];
                        continue;
                    }
                    $onnistuneet[] = [$str];
                } else {
                    // ei oo tilaisuudessa, onko hevonen jo tietokannassa
                    $onkoheppaa->execute([$vhnro]);
                    if (!empty($onkoheppaa->fetch())) {
                        // on: päivitä heppa
                        if (!$paivitahevonen->execute([$rotu, $nimi, $vhnro])) {
                            $epaonnistuneet[] = [$str];
                            continue;
                        }
                    } else {
                        // ei: lisää uutena hevonen-tauluun
                        if (!$lisaahevonen->execute([$vhnro, $nimi, $rotu])) {
                            $epaonnistuneet[] = [$str];
                            continue;
                        }
                    }
                    // lisää osallistuminen
                    if (!$lisaaosall->execute([$vhnro, $skp, $tilid, $linkki, "(Ylläpidon lisäämä) " . $poikkeukset, $pisteet, $palkinto])) {
                        $epaonnistuneet[] = [$str];
                        continue;
                    }
                    $onnistuneet[] = [$str];
                }
            }

            echo json_encode(array("onnistuneet" => $onnistuneet, "epaonnistuneet" => $epaonnistuneet, "error" => false));
            $_SESSION['Epaonnistuneet'] = array("klo" => date('d.m.Y H:i:s'), "epaonnistuneet" => $epaonnistuneet);
        } catch (PDOException $e) {
            header('500 Internal Server Error', true, 500);
            echo json_encode(array("error" => $conn->errorInfo(), "info" => $e->getMessage()));
        }
    }
} else {
    echo json_encode(array("error" => true, "viesti" => "ei valtuuksia"));
}

$conn = null;

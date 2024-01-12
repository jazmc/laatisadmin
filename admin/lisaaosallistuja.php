<?php
session_start();
require 'tk_kredentiaalit.php';

// muuttujien alustus
$vh = $nimi = $vrl = $til_id = $edellinentil = $linkki = $rotu = $skp = $poikkeukset = $varahevonen = "";
$olitilaa = false;
$osallistujat = array();

$palautusviesti = "";

print_r($_POST);
echo "<br><br>";

if (isset($_POST['os'])) {
    $osallistujat = $_POST['os'];
} else {
    echo "Osallistujien tunnistaminen epäonnistui";
    exit();
}

if (isset($_POST['VRL'])) {
    $vrl = htmlspecialchars($_POST['VRL']);
} else {
    echo "VRL epäonnistui";
    exit();
}

if (isset($_POST['tilaisuusid'])) {
    $til_id = htmlspecialchars($_POST['tilaisuusid']);
} else {
    echo "Tilaisuus epäonnistui";
    exit();
}

$vh = $nimi = $rotu = $skp = $linkki = $varahevonen = $poikkeukset = null;

// tietokantayhteyden avaus    
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tilaisuudet = $conn->prepare("SELECT * FROM Tilaisuus WHERE Til_ID <= (?) ORDER BY Til_ID DESC LIMIT 2");
    $tilaisuudet->execute([$til_id]);
    $tamatil = $tilaisuudet->fetch();
    $edellinentil = $tilaisuudet->fetch();

    if (empty($edellinentil)) {
        $edellinentil = array("Til_ID" => null);
    }

    $hlisays = $conn->prepare("INSERT INTO Hevonen(VH, Nimi, Rotu) VALUES (?,?,?)");

    $hpaivitys = $conn->prepare("UPDATE Hevonen SET Nimi = (?), Rotu = (?) WHERE VH = (?)");

    $htark = $conn->prepare("SELECT VH FROM Hevonen WHERE VH = (?)");

    $htiltark = $conn->prepare("SELECT VH FROM Osallistuminen WHERE VH = (?) AND Til_ID = (?)");


    $ostark = $conn->prepare("SELECT VH FROM Osallistuminen WHERE VRL = (?) AND Til_ID = (?)");

    // onko aktiivinen tuomari tai edellisen tilaisuuden keikkatuomari
    $onkotuomari = $conn->prepare("SELECT alt.VRL FROM AlueidenTuomarit alt 
		LEFT JOIN Tuomari t ON alt.VRL = t.VRL
		LEFT JOIN TuomareidenTauot tt ON tt.VRL = alt.VRL 
		WHERE (alt.VRL = (?) AND (tt.Alku IS NULL OR tt.Alku >= CURRENT_DATE OR (tt.Alku <= CURRENT_DATE AND tt.Loppu <= CURRENT_DATE)))
		AND (tt.Rivi_ID = ( 
            SELECT MAX(Rivi_ID) FROM TuomareidenTauot 
            WHERE VRL = alt.VRL) OR tt.Rivi_ID IS NULL)
        UNION
        SELECT kei.VRL FROM Keikkatuomari kei
        WHERE (kei.VRL = (?) AND kei.Til_ID = (?));");

    // tavallisten osallistujijen varsinaiset osallistujat
    $taystarkistus = $conn->prepare("SELECT O.VRL, T.Maxos FROM Osallistuminen O
    JOIN Tilaisuus T ON O.Til_ID = T.Til_ID WHERE O.Til_ID = (?) AND O.Varahevonen = '0' AND O.VRL NOT IN (
            SELECT alt.VRL FROM AlueidenTuomarit alt 
		    LEFT JOIN Tuomari t ON alt.VRL = t.VRL
		    LEFT JOIN TuomareidenTauot tt ON tt.VRL = alt.VRL 
		    WHERE (tt.Alku IS NULL OR tt.Alku >= CURRENT_DATE OR (tt.Alku <= CURRENT_DATE AND tt.Loppu <= CURRENT_DATE))
            UNION
            SELECT kei.VRL FROM Keikkatuomari kei
            WHERE kei.Til_ID = (?))");

    $oslisays = $conn->prepare("INSERT INTO Osallistuminen(VH, VRL, Til_ID, Linkki, Skp, Varahevonen, Poikkeukset) VALUES (?,?,?,?,?,?,?)");

    // loopataan post-arrayt
    foreach ($osallistujat as $osall) {

        $vh = htmlspecialchars($osall['VH']);
        $nimi = htmlspecialchars($osall['Nimi']);
        $rotu = htmlspecialchars($osall['Rotu']);
        $skp = htmlspecialchars($osall['Skp']);
        $linkki = htmlspecialchars($osall['Linkki']);
        $varahevonen = htmlspecialchars($osall['Varahevonen']);
        $poikkeukset = htmlspecialchars($osall['Poikkeukset']);

        // onko tuomari
        $onkotuomari->execute([$vrl, $vrl, $edellinentil['Til_ID']]);

        // montako osallistumista
        $ostark->execute([$vrl, $til_id]);
        $osall = $ostark->fetchAll();
        $osallistumisia = count($osall);
        $palautusviesti .= "VRL:lle " . $vrl . " löydettiin " . $osallistumisia . " aiempaa osallistumista tilaisuuteen.\n";

        $htark->execute([strtoupper($vh)]);
        $onksheppaa = $htark->fetchAll();

        // jos heppa ei ole vielä tietokannassa
        if (count($onksheppaa) < 1) {
            // yritä lisäystä
            if (!$hlisays->execute([strtoupper($vh), $nimi, $rotu])) {
                $palautusviesti .= "Hevonen " . $nimi . " löytyi jo tietokannasta.\n";
                if (!$hpaivitys->execute([$nimi, $rotu, strtoupper($vh)])) {
                    $palautusviesti .= "Hevosen " . $vh . " tietojen päivitys epäonnistui.\n";
                }
            }
        }

        // jos heppa on jo tietokannassa:
        if (count($onkotuomari->fetchAll()) > 0 && $osallistumisia < $tuomarienhevosmaara) {
            // on tuomari, lisää
            if (!$oslisays->execute([strtoupper($vh), $vrl, $til_id, $linkki, $skp, $varahevonen, $poikkeukset])) {
                $palautusviesti .= "Hevosen " . $vh . " lisääminen tilaisuuteen epäonnistui.\n";
            } else {
                $palautusviesti .= "Hevonen " . $vh . " lisättiin onnistuneesti tilaisuuteen.\n";
            }
        } else {
            // ei ole tuomari tai tuomari mutta liikaa osallistumisia
            $taystarkistus->execute([$til_id, $edellinentil['Til_ID']]);
            $oslkm = $taystarkistus->fetchAll();

            $olitilaa = (count($oslkm) >= 0 && ((!empty($oslkm[0]['Maxos']) && count($oslkm) < $oslkm[0]['Maxos']) || count($oslkm) == "0"));

            if (
                ($osallistumisia <= $tavallistenhevosmaara && $varahevonen == "0") ||
                ($osallistumisia < $tavallistenhevosmaara + $varahevosmaara && $varahevonen == "1") ||
                ($osallistumisia == $tavallistenhevosmaara && $varahevonen == "1")
            ) {

                if ($olitilaa) {
                    $palautusviesti .= "Tilaisuudessa oli tilaa.\n";
                } else {
                    $palautusviesti .= "Tilaisuus oli täynnä!! Lisätään hevonen jonoon peruutuspaikkojen tai osallistujakapasiteetin noston varalta.\n";
                }

                // onko jo tilaisuudessa
                $htiltark->execute([strtoupper($vh), $til_id]);
                if (count($htiltark->fetchAll()) < 1) {
                    // lisää
                    if (!$oslisays->execute([$vh, $vrl, $til_id, $linkki, $skp, $varahevonen, $poikkeukset])) {
                        $palautusviesti .= "Hevosen " . $nimi . " lisääminen tilaisuuteen epäonnistui.\n";
                    } else if (!$olitilaa) {
                        $palautusviesti .= "Hevonen " . $nimi . " lisättiin onnistuneesti tilaisuuden jonoon.\n";
                    } else {
                        $palautusviesti .= "Hevonen " . $nimi . " lisättiin onnistuneesti tilaisuuteen.\n";
                    }
                } else {
                    $palautusviesti .= "Hevonen " . $nimi . " oli jo tässä tilaisuudessa.\n";
                }
            }
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
$conn = null;

$_SESSION['Palautusviesti'] = "[ILMOITTAUTUMINEN " . date('d.m. H:i:s', time()) . "]:\n " . $palautusviesti;

if ($olitilaa) {
    header("Location: $tilaisuussivu");
} else {
    header("Location: " . $tilaisuussivu . "?error=full");
}

die();

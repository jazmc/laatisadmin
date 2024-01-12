<?php
require 'tk_kredentiaalit.php';

$vrl = $tuomari = null;
$tilid = 9999;

if (isset($_GET['VRL'])) {
    $vrl = $_GET['VRL'];
}

if (isset($_GET['tilid'])) {
    $tilid = $_GET['tilid'];
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tilaisuudet = $conn->prepare("SELECT * FROM Tilaisuus WHERE Til_ID <= (?) ORDER BY Til_ID DESC LIMIT 2");
    $tilaisuudet->execute([$tilid]);
    $tamatil = $tilaisuudet->fetch();
    $edellinentil = $tilaisuudet->fetch();

    if (empty($edellinentil)) {
        $edellinentil = array("Til_ID" => null);
    }

    $stmt = $conn->prepare("SELECT alt.VRL FROM AlueidenTuomarit alt 
		LEFT JOIN Tuomari t ON alt.VRL = t.VRL
		LEFT JOIN TuomareidenTauot tt ON tt.VRL = alt.VRL 
		WHERE (alt.VRL = (?) AND (tt.Alku IS NULL OR tt.Alku > CURRENT_DATE OR (tt.Alku < CURRENT_DATE AND tt.Loppu < CURRENT_DATE)))
		AND (tt.Rivi_ID = ( 
            SELECT MAX(Rivi_ID) FROM TuomareidenTauot 
            WHERE VRL = alt.VRL) OR tt.Rivi_ID IS NULL)
        UNION
        SELECT kei.VRL FROM Keikkatuomari kei
        WHERE (kei.VRL = ? AND kei.Til_ID = ?);");
    $stmt->execute([$vrl, $vrl, $edellinentil['Til_ID']]);

    if (count($stmt->fetchAll()) > 0) {
        $tuomari = true;
    } else {
        $tuomari = false;
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo json_encode(array("tuomari" => $tuomari));

$conn = null;

<?php
require 'tk_kredentiaalit.php';

$vrl = $tuomari = null;
$tilid = 9999;
$aluearr = array();
$taukoarr = array();
$alueidt = array();

if (isset($_GET['muokattavatuomari'])) {
    $vrl = $_GET['muokattavatuomari'];
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT t.* FROM Tuomari t
		WHERE (t.VRL = ?);");

    if ($stmt->execute([$vrl])) {
        $tuomari = $stmt->fetch();

        $alueet = $conn->prepare("SELECT a.*, alt.Paatoimisuus from Alue a 
LEFT JOIN AlueidenTuomarit alt ON a.Alue_ID = alt.Alue_ID AND alt.VRL = (?) ORDER BY a.Jarjestys ASC, a.Alue_ID ASC;");

        $alueet->execute([$vrl]);

        $alueinfo = $alueet->fetchAll();

        foreach ($alueinfo as $a) {
            $aluearr[$a['Alue_ID']] =  $a['Paatoimisuus'];
            array_push($alueidt, $a['Alue_ID']);
        }

        $tauot = $conn->prepare("SELECT tt.* FROM TuomareidenTauot tt
            WHERE tt.VRL = (?) 
            AND ((tt.Alku >= CURRENT_DATE) OR (tt.Alku <= CURRENT_DATE AND tt.Loppu >= CURRENT_DATE) OR (tt.Alku <= CURRENT_DATE AND tt.Loppu IS NULL));");

        $tauot->execute([$vrl]);
        $taukoarr = $tauot->fetchAll();
    } else {
        $tuomari = false;
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo json_encode(array("tuomari" => $tuomari, "alueidt" => $alueidt, "alueet" =>  $aluearr, "tauot" => $taukoarr));

$conn = null;

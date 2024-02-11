<?php

function haeAiemmat($vh, $til)
{
    require 'tk_kredentiaalit.php';

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



        $stmt = $conn->prepare("SELECT T.Tulokset, T.Pvm FROM Tilaisuus T JOIN Osallistuminen O ON O.Til_ID = T.Til_ID WHERE O.VH = (?) AND O.Til_ID != (?)");
        $stmt->execute([$vh, $til]);
        $aiemmat = $stmt->fetchAll();

        if (!empty($aiemmat)) {
            foreach ($aiemmat as $a) {
                echo "<a href=\"" . $a['Tulokset'] . "\">" . date('m', strtotime($a['Pvm'])) . "/" . date('y', strtotime($a['Pvm'])) . "</a>, ";
            }
        }
    } catch (PDOException $e) {
        echo "Aiempien tulosten haku epäonnistui.<br>" . $sql . "<br>" . $e->getMessage();
    }

    $conn = null;
}



function haeOsallistujataulukko($tiettytilaisuus = '0', $admintaulukko = false)
{
    require 'tk_kredentiaalit.php';

    $kuukausi = array("01" => "Tammikuu", "02" => "Helmikuu", "03" => "Maaliskuu", "04" => "Huhtikuu", "05" => "Toukokuu", "06" => "Kesäkuu", "07" => "Heinäkuu", "08" => "Elokuu", "09" => "Syyskuu", "10" => "Lokakuu", "11" => "Marraskuu", "12" => "Joulukuu");

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tilsu = $conn->prepare("SELECT T.*, COUNT(O.Os_ID) as Oslkm, COUNT(IF(Varahevonen='1', 1, NULL)) as Varahevosia, COUNT(IF(Varahevonen='0', 1, NULL)) as Osallistujia FROM Osallistuminen O 
	JOIN Tilaisuus T ON T.Til_ID = O.Til_ID 
    WHERE T.Til_ID <= '$tiettytilaisuus' 
    GROUP BY T.Til_ID
    ORDER BY T.Pvm DESC;");

        $tilsu->execute();
        $tils = $tilsu->fetchAll();
        $til = $tils[0];
        $edellinentil = $tils[1];

        if (empty($edellinentil)) {
            $edellinentil = array("Til_ID" => "0");
        }

        /*if (!$admintaulukko && date('Y-m', strtotime($til['Pvm'])) != date('Y-m')) {
            echo "<table><tr><td colspan=\"" . (!$admintaulukko ? "4" : "5") . "\">Ei avoinna olevaa tilaisuutta juuri nyt</td></tr></table>";
            return;
        }*/


        // jos tilaisuus on valmis, älä tulosta taulukkoa
        if (!$admintaulukko && !empty($til) && $til['Tulokset'] != NULL) {
            echo "<table";
            echo (!$admintaulukko ? "class=\"mt-4\"" : " style=\"margin-top:0;\"");
            echo "><tr><td colspan=\"" . (!$admintaulukko ? "4" : "5") . "\">Tilaisuuden tulokset tulleet</td></tr></table>";
            return;
        } else if (empty($til) && ($tiettytilaisuus == 0 || $tiettytilaisuus == "")) {
            echo "<table";
            echo (!$admintaulukko ? "class=\"mt-4\"" : " style=\"margin-top:0;\"");
            echo "><tr><td colspan=\"" . (!$admintaulukko ? "4" : "5") . "\">Ei tiedossa olevaa seuraavaa tilaisuutta</td></tr></table>";
            return;
        } else if (empty($til) || $til['Oslkm'] < 1 || $til['Til_ID'] != $tiettytilaisuus) {
            echo "<table";
            echo (!$admintaulukko ? "class=\"mt-4\"" : " style=\"margin-top:0;\"");
            echo "><tr><td colspan=\"" . (!$admintaulukko ? "4" : "5") . "\">Ei osallistujia</td></tr></table>";
            return;
        }

        $maksimi = $til['Maxos'];
        $varahevosia = $til['Varahevosia'];
        $osallistujia = $til['Osallistujia'];

        // tavallisten osallistujien osallistumiset
        $sql = "SELECT O.*, H.*, T.* FROM Osallistuminen O
            JOIN Hevonen H ON H.VH = O.VH
            JOIN Tilaisuus T ON T.Til_ID = O.Til_ID
        WHERE O.Til_ID = (?) 
        AND O.VRL NOT IN 
            (SELECT alt.VRL FROM AlueidenTuomarit alt 
		    LEFT JOIN Tuomari t ON alt.VRL = t.VRL
		    LEFT JOIN TuomareidenTauot tt ON tt.VRL = alt.VRL 
		    WHERE (tt.Alku IS NULL OR tt.Alku >= CURRENT_DATE OR (tt.Alku <= CURRENT_DATE AND tt.Loppu <= CURRENT_DATE))
            UNION
            SELECT kei.VRL FROM Keikkatuomari kei
            WHERE kei.Til_ID = (?))
        ORDER BY O.Varahevonen ASC, O.Os_ID";

        if (!$admintaulukko) {
            $sql .= " LIMIT $maksimi";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([$til['Til_ID'], $edellinentil['Til_ID']]);

        $rows = $stmt->fetchAll();
        // tuomareiden osallistumiset
        $stmt2 = $conn->prepare("SELECT O.*, H.*, T.* FROM Osallistuminen O
            JOIN Hevonen H ON H.VH = O.VH
            JOIN Tilaisuus T ON T.Til_ID = O.Til_ID
        WHERE T.Til_ID = (?)
        AND O.VRL IN (
            SELECT alt.VRL FROM AlueidenTuomarit alt 
		    LEFT JOIN Tuomari t ON alt.VRL = t.VRL
		    LEFT JOIN TuomareidenTauot tt ON tt.VRL = alt.VRL 
		    WHERE (tt.Alku IS NULL OR tt.Alku >= CURRENT_DATE OR (tt.Alku <= CURRENT_DATE AND tt.Loppu <= CURRENT_DATE))
            UNION
            SELECT kei.VRL FROM Keikkatuomari kei
            WHERE kei.Til_ID = (?))
        ");
        $stmt2->execute([$til['Til_ID'], $edellinentil['Til_ID']]);
        $th = $stmt2->fetchAll();

        echo "<table rel=\"" . $til['Til_ID'] . "\" ";
        echo (!$admintaulukko ? "class=\"mt-4\"" : " style=\"margin-top:0;\"");
        echo ">";
        if (!empty($til)) {
            echo "<t" . (!$admintaulukko ? "r><th" : "r class=\"ots-dark\"><td") . " colspan=\"" . (!$admintaulukko ? "3" : "4\" style=\"font-size: small; padding-top: 0.2em; padding-bottom: 0.2em;\"") . "\">" . (!empty($til['Otsikko']) ? $til['Otsikko'] : $kuukausi[date('m', strtotime($til['Pvm']))] . "n tilaisuus") . ", " . date('d.m.Y', strtotime($til['Pvm']));
            echo " (" . $osallistujia - count($th) . " + " . count($th) . " / " . $til['Maxos'] . " os.)";
            echo "</th></tr>";
        }

        if (count($rows) < 1 && count($th) < 1) {
            echo "<tr><td colspan=\"" . (!$admintaulukko ? "3" : "4") . "\">Ei osallistujia</td></tr></table>";
            return;
        } else if (count($rows) < 1) {
            echo "<tr><td colspan=\"" . (!$admintaulukko ? "3" : "4") . "\">Ei varsinaisia osallistujia</td></tr>";
        }

        $ossumma = 0;
        $nthrow = 1;

        foreach ($rows as $r) {
            echo "<tr><td";
            echo $admintaulukko ? " style=\"min-width:20rem;\"" : "";
            echo ">";
            if ($admintaulukko) {
                echo $nthrow > $til['Maxos'] ? "<i class=\"mr-2 text-warning fas fa-exclamation-triangle\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Ei mahtumassa mukaan tilaisuuteen\"></i>" : "";
            }
            echo $r['Rotu'] . "-" . $r['Skp'] . ". ";
            echo "<a href=\"" . $r['Linkki'] . "\" target=\"new\">";
            echo $r['Nimi'] . "</a>";
            if ($r['Varahevonen'] == "1") {
                echo " <i>Varahevonen</i>";
            }
            echo "</td><td";
            echo $admintaulukko ? " class=\"small\"" : "";
            echo ">";
            haeAiemmat($r['VH'], $r['Til_ID']);
            echo $r['Poikkeukset'] . "</td><td style=\"text-align:right;min-width: 10rem;\">";
            echo $r['VH'] . "</td>";

            if ($admintaulukko) {
                echo "<td style=\"text-align:right;\">";
                echo "<i class=\"text-danger fas fa-trash-alt\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Poista osallistuja\" style=\"cursor:pointer;\" onclick=\"poistaOsallistuja(" . $r['Os_ID'] . ")\"></i></td>";
            }

            echo "</tr>";

            if ($r['Varahevonen'] == "0") {
                $ossumma += 1;
            }
            $nthrow++;
        }

        echo "<t" . (!$admintaulukko ? "r><th" : "r class=\"ots-dark\"><td") . " colspan=\"" . (!$admintaulukko ? "3" : "4\" style=\"font-size: small; padding-top: 0.2em; padding-bottom: 0.2em;\"") . "\">Tuomareiden hevoset (" . count($th) . " kpl)</th></tr>";


        if (count($th) < 1) {
            echo "<tr><td colspan=\"" . (!$admintaulukko ? "3" : "4") . "\">Ei hevosia</td><tr>";
        } else {

            foreach ($th as $r) {
                echo "<tr><td>" . $r['Rotu'] . "-" . $r['Skp'] . ". ";
                echo "<a href=\"" . $r['Linkki'] . "\" target=\"new\">";
                echo $r['Nimi'] . "</a>";

                echo "</td><td>";
                haeAiemmat($r['VH'], $r['Til_ID']);
                echo $r['Poikkeukset'] . "</td><td style=\"text-align:right; min-width:10em;\">";
                echo $r['VH'] . "</td>";

                echo (!$admintaulukko ? "" : "<td style=\"text-align:right;\"><i class=\"text-danger fas fa-trash-alt\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Poista osallistuja\" style=\"cursor:pointer;\" onclick=\"poistaOsallistuja(" . $r['Os_ID'] . ")\"></i></td>");

                echo "</tr>";
            }
        }


        echo "</table>";
        if (!$admintaulukko) {
            echo "<i>Varsinaisia osallistujia tällä hetkellä " . $ossumma . " kpl";
            if ($til['Oslkm'] > $til['Maxos']) {
                echo ", lisäksi jonossa ja varalla yhteensä " . $varahevosia . " hevosta";
            }
            echo ". Varahevoset arvostellaan ilmoittautumisjärjestyksessä vain, jos tilaisuus jää osallistujamäärältään vajaaksi</i>\r\n";
            if ($ossumma >= $til['Maxos']) {
                echo "<script>
                $(\"#taynna\").append(\"<p>" . (!empty($til['Otsikko']) ? $til['Otsikko'] : $kuukausi[date('m', strtotime($til['Pvm']))] . "n tilaisuus") . " on täynnä. Vain tuomareiden hevoslisäykset menevät enää läpi.</p>\");
                </script>";
            }
        }
    } catch (PDOException $e) {
        echo "<table><tr><td colspan=\"" . (!$admintaulukko ? "4" : "5") . "\">Taulukon haku epäonnistui.<br>" . $e->getMessage() . "</td></tr></table>";
    }

    $conn = null;
}
<?php
// hae seuraava tilaisuus, jota ei ole vielä suljettu
$seuraavatil = $sitaseuraava = $avoimet = array();

try {
    // tilaisuudet joissa ilmo on auki tai jos missään ei oo nii sit seuraavat tilaisuudet
    $stmt = $conn->prepare("SELECT * FROM Tilaisuus 
    WHERE IlmoLoppu >= CURRENT_DATE OR Pvm >= CURRENT_DATE
    AND Valmis IS NULL 
    ORDER BY IlmoAlku ASC, IlmoLoppu ASC, Pvm ASC;");
    $stmt->execute();

    $avoinnaolevat = $stmt->fetchAll();

    if (!empty($avoinnaolevat[0])) {
        $seuraavatil = $avoinnaolevat[0];
    }
    if (!empty($avoinnaolevat[1])) {
        $sitaseuraava = $avoinnaolevat[1];
    }

    foreach ($avoinnaolevat as $til) {
        if ($til['IlmoAlku'] <= date('Y-m-d') && $til['IlmoLoppu'] >= date('Y-m-d')) {
            $avoimet[$til['Til_ID']] = array("Pvm" => $til['Pvm'], "Otsikko" => $til['Otsikko']);
        }
    }
} catch (PDOException $e) {
    echo $e->getMessage();
    die("<br>Tietokantayhteyden muodostus epäonnistui");
}

// jos seuraavaa tilaisuutta ei ole tiedossa
if (empty($seuraavatil)) { ?>

    <h2>Ei tiedossa olevaa seuraavaa tilaisuutta</h2>
    <p>Ota yhteys ylläpitoon sähköpostitse tiedustellaksesi seuraavan tilaisuuden aikataulua.</p>
<?php }

// jos seuraavan tilaisuuden ilmoittautumisaika ei ole vielä alkanut 
else if (!empty($seuraavatil) && date('Y-m-d') < date('Y-m-d', strtotime($seuraavatil['IlmoAlku']))) { ?>

        <h2>Ei avoimia tilaisuuksia juuri nyt</h2>
        <p>Ilmoittautuminen seuraavaan tilaisuuteen aukeaa
        <?php echo strtolower($kuukausi[date('m', strtotime($seuraavatil['IlmoAlku']))]) . "n " . date('j', strtotime($seuraavatil['IlmoAlku'])); ?>.
            päivänä.
        </p>
    <?php
    // jos seuraavan tilaisuuden ilmoaika on mennyt jo umpeen
} else if (!empty($seuraavatil) && date('Y-m-d') > date('Y-m-d', strtotime($seuraavatil['IlmoLoppu']))) { ?>

            <h2>Ilmoittautuminen,
        <?php if (count($avoimet) <= 1) {
            echo (!empty($seuraavatil['Otsikko']) ? $seuraavatil['Otsikko'] : strtolower($kuukausi[date('m', strtotime($seuraavatil['Pvm']))]) . "n tilaisuus");
            echo " &ndash; VIP ";
            echo (date('Y-m-d') == $seuraavatil['IlmoLoppu']) ? "tänään!" : date('d.m.', strtotime($seuraavatil['IlmoLoppu']));
        } else if (count($avoimet) > 1) {
            echo "useita tilaisuuksia avoinna";
        }
        ?>
            </h2>
            <p>Ilmoittautuminen on sulkeutunut. Ilmoittautuminen seuraavaan tilaisuuteen aukeaa
        <?php echo strtolower($kuukausi[date('m', strtotime($sitaseuraava['IlmoAlku']))]) . "n " . date('j', strtotime($sitaseuraava['IlmoAlku'])); ?>.
                päivänä.
            </p>
    <?php
    // jos seuraavan tilaisuuden ilmoittautumisaika on nyt menossa
} else { // SIIRRÄ TAKAS TÄHÄN KU VALMISTA 
    ?>

            <h2>Ilmoittautuminen,
        <?php if (count($avoimet) == 1) {
            echo (!empty($seuraavatil['Otsikko']) ? $seuraavatil['Otsikko'] : strtolower($kuukausi[date('m', strtotime($seuraavatil['Pvm']))]) . "n tilaisuus");
            echo " &ndash; VIP ";
            echo (date('Y-m-d') == $seuraavatil['IlmoLoppu']) ? "tänään!" : date('d.m.', strtotime($seuraavatil['IlmoLoppu']));
        } else if (count($avoimet) > 1) {
            echo "useita tilaisuuksia avoinna";
        }
        ?>
            </h2>
            <form action="admin/lisaaosallistuja.php" method="POST" class="mt-4">
                <div class="row">
                    <div class="col-4 form-group" style="min-width: 11em;">
                        <select class="custom-select" name="tilaisuusid" id="tilaisuusid">
                            <option selected disabled value="">(valitse tilaisuus)</option>
                    <?php foreach ($avoimet as $id => $a) {
                        echo "<option value=\"" . $id . "\"";
                        echo (count($avoimet) == 1 ? " selected" : "");
                        echo ">";
                        echo ($a['Otsikko'] != null ? $a['Otsikko'] . " " . date('d.m.', strtotime($a['Pvm'])) : date('m/Y', strtotime($a['Pvm'])));
                        echo "</option>";
                    } ?>
                        </select>
                    </div>
                    <div class="col input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">VRL-</span>
                        </div>
                        <input type="text" id="VRL" name="VRL" class="form-control" placeholder="00000" aria-label="00000"
                            aria-describedby="basic-addon1">
                        <div class="input-group-append">
                            <button class="btn btn-success" id="aloita" type="button" onclick="tarkistaTuomarius()" disabled>Aloita
                                ilmoittautuminen</button>
                        </div>
                    </div>
                </div>
                <div id="taynna" style="color:red; font-weight:bold;"></div>
                <div id="hallintanapit"></div>
                <div id="paahevoset"></div>
                <div id="varahevoset"></div>
                <div id="submitnappi" style="margin-top:2em;"></div>
            </form>
    <?php if (isset($_SESSION['Palautusviesti'])) {
        echo "<p class=\"text-info\">" . nl2br($_SESSION['Palautusviesti']) . "</p>";
    } ?>
    <?php if (isset($_GET['error'])) {
        echo "<span style=\"color:red\">Valitettavasti tilaisuus tuli täyteen, eikä kaikkia hevosia pystytty lisäämään!</span>";
    } ?>
<?php } ?>

<hr />
<?php
foreach ($avoimet as $aid => $a) {
    echo "<h2>Ilmoittautuneet, ";
    echo (!empty($a['Otsikko']) ? $a['Otsikko'] : strtolower($kuukausi[date('m', strtotime($a['Pvm']))]) . "n tilaisuus");
    echo "</h2>";
    haeOsallistujataulukko($aid);
    echo "<hr/>";
}

?>
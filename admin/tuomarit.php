<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = null;
$tilid = null;
$osallistujia = 0;
$uploaded = false;
$tuoms = array();


require_once "../" . $headerurl;
?>
<?php include 'adminlinkit.php'; ?>

<h1>Tuomareiden hallinta</h1>
<?php if (!empty($_SESSION['koodi']) && $_SESSION['koodi'] === $koodi) { ?>
    <b>Aktiivisten päätuomareiden sähköpostiosoitteet tuomarointisähköpostia varten:</b>
    <p>
        <?php
        try {

            $stmt = $conn->prepare("SELECT a.Paatoimisuus, t.Sahkoposti, tt.Rivi_ID, tt.Alku, tt.Loppu, 
(CASE
  WHEN (Alku <= CURRENT_DATE AND (Loppu IS NULL OR Loppu >= CURRENT_DATE)) THEN '1'
  ELSE '0'
END) as Taukoilee
FROM Tuomari t
LEFT JOIN TuomareidenTauot AS tt ON tt.VRL = t.VRL
LEFT JOIN AlueidenTuomarit a ON a.VRL = t.VRL
WHERE a.Paatoimisuus = '1'
AND 
   (tt.Rivi_ID = (
      SELECT Rivi_ID
      FROM TuomareidenTauot
      WHERE VRL = t.VRL
       AND Alku <= CURRENT_DATE 
	ORDER BY Alku DESC, Rivi_ID DESC
      LIMIT 1
   ) OR tt.Rivi_ID IS NULL)
   GROUP BY t.VRL;");
            $stmt->execute();
            $tuoms = $stmt->fetchAll();
            $tuomarit = array();

            if (count($tuoms) > 0) {
                foreach ($tuoms as $t) {
                    if ($t['Taukoilee'] != "1") {
                        array_push($tuomarit, $t['Sahkoposti']);
                    }
                }
                $tuomarit = implode(", ", $tuomarit);

                echo $tuomarit;
            } else {
                echo "Ei aktiivisia päätuomareita";
            }
        } catch (PDOException $e) {
            echo "Tuomareiden haku epäonnistui";
        }
        ?>
    </p>

    <hr />

    <h2 data-toggle="collapse" data-target="#lisaac" aria-expanded="false" aria-controls="lisaac">Lisää uusi tuomari <i
            class="fas fa-caret-down"></i></h2>
    <div class="collapse mt-4" id="lisaac">
        <div class="row mb-3">
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">VRL-</span>
                </div>
                <input type="text" id="ut-VRL" name="VRL" pattern="[0-9]{5}" class="form-control" placeholder="00000"
                    aria-label="00000">
            </div>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-address-book"></i></span>
                </div>
                <input type="text" class="form-control" id="ut-nimimerkki" required placeholder="Nimimerkki" />
            </div>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" class="form-control" id="ut-email" required placeholder="sahkoposti@domain.com" />
            </div>
        </div>
        <div class="row">
            <?php
            try {

                $stmt = $conn->prepare("SELECT * FROM Alue ORDER BY Jarjestys ASC, Alue_ID ASC");
                $stmt->execute();
                $alueet = $stmt->fetchAll();

                if (count($alueet) > 0) {
                    foreach ($alueet as $a) { ?>
                        <div class="col form-group" style="min-width: 11em;">
                            <label for="<?php echo strtolower($a['Otsikko']); ?>">
                                <?php echo $a['Otsikko']; ?>
                            </label>
                            <select class="custom-select" name="uusialue[<?php echo strtolower($a['Alue_ID']); ?>]"
                                id="<?php echo strtolower($a['Otsikko']); ?>">
                                <option selected value="">(ei tuomaroi)</option>
                                <option value="1">Päätuomari</option>
                                <option value="0">Varatuomari</option>
                            </select>
                        </div>
                        <?php
                    }
                } else { ?>
                    <div class="col form-group" style="min-width: 11em;">
                        <label for="dummyotsikko">Tuomaroitavat osa-alueet</label>
                        <select class="custom-select" name="dummyalue" id="dummyotsikko">
                            <option selected disabled value="">(ei yhtään osiota tietokannassa)</option>
                        </select>
                    </div>
                <?php }
            } catch (PDOException $e) {
                echo "Alueiden haku epäonnistui";
            }
            ?>
        </div>
        <input class="btn btn-success" id="lisaatuomari" type="submit" value="Lisää tuomari" disabled />
    </div>

    <hr />

    <h2 data-toggle="collapse" data-target="#muokkaac" aria-expanded="false" aria-controls="muokkaac">Muokkaa tuomaria <i
            class="fas fa-caret-down"></i></h2>
    <div class="collapse mt-4" id="muokkaac">
        <div class="row mb-3">
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                </div>
                <select class="custom-select" name="muokattavatuomari" id="muokattavatuomari">
                    <option selected disabled value="null">(valitse tuomari)</option>
                    <?php
                    try {

                        $stmt = $conn->prepare("SELECT * FROM Tuomari ORDER BY Nimimerkki ASC");
                        $stmt->execute();
                        $tuomarit = $stmt->fetchAll();

                        if (count($tuomarit) < 1) {
                            echo "<option selected disabled value=\"null\">(ei tuomareita tietokannassa)</option>";
                        }

                        foreach ($tuomarit as $t) { ?>
                            <option value="<?php echo $t['VRL']; ?>">
                                <?php echo $t['Nimimerkki'] . " VRL-" . $t['VRL']; ?>
                            </option>
                            <?php
                        }
                    } catch (PDOException $e) {
                        echo "<option>Tuomareiden haku epäonnistui</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-address-book"></i></span>
                </div>
                <input type="text" class="form-control" id="mt-nimimerkki" required placeholder="Nimimerkki" />
            </div>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" class="form-control" id="mt-email" required placeholder="sahkoposti@domain.com" />
            </div>
        </div>
        <div class="row">
            <?php
            try {

                $stmt = $conn->prepare("SELECT * FROM Alue ORDER BY Jarjestys ASC, Alue_ID ASC");
                $stmt->execute();
                $alueet = $stmt->fetchAll();

                if (count($alueet) < 1) { ?>
                    <div class="col form-group" style="min-width: 11em;">
                        <label for="dummyotsikko2">Tuomaroitavat osa-alueet</label>
                        <select class="custom-select" name="dummyalue" id="dummyotsikko2">
                            <option selected disabled value="">(ei yhtään osiota tietokannassa)</option>
                        </select>
                    </div>
                <?php }
                foreach ($alueet as $a) { ?>
                    <div class="col form-group" style="min-width: 11em;">
                        <label for="m-<?php echo strtolower($a['Otsikko']); ?>">
                            <?php echo $a['Otsikko']; ?>
                        </label>
                        <select class="custom-select" name="alue[<?php echo strtolower($a['Alue_ID']); ?>]"
                            id="m-<?php echo strtolower($a['Otsikko']); ?>">
                            <option selected value="">(ei tuomaroi)</option>
                            <option value="1">Päätuomari</option>
                            <option value="0">Varatuomari</option>
                        </select>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo "Alueiden haku epäonnistui";
            }
            ?>
        </div>
        <div class="row">
            <div class="col col-4">
                <label>Taukojaksot</label>
                <div id="taukojaksot"> (ei valittua tuomaria) </div>
            </div>
            <div class="col form-group">
                <label>Tauota tuomari</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">Alkaen</span>
                    </div>
                    <input type="date" class="form-control" id="taukoalku">
                </div>
            </div>
            <div class="col form-group">
                <label>Päättyminen / toistaiseksi</label>
                <div class="input-group mb-3">
                    <input type="date" class="form-control" id="taukoloppu">
                    <div class="btn-group-toggle input-group-append" data-toggle="buttons">
                        <label id="toistaiseksi" class="btn btn-outline-info"
                            onclick="if($(this).hasClass('active') === false){$(this).text('&#x2611; Toistaiseksi'); $('#taukoloppu').val('').prop('disabled', true); } else {$(this).text('&#x2610; Toistaiseksi'); $('#taukoloppu').prop('disabled', false);}">
                            <input type="checkbox" name="options" id="option1"> &#x2610; Toistaiseksi </label>
                    </div>
                </div>
            </div>
        </div>
        <input id="paivitatuomari" class="btn btn-info mr-2" type="button" value="Päivitä tuomarin tiedot" disabled />
        <input id="poistatuomari" class="btn btn-outline-danger" type="button" value="Poista tuomari"
            onclick="poistaTuomari();" disabled />
    </div>

    <hr />

    <h2 class="mb-4">Tuomaritaulukko</h2>
    <?php include_once 'tuomaritaulukko.php'; ?>

    <hr />

    <h2>Keikkatuomareiden hallinta</h2>
    <p>Poista ne jotka eivät hoitaneet tuomarointeja määräaikaan mennessä, jotta he eivät saa tuomarioikeuksia turhaan.
        Näytetään keikkatuomarit, joiden tilaisuus ei ole vielä valmis, tai on merkitty valmiiksi alle 1 kk sitten.</p>
    <?php
    // keikkatuomarit, 20 uusinta joiden tuomaroima tilaisuus on alle kk vanha

    if ($conn) {
        $keikkikset = $conn->prepare("SELECT kt.*, t.* FROM Keikkatuomari kt JOIN Tilaisuus t ON kt.Til_ID = t.Til_ID
            WHERE (t.Valmis IS NULL OR DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) < t.Valmis)
        ORDER BY kt.Til_ID ASC LIMIT 20");
        $keikkikset->execute();
        $ktt = $keikkikset->fetchAll();


        $haealueet = $conn->prepare("SELECT * FROM Alue;");
        $haealueet->execute();

        $osiot = $haealueet->fetchAll();
        $osioluntti = array();

        foreach ($osiot as $o) {
            $osioluntti[$o['Alue_ID']] = $o['Otsikko'];
        }

        if (count($ktt) < 1) {
            echo "<p><i>Ei relevantteja keikkatuomareita</i></p>";
        }

        foreach ($ktt as $kt) {
            $aluerimpsu = "";
            $alueetkannasta = substr(str_replace(["'", "\""], "", $kt['Alueet']), 1, -1);

            $tuomarinalueet = explode(",", $alueetkannasta);
            foreach ($tuomarinalueet as $a) {
                $aluerimpsu .= $osioluntti[$a] . " ";
            }


            echo "Tilaisuus " . date('m', strtotime($kt['Pvm'])) . "/" . date('y', strtotime($kt['Pvm'])) . ": <a href=\"mailto:" . $kt['Sahkoposti'] . "\">VRL-" . $kt['VRL'] . "</a>";
            echo "<button type=\"button\" class=\"mx-2 my-2 btn btn-sm btn-danger\" onclick=\"poistaKeikkatuomari(" . $kt['Rivi_ID'] . ");\">poista tuomarioikeudet</button> ( $aluerimpsu)<br>";
        }
    }
} else { ?>
    <p>Olet tekemässä ylläpitäjien toimintoja. Anna salasana.</p>
    <form method="POST" action="valtuuta.php">
        <input type="password" name="password">
        <input type="hidden" value="admin/tuomarit.php" name="paluu">
        <button class="btn btn-success" type="submit">Valtuuta</button>
    </form>
<?php }

$conn = null; ?>
<?php
require_once "../" . $footerurl; ?>
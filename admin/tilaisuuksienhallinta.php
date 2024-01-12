<?php
session_start();

$conn = null;
$tilid = null;
$osallistujia = 0;
$uploaded = false;

include 'haeosallistujataulukko.php';
require_once("../header.php");
?>
<?php include 'adminlinkit.php'; ?>

<h1>Tilaisuuksien hallinta</h1>
<?php if (!empty($_SESSION['koodi']) && $_SESSION['koodi'] === $koodi) { ?>

    <hr />

    <h2 data-toggle="collapse" data-target="#lahetac" aria-expanded="false" aria-controls="lahetac"> Lähetä tilaisuuden
        tulokset <i class="fas fa-caret-down"></i></h2>
    <?php
    error_reporting(E_ALL);

    // Check if image file is a actual image or fake image
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['tilaisuus'])) {

        // assign vars
        if (!empty($_POST['tilaisuus'])) {
            $tilid = htmlspecialchars($_POST['tilaisuus']);
        }
        if (!empty($_POST['osallistujia'])) {
            $osallistujia = htmlspecialchars($_POST['osallistujia']);
        }


        echo "<i class=\"fas fa-info-circle\"></i> YRITETÄÄN LATAUSTA<br>";

        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
            $filename = $_FILES['pdf']['name'];
            $filesize = $_FILES['pdf']['size'];
            $filetype = $_FILES['pdf']['type'];
            $uploadOk = 1;

            echo "<i class=\"fas fa-info-circle\"></i> Tiedostopolku" . $pdfkansio . $filename . "<br>";

            // Check if file already exists
            if (file_exists($pdfkansio . $filename)) {
                echo "<span style=\"color:red\"><i class=\"fas fa-exclamation-circle\"></i> Samanniminen tiedosto on jo ladattu palvelimelle.</span><br>";
                $uploadOk = 0;
                $uploaded = true;
            } else {
                echo "<i class=\"far fa-check-circle\"></i> Samannimistä tiedostoa ei vielä löytynyt palvelimelta.<br>";
            }


            // Check file size
            if ($filesize > 120000) {
                echo "<span style=\"color:red\"><i class=\"fas fa-exclamation-circle\"></i> Tiedoston maksimikoko on 120 KB.<br></span>";
                $uploadOk = 0;
            } else {
                echo "<i class=\"far fa-check-circle\"></i> Tiedostokoko OK.<br>";
            }

            // Allow certain file formats
            if ($filetype != "application/pdf") {
                echo "<span style=\"color:red\"><i class=\"fas fa-exclamation-circle\"></i> Vain PDF-tiedoston lataaminen on mahdollista.</span>";
                $uploadOk = 0;
            } else {
                echo "<i class=\"far fa-check-circle\"></i> Tiedostomuoto oli pdf eli OK.<br>";
            }

            // Check if $uploadOk is set to 0 by an error
            if ($uploadOk != 1) {
                echo "<i class=\"fas fa-exclamation-circle\"></i> Tiedoston lataaminen palvelimelle epäonnistui (syy ylempänä punaisella).<br>";
                // if everything is ok, try to upload file
            } else {
                "<i class=\"fas fa-info-circle\"></i> Aloitetaan tiedoston lataaminen palvelimelle.<br>";
                if (move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfkansio . $filename)) {
                    echo "<span style=\"color:green\"><i class=\"far fa-check-circle\"></i> Tiedosto " . htmlspecialchars(basename($_FILES["pdf"]["name"])) . " ladattiin palvelimelle onnistuneesti.</span><br>";
                    $uploaded = true;
                } else {
                    echo "<i class=\"fas fa-exclamation-circle\"></i> Valitettavasti tiedoston lataaminen epäonnistui.<br>";
                }
            }

            // liitä tiedosto tilaisuuteen
            if ($conn && $uploaded) {
                $pdfkansio_pitkaurl .= $filename;

                $stmt = $conn->prepare("UPDATE Tilaisuus 
                    SET Osallistujia = (?), Tulokset = (?), Valmis = CURDATE()
                    WHERE Til_ID = (?)");
                echo "<i class=\"fas fa-info-circle\"></i> Yritetään lisätä tiedosto " . $filename . " tilaisuuteen ID:llä " . $tilid . "<br>";
                if ($stmt->execute([$osallistujia, $pdfkansio_pitkaurl, $tilid])) {
                    echo "<span style=\"color:green\"><i class=\"far fa-check-circle\"></i> Tilaisuuden päivitys onnistui. Tulokset lähetetty tietokantaan onnistuneesti.  Päivitetään sivu 5 sekunnin kuluessa...</span><br>";
                    echo "<script>setTimeout(function(){
                           location.href = '';
                        }, 5000);</script>";
                } else {
                    echo "<span style=\"color:red\"><i class=\"fas fa-exclamation-circle\"></i> Tilaisuuden päivitys epäonnistui.</span>";
                }
            }
        }
    }
    ?>
    <div class="collapse mt-4" id="lahetac">
        <p>Voit lisätä LaatisAdminin kautta tilaisuudelle tulos-pdf:n kahdella eri tavalla. Jos tiedostoa ei ole vielä
            ladattu internetiin, voit tehdä sen tämän lomakkeen avulla. Jos tiedosto on jo netissä, voit linkittää
            tulos-pdf:n URL-osoitteen tilaisuuteen <a href="#tuloksettomat">Tuloksettomat tilaisuudet</a>-otsikon alta
            muokkaamalla kyseistä tilaisuutta.</p>
        <form id="pdfform" class="form mt-4 mb-5" method="POST" enctype="multipart/form-data">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="tilaisuusi">Tilaisuus</label>
                <div class="col">
                    <select class="custom-select" name="tilaisuus" id="tilaisuusi" required>
                        <?php

                        try {
                            $stmt = $conn->prepare("SELECT * FROM Tilaisuus 
        WHERE Valmis IS NULL 
        ORDER BY Pvm ASC;");
                            $stmt->execute();
                            $tils = $stmt->fetchAll();

                            if (count($tils) > 0) {
                                echo "<option selected disabled value=\"\">Valitse tilaisuus...</option>";
                            } else {
                                echo "<option selected disabled value=\"null\">(ei tuloksettomia tilaisuuksia tietokannassa)</option>";
                            }

                            foreach ($tils as $til) {
                                echo "<option value=\"" . $til['Til_ID'] . "\">" . date('m/Y', strtotime($til['Pvm'])) . " (ID: " . $til['Til_ID'] . ")</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=\"\">Tilaisuuksien haku epäonnistui</option>";
                        }


                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="osi">Osallistujien lkm</label>
                <div class="col">
                    <input type="text" name="osallistujia" id="osi" class="form-control" placeholder="Osallistujien lkm"
                        required aria-describedby="passwordHelpBlock">
                    <small id="passwordHelpBlock" class="form-text text-muted"> Voit määrittää osallistujamäärän laatiksesi
                        haluamalla tavalla. Lomake hyväksyy tekstiä ja numeroita. Esim. <code>20 + 3</code> tai
                        <code>23</code>
                    </small>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="pdfi">Tulosten pdf-tiedosto</label>
                <div class="col">
                    <input type="file" id="pdfi" name="pdf" size="50" required />
                </div>
            </div>
            <div class="mb-3">
                <label for="pdfpisteet">Osallistujakohtaiset tulokset: <code>VH;PISTEET;PALKINTO;KOMMENTTI</code> tai
                    <code>VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET</code></label>
                <textarea class="form-control" id="pdfpisteet"
                    placeholder="VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET" required></textarea>
                <div class="invalid-feedback"> Jokin tekstikentän tiedoista ei ole muotoonlaitettu oikein. Tarkista tiedot.
                </div>
                <small class="form-text text-muted">Syötä tähän Google Sheetsistä muotoonlaitetut osallistujarivit, niin
                    järjestelmä parsii tietojen joukosta pisteet ja palkinnot. Ei ole väliä, kummassa muodossa laitat
                    pisteet. Jos Sheetsissä on arvosteltu hevosia jotka eivät ilmoittautuneet tilaisuuteen tietokannan
                    kautta, lisääthän ne ensin <a href="#lisaapuuttuvia">Lisää puuttuvia osallistujia</a>-lomakkeen
                    avulla!</small>
            </div>
            <button id="lataapdf" class="btn btn-success" onclick="lataaPdf()" disabled>Lataa tulokset palvelimelle</button>
        </form>

        <h2>Muista myös tarkistaa keikkatuomarit:</h2>
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


                echo "Tilaisuus ";
                echo ($kt['Otsikko'] != null ? $kt['Otsikko'] . " " . date('d.m.y', strtotime($kt['Pvm'])) : date('m/Y', strtotime($kt['Pvm'])));
                echo ": <a href=\"mailto:" . $kt['Sahkoposti'] . "\">VRL-" . $kt['VRL'] . "</a>";
                echo "<button type=\"button\" class=\"mx-2 my-2 btn btn-sm btn-danger\" onclick=\"poistaKeikkatuomari(" . $kt['Rivi_ID'] . ");\">poista tuomarioikeudet</button> ( $aluerimpsu)<br>";
            }
        } ?>
    </div>

    <hr />

    <h2 id="alustauusi" data-toggle="collapse" data-target="#lisaac" aria-expanded="false" aria-controls="lisaac"> Alusta
        uusi tilaisuus <i class="fas fa-caret-down"></i></h2>
    <div class="collapse mt-4" id="lisaac">
        <div class="row">
            <label class="col-sm-2 col-form-label" for="til-otsikko">Otsikko</label>
            <div class="col input-group">
                <input type="text" id="til-otsikko" name="otsikko" class="form-control" placeholder="(ei pakollinen)"
                    aria-label="otsikko" aria-describedby="otsikkosel">
            </div>
            <label class="col-sm-2 col-form-label" for="til-maxos">Osallistujia (max)</label>
            <div class="col input-group">
                <input type="number" id="til-maxos" name="til-maxos" class="form-control" aria-label="max-os"
                    value="<?php echo $oletus_osallistujamaara; ?>" required>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-sm-2">
            </div>
            <div class="col input-group">
                <small id="otsikkosel" class="form-text text-muted"> Jos jätät otsikon tyhjäksi, tilaisuus otsikoidaan
                    päivämäärän perusteella esimerkiksi "Elokuun tilaisuus". Syötä otsikko vain, jos haluat sen olevan
                    jotain muuta. </small>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-2 col-form-label" for="til-pvm">Päivämäärät</label>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-calendar-day" data-toggle="tooltip" data-placement="top"
                            title="Tilaisuuden päivämäärä"></i></span>
                </div>
                <input type="date" class="form-control" id="til-pvm" required />
            </div>
            <div class="col-sm-3 input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-hourglass-start" data-toggle="tooltip"
                            data-placement="top" title="Ilmoittautuminen alkaa"></i></span>
                </div>
                <input type="date" class="form-control" id="til-ilmo" required />
            </div>
            <div class="col-sm-3 input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" data-toggle="tooltip" data-placement="top"
                        title="Viimeinen ilmoittautumispäivä">VIP</span>
                </div>
                <input type="date" class="form-control" id="til-vip" required />
            </div>
        </div>
        <input class="btn btn-success" id="alustatilaisuus" type="button" value="Alusta tilaisuus"
            onclick="alustaTilaisuus()" disabled />
    </div>

    <hr />

    <h2 id="lisaapuuttuvia" data-toggle="collapse" data-target="#lisaaos" aria-expanded="false" aria-controls="lisaaos">
        Lisää puuttuvia osallistujia / lähetä arkistotulokset <i class="fas fa-caret-down"></i>
    </h2>
    <?php if (!empty($_SESSION['Epaonnistuneet']) && count($_SESSION['Epaonnistuneet']['epaonnistuneet']) > 0) {
        echo '<p class="text-danger lead">Vähintään yhden hevosen tietojen lähetys epäonnistui [ ' . $_SESSION['Epaonnistuneet']['klo'] . ' ].<br>Epäonnistuneet rivit alapuolella:</p>';
        echo "<p class=\"text-danger\">";
        foreach ($_SESSION['Epaonnistuneet']['epaonnistuneet'] as $rivi) {
            echo $rivi . "<br>";
        }
        echo "</p>";
    } ?>
    <div class="collapse mt-4" id="lisaaos">
        <p>Jos tilaisuudessa on arvosteltu "tietokannan ulkopuolisia" hevosia jotka on vain lisätty suoraan Google Sheetsiin
            arvosteltaviksi, saat niiden tiedot, palkinnot ja pisteet tietokantaan käyttämällä tätä lomaketta. Voit syöttää
            hevosten tiedot kohta kerrallaan jolloin lomake muotoonlaittaa tiedot puolestasi, tai lisätä kaikki tiedot
            "massalisäyksenä" tekstikenttään Sheetsin LaatisAdmin-muotoonlaittovälilehden muodon
            mukaisesti:<br><code>VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET</code> <small class="ml-2">(huom:
                rodun ja sukupuolen välissä on viiva eikä puolipiste.)</small></p>
        <p>Jos pisteitä ja palkintoja ei ole vielä tiedossa ja syötät hevosen tietoja peruslomakkeella, <b>jätä kentät
                tyhjäksi</b>.</p>
        <p>Tätä lomaketta voi myös hyödyntää, kun halutaan syöttää vanhojen tilaisuuksien tuloksia tietokantaan, sillä
            lomake antaa lisätä tietoja kaikille tietokantaan alustetuille tilaisuuksille huolimatta niiden päivämäärästä.
            Järjestelmä lisää tai päivittää hevosen ja osallistumisen kaikki annetut tiedot. <span class="text-danger">Ole
                tarkkana että valitset oikean tilaisuuden valikosta!</span></p>
        <p class="lead">Syötä tiedot kenttiin ja siirrä, tai kirjoita tiedot muotoonlaitettuna suoraan tekstikenttään:</p>
        <div id="jalkiilmoform">
            <div class="row">
                <div class="col-3 form-group" style="min-width: 11em;">
                    <select class="form-control kriittinen" name="tilid" id="jalk-tilid" required>
                        <?php
                        try {

                            $stmt = $conn->prepare("SELECT * FROM Tilaisuus
                                        ORDER BY Pvm DESC;");
                            $stmt->execute();
                            $tilsut = $stmt->fetchAll();

                            if (count($tilsut) > 0) {
                                echo "<option selected disabled value=\"\">Valitse tilaisuus...</option>";
                            } else {
                                echo "<option selected disabled value=\"null\">(ei tilaisuuksia)</option>";
                            }

                            foreach ($tilsut as $a) {
                                echo "<option value=\"" . $a['Til_ID'] . "\"";
                                echo (count($tilsut) == 1 ? " selected" : "");
                                echo ">";
                                echo ($a['Otsikko'] != null ? $a['Otsikko'] . " " . date('d.m.y', strtotime($a['Pvm'])) : date('m/Y', strtotime($a['Pvm'])));
                                echo ($a['Valmis'] != null ? " (valmis)" : "");
                                echo "</option>";
                            }
                        } catch (PDOException $e) {
                            echo "<option value=\"\">Tilaisuuksien haku epäonnistui</option>";
                        }

                        ?>
                    </select>
                </div>
                <div class="col col-2 input-group mb-3">
                    <input type="text" id="jalk-rotu" class="form-control kriittinen" placeholder="rotulyh.">
                </div>
                <div class="col col-2 input-group mb-3" style="flex-grow:2;"><select id="jalk-skp"
                        class="form-control kriittinen">
                        <option value="" selected="" disabled="">Skp.</option>
                        <option value="t">t</option>
                        <option value="o">o</option>
                        <option value="r">r</option>
                    </select></div>
                <div class="col input-group mb-3"><input type="text" id="jalk-nimi" class="form-control kriittinen"
                        placeholder="Hevosen Nimi"></div>
                <div class="col"><input type="text" id="jalk-VH" class="form-control kriittinen"
                        placeholder="VH00-000-0000"></div>
            </div>
            <div class="row form-group">
                <div class="col-4 input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-link" data-toggle="tooltip" data-placement="top"
                                title="Hevosen URL-osoite"></i></span>
                    </div><input type="text" id="jalk-linkki" class="form-control kriittinen"
                        placeholder="https://hevosenosoite.com">
                </div>
                <div class="col input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-info-circle" data-toggle="tooltip"
                                data-placement="top" title="Lisätiedot / poikkeukset"></i></span>
                    </div><input type="text" id="jalk-poikkeukset" class="form-control"
                        placeholder="Lisätiedot / poikkeukset">
                </div>
                <div class="col-2 input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calculator" data-toggle="tooltip"
                                data-placement="top" title="Pisteet (jos tilaisuus valmis)"></i></span>
                    </div><input type="number" step="0.01" id="jalk-pisteet" class="form-control" placeholder="0">
                </div>
                <div class="col-2 input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-trophy" data-toggle="tooltip" data-placement="top"
                                title="Palkinto (jos tilaisuus valmis)"></i></span>
                    </div><input type="text" id="jalk-palkinto" class="form-control" placeholder="Palkinto">
                </div>
            </div>
            <div class="row mb-3 mt-3 justify-content-center">
                <button class="btn btn-info" id="siirrajalkiilmot" disabled>
                    <i class="fas fa-arrow-alt-circle-down"></i> Siirrä tiedot tekstikenttään <i
                        class="fas fa-arrow-alt-circle-down"></i>
                </button>
            </div>
            <div class="mb-3">
                <label for="jalkiilmot">Muotoonlaitetut tiedot:
                    <code>VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET</code></label>
                <textarea class="form-control" id="jalkiilmot"
                    placeholder="VH;PISTEET;PALKINTO;ROTU-SKP;NIMI;LINKKI;POIKKEUKSET" required></textarea>
                <div class="invalid-feedback"> Jokin tekstikentän tiedoista ei ole muotoonlaitettu oikein. Tarkista tiedot.
                </div>
            </div>
            <button class="btn btn-success" id="lahetajalkiilmot" disabled>Lähetä tiedot</button>
        </div>
    </div>

    <hr />

    <h2 id="tuloksettomat">Tuloksettomat tilaisuudet</h2>
    <p>Admin-puolen osallistujataulukoissa näkyvät kaikki hevoset, myös ne jotka eivät ole mahtumassa mukaan. Tunnistat
        tilaisuuden maksimiosallistujamäärän ulkopuolelle jäävät rivit keltaisesta kuvakkeesta.</p>
    <?php

    if ($conn) {
        // tulevat tilaisuudet
        $tilsut = $conn->prepare("SELECT t.*, COUNT(kt.VRL) as Keikat 
        FROM Tilaisuus t LEFT JOIN Keikkatuomari kt ON kt.Til_ID = t.Til_ID
        WHERE t.Valmis IS NULL
        GROUP BY t.Til_ID 
        ORDER BY t.Pvm ASC");
        $tilsut->execute();
        $ktt = $tilsut->fetchAll();

        $seuraavatilaisuus = true;

        if (count($ktt) < 1) {
            echo "<tr><td colspan=\"7\">Ei seuraavaa tilaisuutta tietokannassa</td></tr>";
        }

        foreach ($ktt as $t) {

            echo "<table class=\"mt-4 mb-0\"><tr><th colspan=\"7\">";
            echo ((!empty($t['Otsikko'])) ? ($t['Otsikko']) : ($kuukausi[date('m', strtotime($t['Pvm']))]) . "n tilaisuus");
            echo "</th></tr>";

            echo "<tr class=\"ots\">";
            echo "<td>Til_ID</td>";
            echo "<td>Otsikko</td>";
            echo "<td>Pvm</td>";
            echo "<td>Ilmoaika</td>";
            echo "<td>Max. Os.</td>";
            echo "<td><i class=\"fas fa-user-plus\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Keikkatuomareita\"></i></td>";
            echo "<td></td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>" . $t['Til_ID'] . "</td>";
            echo "<td>" . (!empty($t['Otsikko']) ? $t['Otsikko'] : "<i>" . $kuukausi[date('m', strtotime($t['Pvm']))] . "n tilaisuus</i>") . "</td>";
            echo "<td>" . date('d.m.Y', strtotime($t['Pvm'])) . "</td>";
            echo "<td>" . date('d.m.', strtotime($t['IlmoAlku'])) . "&ndash;" . date('d.m.', strtotime($t['IlmoLoppu'])) . "</td>";
            echo "<td>" . $t['Maxos'] . "</td>";
            echo "<td>" . ($t['Keikat'] == 0 ? "<span style='color:rgba(0,0,0,.4)'>" . $t['Keikat'] . "</span>" : $t['Keikat']) . "</td>";
            echo "<td style=\"text-align:right;\">";
            echo "<span data-toggle=\"modal\" data-target=\"#muokkausmodal\" onclick=\"haeTilaisuus(" . $t['Til_ID'] . ");\"><i class=\"fas fa-edit text-primary\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Muokkaa\" style=\"cursor:pointer;\"></i></span></td>";
            echo "</tr></table>";

            haeOsallistujataulukko($t['Til_ID'], true);
        }
    } ?>

    <hr />

    <h2 data-toggle="collapse" data-target="#arkistoc" aria-expanded="false" aria-controls="arkistoc">Muokkaa valmiita
        tilaisuuksia <i class="fas fa-caret-down"></i></h2>
    <div class="collapse mt-4" id="arkistoc">
        <?php if ($conn) {
            // tulevat tilaisuudet
            $tilsut = $conn->prepare("SELECT t.*, max(o.Pisteet), max(o.Palkinto), CONCAT(COALESCE(max(o.Pisteet),''), COALESCE(max(o.Palkinto),'')) as Palkintopisteet, count(k.Rivi_ID) as Keikat FROM Tilaisuus t
LEFT JOIN Osallistuminen o ON o.Til_ID = t.Til_ID 
LEFT JOIN Keikkatuomari k ON k.Til_ID = t.Til_ID
WHERE t.Tulokset IS NOT NULL
GROUP BY t.Til_ID
ORDER BY t.Pvm DESC");
            $tilsut->execute();
            $ktt = $tilsut->fetchAll();

            echo "<table class=\"mt-0 mb-0\"><tr><th colspan=\"7\">Tilaisuusarkisto</th></tr>";

            echo "<tr class=\"ots\">";
            echo "<td>Til_ID</td>";
            echo "<td>Otsikko</td>";
            echo "<td>Pvm</td>";
            echo "<td>Os. / Max.</td>";
            echo "<td>Valmis</td>";
            echo "<td><i class=\"fas fa-user-plus\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Keikkatuomareita\"></i></td>";
            echo "<td></td>";
            echo "</tr>";

            if (count($ktt) < 1) {
                echo "<tr><td colspan=\"7\">Ei valmiita tilaisuuksia arkistossa</td></tr>";
            }

            foreach ($ktt as $t) {
                echo "<tr>";
                echo "<td>" . $t['Til_ID'] . "</td>";
                echo "<td>" . (!empty($t['Otsikko']) ? $t['Otsikko'] : "<i>" . $kuukausi[date('m', strtotime($t['Pvm']))] . "n tilaisuus</i>") . "</td>";
                echo "<td>" . date('d.m.Y', strtotime($t['Pvm'])) . "</td>";
                echo "<td>" . $t['Osallistujia'] . " / " . $t['Maxos'] . "</td>";
                echo "<td><a href=\"" . $t['Tulokset'] . "\" target=\"new\">" . date('d.m.Y', strtotime($t['Valmis'])) . "</a></td>";
                echo "<td>" . ($t['Keikat'] == 0 ? "<span style='color:rgba(0,0,0,.4)'>" . $t['Keikat'] . "</span>" : $t['Keikat']) . "</td>";
                echo "<td style=\"text-align:right;\">";
                echo $t['Palkintopisteet'] == null ? "<i class=\"sospisteet fas fa-exclamation-triangle text-warning mr-3\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Tilaisuudella ei ole osallistujakohtaisia pisteitä / palkintoja tietokannassa\"></i>" : '';
                echo "<span data-toggle=\"modal\" data-target=\"#muokkausmodal\" onclick=\"haeTilaisuus(" . $t['Til_ID'] . ");\"><i class=\"fas fa-edit text-primary\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Muokkaa\" style=\"cursor:pointer;\"></i></span></td>";
                echo "</tr>";
            }

            echo "</table>";
        } ?>
    </div>
    <?php
} else { ?>
    <p>Olet tekemässä ylläpitäjien toimintoja. Anna salasana.</p>
    <form method="POST" action="valtuuta.php">
        <input type="password" name="password">
        <input type="hidden" value="admin/tilaisuuksienhallinta.php" name="paluu">
        <button class="btn btn-success" type="submit">Valtuuta</button>
    </form>
<?php }


$conn = null;

require_once '../footer.php'; ?>

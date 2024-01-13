<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = null;
$tilid = null;
$osallistujia = 0;
$uploaded = false;
$tuoms = array();


require_once("../" . $headerurl);
?>
<?php include 'adminlinkit.php'; ?>

<h1>Osioiden hallinta</h1>
<p>Muuta osioiden järjestystä nuolipainikkeista. Osiot näytetään valitussa järjestyksessä tuomaritaulukossa ja
    lomakkeissa. Tallentaminen päivittää sivun hetken kuluttua.</p>
<?php if (!empty($_SESSION['koodi']) && $_SESSION['koodi'] === $koodi) {

    try {

        $stmt = $conn->prepare("SELECT * FROM Alue ORDER BY Jarjestys ASC, Alue_ID ASC");
        $stmt->execute();
        $alueet = $stmt->fetchAll();

        if (count($alueet) < 1) {
            echo "<p class=\"lead\">Lisää ainakin yksi osio tietokantaan</p>";
        }
        echo "<div class=\"row lead\">";
        foreach ($alueet as $t) {
            echo "<div class=\"osiosiirto col mb-3 mr-3 col-md-auto flex-nowrap\" style=\"min-width: 10rem;\">";
            echo "<div class=\"small input-group flex-nowrap justify-content-between\">";
            echo "<span class=\"siirra-prev badge badge-pill badge-secondary mr-1\">&#8592;</span>";
            echo "<span class=\"badge badge-pill badge-secondary mr-1\">siirrä</span>";
            echo "<span class=\"siirra-next badge badge-pill badge-secondary\">&#8594;</span>";
            echo "</div>";
            echo "<input type=\"hidden\" class=\"osiojarkka\" value=\"" . $t['Alue_ID'] . "\" />";
            echo "<span class=\"d-inline-block lead text-nowrap\">" . $t['Otsikko'] . "<span class=\"badge badge-dark ml-1\">ID " . $t['Alue_ID'] . "</span></span>";
            echo "</div>";
        }
        echo "</div>";
    } catch (PDOException $e) {
        echo "<option>Alueiden haku epäonnistui</option>";
    }
    ?>
    <button class="btn btn-info" onclick="jarjestaOsiot();">Tallenna järjestys</button>

    <hr />

    <h2>Lisää uusi osio</h2>
    <div class="mt-4">
        <div class="row">
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Osion nimi</span>
                </div>
                <input type="text" id="ut-osio" name="osio" class="form-control" placeholder="esim. Rakennearvostelu">
            </div>
            <div class="col-auto pl-0">
                <input class="btn btn-success" id="lisaaosio" type="button" value="Lisää osio"
                    onclick="var alueennimi = $('#ut-osio').val(); simppeliCrud('insert', 'Alue', 'Otsikko', alueennimi, null, null);"
                    disabled />
            </div>
        </div>
    </div>

    <hr />

    <h2>Muokkaa osioita</h2>
    <div class="mt-4" id="muokkaac">
        <div class="row mb-3">
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                </div>
                <select class="custom-select" name="muokattavaosio" id="muokattavaosio">
                    <?php
                    try {

                        $stmt = $conn->prepare("SELECT * FROM Alue ORDER BY Jarjestys ASC, Alue_ID ASC");
                        $stmt->execute();
                        $alueet = $stmt->fetchAll();

                        if (count($alueet) > 0) {
                            echo "<option selected disabled value=\"null\">(valitse muokattava osio)</option>";
                        } else {
                            echo "<option selected disabled value=\"null\">(ei muokattavia osioita tietokannassa)</option>";
                        }

                        foreach ($alueet as $t) { ?>
                            <option value="<?php echo $t['Alue_ID']; ?>">
                                <?php echo $t['Otsikko']; ?>
                            </option>
                            <?php
                        }
                    } catch (PDOException $e) {
                        echo "<option>Alueiden haku epäonnistui</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Osion nimi</span>
                </div>
                <input type="text" class="form-control" id="mt-otsikko" required placeholder="(ei valittua osiota)" />
            </div>
        </div>
        <input id="paivitaosio" class="btn btn-info mr-2" type="button" value="Päivitä osion tiedot"
            onclick="var alueennimi = $('#mt-otsikko').val(); var alueid = $('#muokattavaosio').val(); simppeliCrud('update', 'Alue', 'Otsikko', alueennimi, 'Alue_ID', alueid);"
            disabled />
        <input id="poistaosio" class="btn btn-outline-danger" type="button" value="Poista osio" onclick="poistaOsio();"
            disabled />
    </div>
<?php } else { ?>
    <p>Olet tekemässä ylläpitäjien toimintoja. Anna salasana.</p>
    <form method="POST" action="valtuuta.php">
        <input type="password" name="password">
        <input type="hidden" value="admin/index.php" name="paluu">
        <button class="btn btn-success" type="submit">Valtuuta</button>
    </form>
<?php }

$conn = null; ?>
<?php require_once "../" . $footerurl; ?>
<form id="keikkatuomariform" action="admin/lahetakeikkatuomari.php" method="POST">
    <div class="row">
        <div class="col">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">VRL-</span>
                </div>
                <input type="text" id="keikkaVRL" name="keikkaVRL" class="form-control" placeholder="00000"
                    aria-label="00000" aria-describedby="basic-addon1" required>
            </div>
        </div>
        <div class="col">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">Sähköposti</span>
                </div>
                <input type="email" id="email" name="email" class="form-control" placeholder="esimerkki@domain.com"
                    aria-label="email" aria-describedby="basic-addon1" required>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col"> Rastita alueet, joiden tuomarointeja voit tehdä: </div>
    </div>
    <div class="row mb-3 ml-0">
        <?php
        try {

            $stmt = $conn->prepare("SELECT * FROM Alue ORDER BY Jarjestys ASC, Alue_ID ASC");
            $stmt->execute();
            $alueet = $stmt->fetchAll();

            if (count($alueet) < 1) {
                echo "<div>Laatuarvostelulla ei ole vielä yhtään tuomaroitavaa osa-aluetta. Et voi ilmoittautua vielä keikkatuomariksi.</div>";
            }

            foreach ($alueet as $t) { ?>
                <div class="custom-control custom-checkbox custom-control-inline col-md-auto" style="min-width: 9em;">
                    <input type="checkbox" id="<?php echo strtolower($t['Otsikko']); ?>" name="alue[]"
                        value="<?php echo $t['Alue_ID']; ?>" class="keikkacheck custom-control-input">
                    <label class="custom-control-label" for="<?php echo strtolower($t['Otsikko']); ?>">
                        <?php echo $t['Otsikko']; ?>
                    </label>
                </div>
                <?php
            }
        } catch (PDOException $e) {
            echo "<option>Alueiden haku epäonnistui</option>";
        }
        ?>
    </div>
    <div class="row mb-3">
        <div class="col">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <label class="input-group-text" for="keikkatilaisuus">Tilaisuus</label>
                </div>
                <select class="custom-select" id="keikkatilaisuus" name="keikkatilaisuus" required>
                    <?php
                    date_default_timezone_set('Europe/Helsinki');

                    function haeTilaisuudet($kuluvakk, $kuluvav)
                    {

                        $kuukausi = array("01" => "Tammikuu", "02" => "Helmikuu", "03" => "Maaliskuu", "04" => "Huhtikuu", "05" => "Toukokuu", "06" => "Kesäkuu", "07" => "Heinäkuu", "08" => "Elokuu", "09" => "Syyskuu", "10" => "Lokakuu", "11" => "Marraskuu", "12" => "Joulukuu");

                        require 'tk_kredentiaalit.php';

                        try {
                            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            $stmt = $conn->prepare("SELECT * 
                        FROM Tilaisuus
                        WHERE IlmoLoppu >= (?)
                        AND Valmis IS NULL");
                            $stmt->execute([date('Y-m-d')]);
                            $tils = $stmt->fetchAll();

                            if (count($tils) > 0) {
                                echo "<option selected disabled value=\"null\">Valitse tilaisuus...</option>";
                            } else {
                                echo "<option selected disabled value=\"null\">(ei tilaisuuksia tuomaroitavaksi)</option>";
                            }

                            foreach ($tils as $til) {
                                if ($til['Til_ID'] != "") {
                                    echo "<option value=\"";
                                    echo $til['Til_ID'];
                                    echo "\">";
                                    echo date('m/Y', strtotime($til['Pvm']));
                                    echo ($til['Otsikko'] != null ? " (" . $til['Otsikko'] . ")" : "");
                                    echo "</option>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo "<option value=\"\">Tilaisuuksien haku epäonnistui</option>";
                        }

                        $conn = null;
                    }

                    $mahdkk = null;

                    if (date('d') > 10 && date('n') % 2 == 0) {
                        $mahdkk = date('n', strtotime('first day of +1 month'));
                    } else if (date('d') <= 10 && date('n') % 2 == 0) {
                        $mahdkk = date('n', strtotime('first day of -1 month'));
                    } else {
                        $mahdkk = date('n');
                    }

                    haeTilaisuudet($mahdkk, date('Y'));
                    ?>
                </select>
            </div>
        </div>
        <div class="col">
            <button class="btn btn-success" id="lahetakeikka" type="button" onclick="lahetaKeikkatuomari()"
                disabled>Ilmoittaudu keikkatuomariksi</button>
        </div>
    </div>
</form>
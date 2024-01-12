<?php
function haeTulokset($vuosi)
{
    $kuukausi = array("01" => "Tammikuu", "02" => "Helmikuu", "03" => "Maaliskuu", "04" => "Huhtikuu", "05" => "Toukokuu", "06" => "Kesäkuu", "07" => "Heinäkuu", "08" => "Elokuu", "09" => "Syyskuu", "10" => "Lokakuu", "11" => "Marraskuu", "12" => "Joulukuu");

    require 'tk_kredentiaalit.php';

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT T.*, COUNT(O.Os_ID) as Oslkm, COUNT(IF(Varahevonen='1', 1, NULL)) as Varahevosia FROM Osallistuminen O 
	JOIN Tilaisuus T ON T.Til_ID = O.Til_ID 
    WHERE O.VRL NOT IN (SELECT VRL FROM AlueidenTuomarit) 
        AND YEAR(T.Pvm) = (?)
    GROUP BY T.Til_ID
    ORDER BY T.Pvm DESC;");
        $stmt->execute([$vuosi]);
        $tils = $stmt->fetchAll();

        date_default_timezone_set('Europe/Helsinki');

        if (count($tils) > 0) {
            echo "<tr><th colspan=\"4\">" . $vuosi . "</th></tr>";
        }

        foreach ($tils as $til) {
            if ($til['Til_ID'] != "") {


                echo "<tr>\n<td>\n";
                echo date('d.m.Y', strtotime($til['Pvm'])) . "\n</td>\n";
                echo "<td>\n";
                echo (!empty($til['Otsikko']) ? $til['Otsikko'] : $kuukausi[date('m', strtotime($til['Pvm']))] . "n tilaisuus");
                echo "\n</td>\n";
                echo "<td>\n";
                if ($til['Tulokset'] != "") {
                    echo "<a href=\"" . $til['Tulokset'] . "\" target=\"new\">Tulokset tulleet " . date('d.m.', strtotime($til['Valmis'])) . "</a>\n</td>\n";
                } else if ($til['IlmoLoppu'] >= date('Y-m-d')) {
                    echo "<i>Ilmoittautuminen on vielä avoinna</i>\n</td>\n";
                } else {
                    echo "<i>Odottaa tuloksia</i>\n</td>\n";
                }
                echo "<td class=\"text-right\">\nOsallistujia ";
                if ($til['Osallistujia'] != null) {
                    echo $til['Osallistujia'];
                } else if ($til['Oslkm'] > $til['Maxos']) {
                    echo $til['Maxos'] . " <small class=\"text-black-50\">& jonossa " . ($til['Oslkm'] - $til['Maxos']) . "</small>";
                } else {
                    echo ($til['Oslkm'] - $til['Varahevosia']);
                }


                $varaheppojatulossa = 0;

                if ($til['Osallistujia'] == null && $til['Oslkm'] - $til['Varahevosia'] < $til['Maxos']) {
                    $varaheppojamahtuu = $til['Maxos'] - ($til['Oslkm'] - $til['Varahevosia']);
                    $varahepatjotkaeimahdu = $til['Varahevosia'] - $varaheppojamahtuu;
                    if ($varahepatjotkaeimahdu < 0) {
                        $varahepatjotkaeimahdu = 0;
                    }

                    if ($til['Varahevosia'] > 0) {
                        $varaheppojatulossa = ($til['Varahevosia'] - $varahepatjotkaeimahdu);
                    } else {
                        $varaheppojatulossa = 0;
                    }


                    echo ($varaheppojatulossa > 0 ? "<sub>+" . $varaheppojatulossa . "</sub>" : "");
                }
                echo " / " . $til['Maxos'] . "\n</td>\n</tr>\n";
            }
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan=\"4\">Tulosten haku epäonnistui.<br>" . $sql . "<br>" . $e->getMessage() . "</td></tr>";
    }

    $conn = null;
}

<table class="tuomarit">
    <?php
    function tauollaNyt($alku, $loppu)
    {
        $tanaan = date('Y-m-d');

        if ($alku != null && $alku <= $tanaan) {
            if ($loppu == null || $loppu >= $tanaan) {
                return true;
            }
        } else {
            return false;
        }
    }

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $testi = $conn->prepare("SELECT A.*, T.*, J.Nimimerkki, J.Sahkoposti, tt.Alku, tt.Loppu, (CASE
              WHEN (Alku <= CURRENT_DATE AND (Loppu IS NULL OR Loppu >= CURRENT_DATE)) THEN '1'
              ELSE '0'
            END) as Taukoilee 
        FROM Alue A 
        LEFT JOIN AlueidenTuomarit T ON A.Alue_ID = T.Alue_ID 
        LEFT JOIN Tuomari J ON J.VRL = T.VRL 
        LEFT JOIN TuomareidenTauot tt ON tt.VRL = T.VRL 
        WHERE tt.Rivi_ID = ( 
            SELECT MAX(Rivi_ID) FROM TuomareidenTauot 
            WHERE VRL = T.VRL ) 
        OR tt.Rivi_ID IS NULL 
        ORDER BY A.Jarjestys ASC, A.Alue_ID ASC, T.Paatoimisuus DESC, Taukoilee ASC, J.Nimimerkki ASC;");
        $testi->execute();
        $tuomarit = $testi->fetchAll();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    if (count($tuomarit) > 0) {
        $kaytetytosiot = array();
        $uusialue = false;
        $muistipaikka = null; // pää- vai varatuomari

        foreach ($tuomarit as $key => $t) {
            $uusialue = false;

            if (!empty($t['Paatoimisuus']) && $t['Paatoimisuus'] != $muistipaikka && $muistipaikka != null) {
                echo "</td>\n";
            }

            if (!in_array($t['Otsikko'], $kaytetytosiot)) {
                if ($muistipaikka != null) {
                    if ($t['Paatoimisuus'] == 1 && $tuomarit[$key - 1]['Paatoimisuus'] == "1") {
                        echo "<td><i>Ei varatuomareita</i></td>\n";
                    }
                    echo "</tr>\n";
                }
                echo "<tr>\n<th colspan=\"2\">" . $t['Otsikko'] . "</th>\n</tr>\n";
                echo "<tr class=\"ots\">\n<td>Päätuomarit</td>\n<td>Varatuomarit</td>\n</tr>\n";
                $uusialue = true;
                $muistipaikka = null;
            }

            array_push($kaytetytosiot, $t['Otsikko']);

            if ($uusialue === true && $t['VRL'] != null &&  $t['Paatoimisuus'] == "0") {
                echo "<tr>\n<td><i>Ei päätuomareita</i></td>\n";
            } else if ($t['VRL'] == null) {
                echo "<td><i>Ei päätuomareita</i></td><td><i>Ei varatuomareita</i></td>\n";
                continue;
            }

            if ($t['Paatoimisuus'] != $muistipaikka) {
                echo "<td>";
                $muistipaikka = $t['Paatoimisuus'];
            }
            if (tauollaNyt($t['Alku'], $t['Loppu'])) {
                echo "<span style=\"opacity:.6\">";
            }
            echo "<a href=\"mailto:" . $t['Sahkoposti'] . "\">" . $t['Nimimerkki'] . "</a> VRL-" . $t['VRL'];
            if (tauollaNyt($t['Alku'], $t['Loppu'])) {
                echo " <i class=\"far fa-pause-circle\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Tauolla ";
                echo $t['Loppu'] == null ? "toistaiseksi" : date("d.m.Y", strtotime($t['Loppu'])) . " saakka";
                echo "\"></i></span>";
            }

            echo "<br>";

            if ($key === array_key_last($tuomarit) && $t['Paatoimisuus'] == "1") {
                echo "</td>\n<td><i>Ei varatuomareita</i>";
            } else if ($key !== array_key_last($tuomarit) && $t['Paatoimisuus'] == "1" && !in_array($tuomarit[$key + 1]['Otsikko'], $kaytetytosiot) && $tuomarit[$key + 1]['Paatoimisuus'] == "0") {
                echo "</td>\n<td><i>Ei varatuomareita</i>";
            } else if ($key !== array_key_last($tuomarit) && $t['Paatoimisuus'] == "1" && !in_array($tuomarit[$key + 1]['Otsikko'], $kaytetytosiot) && $tuomarit[$key + 1]['VRL'] == null) {
                echo "</td>\n<td><i>Ei varatuomareita</i>";
            }
        }
    } else {
        echo "<tr>\n<td>Laatuarvostelulla ei ole vielä tuomaroitavia osa-alueita";
    }
    ?>
    </td>
    </tr>
</table>
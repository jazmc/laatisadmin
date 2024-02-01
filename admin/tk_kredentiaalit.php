<?php
date_default_timezone_set('Europe/Helsinki');

// tietokannan kredentiaalit, vaihda omasi
$servername = "localhost"; // <-- tuskin tarvii vaihtaa
$username = ""; // mysql-käyttäjänimi
$password = ""; // mysql-käyttäjän salasana
$dbname = ""; // tietokannan nimi

// ----------------------------------------------------

// kysytään ylläpitäjiltä admin-toimintoja tehdessä
$koodi = "LaatisAdmin";

// tätä ehdotetaan tilaisuuden max-os. määräksi lomakkeella, mutta sitä saa muutettua sieltä kyllä käsin
$oletus_osallistujamaara = 20;

// paljonko hevosia osallistujat / tuomarit saavat tuoda per tilaisuus?
$tavallistenhevosmaara = 2; // tavikset, varsinaiset osallistujat
$varahevosmaara = 1; // tavikset, montako varahevosta
$tuomarienhevosmaara = 5; // varsinaiset + lisähevoset yhteensä (YLAssa 2+3)

// laatuarvostelun palkinnot paremmuusjärjestyksessä (palkitut-sivulle)
$laatispalkinnot =
    array(
        "YLA1*",
        "YLA1",
        "YLA2",
        "YLA3" // ei pilkkua viimeisen palkinnon jälkeen!
    );

// ----------------------------------------------------

// headerin tiedostonimi
$headerurl = 'header.php';
// footerin tiedostonimi
$footerurl = 'footer.php';

// linkeissä ja php-koodeissa käytettyjä osoitteita yms
$domain = "https://hiirenkolo.net/virtuaaliapu/laatisadmin/";
$tilaisuussivu = "https://hiirenkolo.net/virtuaaliapu/laatisadmin/tilaisuudet.php";
$tulosarkisto = "https://hiirenkolo.net/virtuaaliapu/laatisadmin/tilaisuudet.php#tulosarkisto";

// minne tulos-pdf:t ladataan, paikallisena polkuna lähtien /admin kansiosta:
$pdfkansio = "../pdf/"; // paikallinen
$pdfkansio_pitkaurl = "https://hiirenkolo.net/virtuaaliapu/laatisadmin/pdf/"; // pitkä url, kauttaviiva loppuun

// laatuarvostelun sähköpostiosoite
$laatisemail = 'shelyesyllapito@gmail.com';

// hanki oma lisenssi FontAwesome -ikonisivustolle
$fontawesome = "https://kit.fontawesome.com/(lisenssinumerot).js";

// kuukausien suomenkielinen nimiarray, ei tarvitse koskea:
$kuukausi = array("01" => "Tammikuu", "02" => "Helmikuu", "03" => "Maaliskuu", "04" => "Huhtikuu", "05" => "Toukokuu", "06" => "Kesäkuu", "07" => "Heinäkuu", "08" => "Elokuu", "09" => "Syyskuu", "10" => "Lokakuu", "11" => "Marraskuu", "12" => "Joulukuu");
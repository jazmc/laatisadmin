# LaatisAdmin by Jassu L.

Käyttöönotto: seuraa [tämän sivun](https://hiirenkolo.net/virtuaaliapu/laatisadmin/kayttoonotto.php) ohjeita.

## Materiaalit & sivupohjat

-   [Tietokannan alustusscriptit](/assets/tietokantascriptit.txt)
-   `header.php`:n [sivupohja](/header.php)
-   `footer.php`:n [sivupohja](/footer.php)

## Laatisten sivujen geneerinen rakenne

_Voit yrittää upottaa laatiksesi sivujen sisällön näiden geneeristen pohjien sekaan._

<details>
<summary>Kaikki sivut joilla on ulkoasu</summary>

```html
<?php // header-include
require_once $headerurl; ?>

<!-- sivun muu sisältö tähän -->

<?php // footer-include
require_once $footerurl; ?>
```

</details>

<details>
<summary>Tärkeää tietoa -sivu (tarkeaa.php)</summary>

```html
<?php // header-include
require_once $headerurl; ?>

<h1>Tärkeää tietoa</h1>
<!-- (Sivun leipäteksti tähän) -->

<hr />

<div class="palstat">
    <div class="vasen">
        <h2>Palstan otsikko</h2>
        <!-- (palstan teksti) -->
    </div>
    <div class="oikea">
        <!-- tuomaritaulukko -->
        <h2 id="tuomarit">Tuomarit</h2>
        <?php include_once 'admin/tuomaritaulukko.php'; ?>
    </div>
</div>

<hr />

<!-- keikkatuomarilomake -->
<h2 id="keikkatuomari">Keikkatuomarilomake</h2>
<?php include_once 'admin/keikkatuomarilomake.php'; ?>

<?php // footer-include
require_once $footerurl; ?>
```

</details>

<details>
  <summary>Tilaisuudet-sivu (tilaisuudet.php)</summary>

```html
<?php // header + tarvittavat tiedostot
require_once $headerurl;
require 'admin/haeosallistujataulukko.php';
require 'admin/haetulokset.php'; ?>

<h1>Tilaisuudet</h1>
<!-- sivun leipäteksti tähän -->

<hr />

<!-- tilaisuuksien osallistumislomake -->
<?php include_once 'admin/oslomake.php'; ?>
<!-- ei hr-viivaa tähän väliin, tulee taulukon mukana  -->

<h2>Tulosarkisto</h2>
<table id="tulosarkisto">
    <!-- tulosarkisto, huom. tämä tulee table-tagin jälkeen -->
    <?php $vuosi = date('Y');

    // määritä tähän vuosi, josta alkaen laatiksella on tuloksia
    while ($vuosi >= '2021') { haeTulokset($vuosi--); } ?>
    <!-- tulosarkisto päättyy-->
    <tr>
        <th colspan="4">Vanhojen tilaisuussivujen arkisto</th>
    </tr>
    <tr>
        <td colspan="4" style="font-style: italic">
            Vanhat, ei-tietokantapohjaiset tilaisuudet voi listata tähän
            manuaalisina taulukon riveinä
        </td>
    </tr>
    <tr>
        <td>31.06.20XX</td>
        <td>Olen manuaalinen rivi</td>
        <td><a href="#">Tulokset tulleet</a></td>
        <td>Osallistujia 20/20</td>
    </tr>
</table>

<?php // footer-include
require_once $footerurl; ?>
```

</details>

<details>
  <summary>Palkitut-sivu (palkitut.php)</summary>

```html
<?php // header-include
require_once $headerurl; ?>

<h1>Palkitut</h1>
<!-- sivun leipäteksti -->

<hr />

<!-- Palkitut hevoset palkinnoittain -->
<?php

try {

    $stmt = $conn->prepare("SELECT h.Nimi, h.Rotu, o.*, t.Otsikko, t.Pvm, t.Tulokset
        FROM Osallistuminen o
        JOIN Tilaisuus t ON t.Til_ID = o.Til_ID
        JOIN Hevonen h ON h.VH = o.VH
        WHERE Palkinto = (?)
        ORDER BY Pisteet DESC");

    foreach ($laatispalkinnot as $palkinto) {
        $stmt->execute([$palkinto]);
        $palkitut = $stmt->fetchAll();

        echo "<h2>" . $palkinto . "<span class=\"ml-2 badge badge-secondary\">" . count($palkitut) . " kpl</span></h2>";

        echo "<p>";
        foreach ($palkitut as $heppa) {
            echo $heppa['Rotu'] . "-" . $heppa['Skp'] . ". ";
            echo "<a href=\"" . $heppa['Linkki'] . "\" target=\"new\">";
            echo $heppa['Nimi'] . "</a> &ndash; ";
            echo $heppa['VH'] . " &ndash; <b>";
            echo $heppa['Pisteet'] . " p.</b> (<a href=\"";
            echo $heppa['Tulokset'] . "\" target=\"new\">";
            echo date('m/y', strtotime($heppa['Pvm'])) . "</a>)<br>";
        }
        echo "</p>";
    }
} catch (PDOException $e) {
    echo "<p>Palkittujen haku epäonnistui</p>";
}
?>
<!-- Palkitut hevoset loppuu -->

<?php // footer-include
require_once $footerurl; ?>

```

</details>

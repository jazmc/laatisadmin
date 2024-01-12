<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Europe/Helsinki');
require_once 'admin/tk_kredentiaalit.php';
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
    die("<br>Tietokantayhteyden muodostus epäonnistui");
} ?>
<!DOCTYPE html>
<html lang="fi">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title> Laatiksen title </title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
            integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
        <link href="<?php echo $domain; ?>style1.css" type="text/css" rel="stylesheet" />
        <link href="<?php echo $domain; ?>admin/laatisadmin.css" type="text/css" rel="stylesheet" />
        <script src="<?php echo $fontawesome; ?>" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    </head>

    <body>
        <div id="otsikko">Laatis <p id="alaotsikko">Alaotsikko</p>
        </div>
        <div id="linkit">
            <ul>
                <li><a href="<?php echo $domain; ?>pisteytys.php">Pisteytys</a></li>
                <li><a href="<?php echo $domain; ?>tarkeaa.php">Tärkeää tietoa</a></li>
                <li><a href="<?php echo $domain; ?>tilaisuudet.php">Tilaisuudet</a></li>
                <li><a href="<?php echo $domain; ?>palkitut.php">Palkitut</a></li>
                <li><a href="<?php echo $domain; ?>admin/index.php">Ylläpito</a></li>
                <li><a href="<?php echo $domain; ?>index.php">Etusivu</a></li>
            </ul>
        </div>
        <div id="sisalto">
            <div id="teksti">
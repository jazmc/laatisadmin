<?php
session_start();
require 'tk_kredentiaalit.php';

$viesti = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SESSION) && $_SESSION['koodi'] === $koodi) {

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
                echo "<span style=\"color:green\"><i class=\"far fa-check-circle\"></i> Tiedosto " . htmlspecialchars(basename($_FILES["pdf"]["name"])) . " ladattiin palvelimelle onnistuneesti.</span>";
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
                           window.location.reload();
                        }, 5000);</script>";
            } else {
                echo "<span style=\"color:red\"><i class=\"fas fa-exclamation-circle\"></i> Tilaisuuden päivitys epäonnistui.</span>";
            }
        }
    }
} else {
    echo json_encode(array("error" => true, "viesti" => "ei valtuuksia lisätä tuloksia"));
}

$conn = null;
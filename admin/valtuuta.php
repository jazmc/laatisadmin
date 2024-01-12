<?php
session_start();
require 'tk_kredentiaalit.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paluu = htmlspecialchars($_POST['paluu']);
    if (!empty($_POST['password']) && $_POST['password'] === $koodi) {
        $_SESSION['koodi'] = $koodi;
        header('Location: ' . $domain . $paluu);
    } else {
        echo "Väärä koodi<br>";
    }
}

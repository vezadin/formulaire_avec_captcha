<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

use IconCaptcha\IconCaptcha;

$name = $prenom = $email = $demande = $numeroTel = $situationfamilial = $nationalite = "";
$captchaMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $options = require __DIR__ . '/examples/captcha-config.php';
    $captcha = new IconCaptcha($options);
    $validation = $captcha->validate($_POST);

    if (!$validation->success()) {
        die("⚠️ Captcha invalide.");
    }

    // Traitement sécurisé
    function clean($val) {
        return htmlspecialchars(trim($val));
    }

    $name = clean($_POST['name'] ?? '');
    $prenom = clean($_POST['prenom'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $demande = clean($_POST['demande'] ?? '');
    $numeroTel = clean($_POST['numeroTel'] ?? '');
    $situationfamilial = clean($_POST['situationfamilial'] ?? '');
    $nationalite = clean($_POST['nationalite'] ?? '');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Données encodées</title>
</head>
<body>
    <h2>Merci pour votre envoi, <?= $prenom ?> !</h2>

    <h3>Données encodées :</h3>
    <ul>
        <li>Nom (HTML-safe) : <?= $name ?></li>
        <li>Prénom (base64) : <?= base64_encode($prenom) ?></li>
        <li>Email (base64) : <?= base64_encode($email) ?></li>
        <li>Numéro (reversé) : <?= strrev($numeroTel) ?></li>
        <li>Situation familiale (base64) : <?= base64_encode($situationfamilial) ?></li>
        <li>Nationalité (base64) : <?= base64_encode($nationalite) ?></li>
        <li>Demande (htmlentities) : <?= htmlentities($demande) ?></li>
    </ul>

    <br>
    <a href="index.php">← Retour au formulaire</a>
</body>
</html>

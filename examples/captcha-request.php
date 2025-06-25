<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use IconCaptcha\IconCaptcha;

try {
    session_start();

    $options = require __DIR__ . '/captcha-config.php';
    $captcha = new IconCaptcha($options);

    if ($captcha->handleCors()) {
        exit; // Requête OPTIONS CORS, on arrête ici
    }

    $captcha->request()->process(); // Traite la requête du widget JS

    http_response_code(200);
    echo "Captcha request endpoint accessible.";
} catch (Throwable $exception) {
    http_response_code(500);
    echo "Erreur serveur : " . $exception->getMessage();
}

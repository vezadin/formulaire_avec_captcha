<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();
$_SESSION['test'] = 'ok';
echo 'Session test : ' . $_SESSION['test'];

// Initialisation
$name = $prenom = $email = $demande = $situationfamilial = $numeroTel = $nationalite = "";
$nameErr = $prenomErr = $emailErr = $demandeErr = $situationfamilialErr = $numeroTelErr = $nationaliteErr = "";
$captchaMessage = "";
$captchaValid = false;
$successMsg = "";
$encodedData = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation des champs
    if (empty($_POST["name"])) $nameErr = "Le champ est obligatoire.";
    else $name = htmlspecialchars(trim($_POST["name"]));

    if (empty($_POST["prenom"])) $prenomErr = "Le champ est obligatoire.";
    else $prenom = htmlspecialchars(trim($_POST["prenom"]));

    if (empty($_POST["email"])) $emailErr = "Le champ est obligatoire.";
    elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) $emailErr = "Email invalide.";
    else $email = htmlspecialchars(trim($_POST["email"]));

    if (empty($_POST["demande"])) $demandeErr = "Le champ est obligatoire.";
    else $demande = htmlspecialchars(trim($_POST["demande"]));

    if (empty($_POST["numeroTel"])) $numeroTelErr = "Le champ est obligatoire.";
    elseif (!preg_match("/^[0-9]{10}$/", $_POST["numeroTel"])) $numeroTelErr = "10 chiffres requis.";
    else $numeroTel = htmlspecialchars(trim($_POST["numeroTel"]));

    if (empty($_POST["situationfamilial"])) $situationfamilialErr = "Le champ est obligatoire.";
    else $situationfamilial = htmlspecialchars(trim($_POST["situationfamilial"]));

    if (empty($_POST["nationalite"])) $nationaliteErr = "Le champ est obligatoire.";
    else $nationalite = htmlspecialchars(trim($_POST["nationalite"]));

    // Captcha
    $options = require __DIR__ . '/examples/captcha-config.php';
    $captcha = new \IconCaptcha\IconCaptcha($options);
    $validation = $captcha->validate($_POST);

    $captchaValid = $validation->success();
    $captchaMessage = $captchaValid ? "Captcha validé." : "Validation captcha :";

    // Si tout est OK
    if (
        empty($nameErr) && empty($prenomErr) && empty($emailErr) &&
        empty($demandeErr) && empty($numeroTelErr) && empty($situationfamilialErr) &&
        empty($nationaliteErr) && $captchaValid
    ) {
        $successMsg = "Merci pour votre message, $name !";

        // Encodage base64 (optionnel)
        $encodedData = [
            'Nom' => base64_encode($name),
            'Prénom' => base64_encode($prenom),
            'Email' => base64_encode($email),
            'Téléphone' => base64_encode($numeroTel),
            'Situation familiale' => base64_encode($situationfamilial),
            'Nationalité' => base64_encode($nationalite),
            'Demande' => base64_encode($demande),
        ];

        // Enregistrement dans la base SQLite
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/formulaire.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("INSERT INTO users (name, prenom, email, numeroTel, situationfamilial, nationalite, demande, date_envoi)
                                  VALUES (:name, :prenom, :email, :numeroTel, :situationfamilial, :nationalite, :demande, :date_envoi)");

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':numeroTel', $numeroTel);
            $stmt->bindParam(':situationfamilial', $situationfamilial);
            $stmt->bindParam(':nationalite', $nationalite);
            $stmt->bindParam(':demande', $demande);
            $stmt->bindParam(':date_envoi', $date_envoi);

            $date_envoi = date('Y-m-d H:i:s');
            $stmt->execute();
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Erreur lors de l'enregistrement : " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Formulaire de renseignement</title>
    <link rel="stylesheet" href="styleF.css" />
    <link rel="stylesheet" href="assets/client/css/iconcaptcha.min.css" />
</head>
<body>
    <div class="titre">
        <h2>
            <?php
            if ($successMsg) {
                echo "Merci pour votre message,M.$name !<br>";
                echo '<a href="stockage.php" target="_blank" style="font-weight:bold; font-size:1.1em;">Voir les formulaires envoyés (accès protégé)</a>';
            } else {
                echo "Formulaire de renseignement";
            }
            ?>
        </h2>
    </div>

    <?php if (!$successMsg): ?>
    <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>">
        <div class="empl">
            <label class="champs">Nom :
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required />
                <span class="error"><?= $nameErr ?></span>
            </label>
            <label class="champs">Prénom :
                <input type="text" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required />
                <span class="error"><?= $prenomErr ?></span>
            </label>
            <label class="champs">Email :
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required />
                <span class="error"><?= $emailErr ?></span>
            </label>
            <label class="champs">Téléphone :
                <input type="tel" name="numeroTel" value="<?= htmlspecialchars($numeroTel) ?>" pattern="[0-9]{10}" maxlength="10" />
                <span class="error"><?= $numeroTelErr ?></span>
            </label>
            <label class="champs">Situation familiale :
                <input type="text" name="situationfamilial" value="<?= htmlspecialchars($situationfamilial) ?>" required />
                <span class="error"><?= $situationfamilialErr ?></span>
            </label>
            <label class="champs">Nationalité :
                <input type="text" name="nationalite" value="<?= htmlspecialchars($nationalite) ?>" required />
                <span class="error"><?= $nationaliteErr ?></span>
            </label>

        </div>

        <div class="dmd">
            <label class="champs">Demande :
                <textarea name="demande" required><?= htmlspecialchars($demande) ?></textarea>
                <span class="error"><?= $demandeErr ?></span>
            </label>
        </div>

        <div style="margin-top: 20px;">
            <?php if ($captchaMessage): ?>
                <p style="color: <?= $captchaValid ? 'green' : 'red' ?>"><?= htmlspecialchars($captchaMessage) ?></p>
            <?php endif; ?>
            <?= \IconCaptcha\Token\IconCaptchaToken::render() ?>
            <div class="iconcaptcha-widget" data-theme="dark"></div>
        </div>

        <br />
        <button type="submit">Envoyer</button>
    </form>
    <?php endif; ?>

<script src="assets/client/js/iconcaptcha.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    IconCaptcha.init('.iconcaptcha-widget', {
        general: {
            endpoint: 'examples/captcha-request.php',
            fontFamily: 'inherit',
        },
        security: {
            interactionDelay: 1000,
            hoverProtection: true,
            displayInitialMessage: true,
            initializationDelay: 500,
            incorrectSelectionResetDelay: 3000,
            loadingAnimationDuration: 1000,
        },
        locale: {
            initialization: {
                verify: 'Confirmez que vous êtes humain.',
                loading: 'Chargement du captcha...',
            },
            header: 'Sélectionnez l’image affichée le <u>moins</u> de fois',
            correct: 'Vérification réussie.',
            incorrect: {
                title: 'Oups.',
                subtitle: "Vous avez sélectionné la mauvaise image."
            },
            timeout: {
                title: 'Veuillez patienter.',
                subtitle: 'Trop d’erreurs consécutives.'
            }
        }
    });
});
</script>
</body>
</html>

<?php
session_start();

// Code PIN à vérifier (à changer selon ton choix)
define('PIN_CODE', '7852');

// Si le formulaire du code PIN est soumis
if (isset($_POST['pin'])) {
    if ($_POST['pin'] === PIN_CODE) {
        $_SESSION['pin_valid'] = true;
    } else {
        $error = "Code incorrect. Réessayez.";
    }
}

// Vérifie si le PIN est validé sinon affiche le formulaire PIN
if (empty($_SESSION['pin_valid'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <title>Accès protégé</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
            input[type="password"] { font-size: 1.2em; padding: 5px; }
            input[type="submit"] { font-size: 1em; padding: 5px 15px; }
            .error { color: red; margin-top: 10px; }
        </style>
    </head>
    <body>
        <h2>Veuillez entrer le code d'accès</h2>
        <form method="post">
            <input type="password" name="pin" maxlength="4" pattern="\d{4}" required autofocus />
            <br><br>
            <input type="submit" value="Valider" />
        </form>
        <?php if (!empty($error)) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
    </body>
    </html>
    <?php
    exit; // On stoppe le reste du script si le PIN n'est pas validé
}

// Si on arrive ici, le PIN est validé, on affiche les données...

$message ="";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/formulaire.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Suppression si demandé
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
    
        $db->beginTransaction();
    
        // Suppression de l'enregistrement
        $stmtDelete = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmtDelete->bindParam(':id', $deleteId, PDO::PARAM_INT);
        $stmtDelete->execute();
    
        // Sauvegarde temporaire
        $db->exec("CREATE TEMPORARY TABLE users_backup AS
                   SELECT name, prenom, email, numeroTel, situationfamilial, nationalite, demande, date_envoi FROM users");
    
        // Suppression table originale
        $db->exec("DROP TABLE users");
    
        // Recréation avec ID auto-incrémenté
        $db->exec("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            prenom TEXT,
            email TEXT,
            numeroTel TEXT,
            situationfamilial TEXT,
            nationalite TEXT,
            demande TEXT,
            date_envoi TEXT
        )");
    
        // Réinsertion depuis la sauvegarde
        $db->exec("INSERT INTO users (name, prenom, email, numeroTel, situationfamilial, nationalite, demande, date_envoi)
                   SELECT name, prenom, email, numeroTel, situationfamilial, nationalite, demande, date_envoi FROM users_backup");
    
        $db->commit();
    
        $_SESSION['message'] = "Formulaire supprimé";
header("Location: stockage.php");
exit;

    }
    

    $result = $db->query("SELECT * FROM users ORDER BY date_envoi DESC");

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Données des utilisateurs</title>
    <link rel="stylesheet" href="styleS.css" />
</head>
<body>
<h2 style="text-align: center;">Liste des formulaires envoyés</h2>

<?php if (!empty($message)): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<?php
if (!empty($_SESSION['message'])) {
    echo '<p class="message">' . htmlspecialchars($_SESSION['message']) . '</p>';
    unset($_SESSION['message']); // Supprime le message après affichage
}
?>

<table>
    <tr class="champs">
        <th>ID</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Situation</th>
        <th>Nationalité</th>
        <th>Demande</th>
        <th>Date d'envoi</th>
        <th>Suppression</th>
    </tr>
    <?php foreach ($result as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['prenom']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['numeroTel']) ?></td>
            <td><?= htmlspecialchars($row['situationfamilial']) ?></td>
            <td><?= htmlspecialchars($row['nationalite']) ?></td>
            <td><?= nl2br(htmlspecialchars($row['demande'])) ?></td>
            <td><?= htmlspecialchars($row['date_envoi']) ?></td>
            <td>
                <form method="post" action="stockage.php" onsubmit="return confirm('Confirmez-vous la suppression ?');">
                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row['id']) ?>">
                    <button type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>

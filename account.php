<?php
session_start();
include 'db.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$utilisateur_id = $_SESSION['utilisateur_id'];
$query = "SELECT * FROM utilisateurs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $utilisateur_id);
$stmt->execute();
$result = $stmt->get_result();
$utilisateur = $result->fetch_assoc();

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier'])) {
    $nom = $_POST['nom'];
    $email = $_POST['email'];

    // Mettre à jour les informations
    $query = "UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $nom, $email, $utilisateur_id);
    $stmt->execute();

    header('Location: account.php'); // Recharger la page après modification
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['supprimer'])) {
    $query = "DELETE FROM utilisateurs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $utilisateur_id);
    $stmt->execute();

    // Déconnexion après suppression
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte</title>
    <link rel="stylesheet" href="account.css">
</head>
<body>

    <!-- Bouton de retour à l'accueil -->
    <div class="back-button">
        <a href="index.php" class="btn">← Retour à l'accueil</a>
    </div>

    <div class="account-container">
        <h1>Mon Compte</h1>
        
        <form method="POST" action="account.php" class="account-form">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="modifier" class="btn modifier-btn">Modifier</button>
                <button type="submit" name="supprimer" class="btn supprimer-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre compte ?');">Supprimer</button>
            </div>
        </form>
    </div>

</body>
</html>

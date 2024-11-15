<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    $mot_de_passe_confirme = $_POST['mot_de_passe_confirme'];
    $role = 'user'; // Rôle par défaut
    $image = NULL; // Valeur par défaut si aucune image n'est envoyée

    // Vérification des champs
    if ($mot_de_passe !== $mot_de_passe_confirme) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'utilisateur existe déjà
        $query = "SELECT * FROM utilisateurs WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $utilisateur_existe = $result->fetch_assoc();

        if ($utilisateur_existe) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            // Insérer le nouvel utilisateur dans la base de données
            $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);

            // Préparer la requête pour ajouter l'utilisateur
            $query = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sssss', $nom, $email, $mot_de_passe_hash, $role, $image);

            if ($stmt->execute()) {
                // Rediriger vers la page de connexion après inscription
                header('Location: login.php');
                exit;
            } else {
                $erreur = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="logreg.css">
</head>
<body>

<!-- Bouton Retour en dehors du container -->
<button onclick="window.location.href='index.php'" class="btn-back">Retour à l'accueil</button>

<div class="auth-container">
    <div class="auth-box">
        <h2>Créer un compte</h2>

        <?php if (isset($erreur)): ?>
            <p class="error-message"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="input-group">
                <label for="nom">Nom et Prénom :</label>
                <input type="text" name="nom" id="nom" required>
            </div>
            <div class="input-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" required>
            </div>
            <div class="input-group">
                <label for="mot_de_passe_confirme">Confirmez le mot de passe :</label>
                <input type="password" name="mot_de_passe_confirme" id="mot_de_passe_confirme" required>
            </div>
            <button type="submit" class="btn">S'inscrire</button>
        </form>

        <p class="auth-link">Déjà inscrit ? <a href="login.php">Connectez-vous ici</a>.</p>
    </div>
</div>

</body>
</html>

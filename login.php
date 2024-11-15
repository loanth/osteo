<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    $query = "SELECT * FROM utilisateurs WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $utilisateur = $result->fetch_assoc();

    if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
        // Stocker les informations dans la session
        $_SESSION['utilisateur_id'] = $utilisateur['id'];
        $_SESSION['role'] = $utilisateur['role']; // Assure que le rôle est récupéré dans la session

        // Vérifier si l'URL contient ?rdv pour redirection vers rdv.php
        if (isset($_GET['rdv'])) {
            header('Location: rdv.php');
        } else {
            // Redirection selon le rôle
            if ($utilisateur['role'] === 'admin') {
                header('Location: admin.php'); // Redirige vers la page admin
            } else {
                header('Location: index.php'); // Redirige vers la page d'accueil
            }
        }
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="logreg.css">
</head>
<body>

<!-- Bouton Retour en dehors du container -->
<button onclick="window.location.href='index.php'" class="btn-back">Retour à l'accueil</button>

<div class="auth-container">
    <div class="auth-box">
        <h2>Connexion</h2>

        <?php if (isset($erreur)): ?>
            <p class="error-message"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php<?= isset($_GET['rdv']) ? '?rdv' : '' ?>">
            <div class="input-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="input-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" required>
            </div>
            <button type="submit" class="btn">Se connecter</button>
        </form>

        <p class="auth-link">Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
    </div>
</div>

</body>
</html>

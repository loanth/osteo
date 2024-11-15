<?php
session_start();
include 'db.php';

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Traitement du formulaire lors de la soumission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_POST['client_id'];
    $date_ouverture = $_POST['date_ouverture'];
    $date_cloture = $_POST['date_cloture'] ?? null;
    $statut = $_POST['statut'];
    $description = $_POST['description'];
    $type_dossier = $_POST['type_dossier'];
    $notes_medicales = $_POST['notes_medicales'];
    $dernier_suivi = $_POST['dernier_suivi'] ?? null;

    // Préparer la requête pour insérer le dossier dans la base de données
    $query = "INSERT INTO dossiers_clients (client_id, date_ouverture, date_cloture, statut, description, type_dossier, notes_medicales, dernier_suivi)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isssssss', $client_id, $date_ouverture, $date_cloture, $statut, $description, $type_dossier, $notes_medicales, $dernier_suivi);

    if ($stmt->execute()) {
        $message = "Dossier ajouté avec succès.";
    } else {
        $message = "Erreur lors de l'ajout du dossier. Veuillez réessayer.";
    }
}

// Récupérer la liste des clients pour la sélection
$query_clients = "SELECT id, nom FROM utilisateurs WHERE role = 'user'";
$result_clients = $conn->query($query_clients);
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Dossier</title>
    <link rel="stylesheet" href="logreg.css">
</head>
<body>
    <!-- Bouton Retour -->
    <button onclick="window.location.href='index.php'" class="btn-back">Retour à l'accueil</button>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Ajouter un Dossier</h2>

            <?php if (isset($message)): ?>
                <p class="message"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" action="ajout_dossier.php">
                <div class="input-group">
                    <label for="client_id">Patient :</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Sélectionnez un patient</option>
                        <?php while ($client = $result_clients->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['nom']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="date_ouverture">Date d'Ouverture :</label>
                    <input type="date" name="date_ouverture" id="date_ouverture" required>
                </div>
                <div class="input-group">
                    <label for="date_cloture">Date de Clôture :</label>
                    <input type="date" name="date_cloture" id="date_cloture">
                </div>
                <div class="input-group">
                    <label for="statut">Statut :</label>
                    <select name="statut" id="statut" required>
                        <option value="ouvert">Ouvert</option>
                        <option value="fermé">Fermé</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="description">Description :</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <div class="input-group">
                    <label for="type_dossier">Type de Dossier :</label>
                    <input type="text" name="type_dossier" id="type_dossier" required>
                </div>
                <div class="input-group">
                    <label for="notes_medicales">Notes Médicales :</label>
                    <textarea name="notes_medicales" id="notes_medicales"></textarea>
                </div>
                <div class="input-group">
                    <label for="dernier_suivi">Dernier Suivi :</label>
                    <input type="date" name="dernier_suivi" id="dernier_suivi">
                </div>
                <button type="submit" class="btn">Ajouter le Dossier</button>
            </form>
        </div>
    </div>
</body>
</html>

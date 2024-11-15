<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$query_utilisateur = "SELECT nom, image FROM utilisateurs WHERE id = " . $_SESSION['utilisateur_id'];
$result_utilisateur = $conn->query($query_utilisateur);
$utilisateur = $result_utilisateur->fetch_assoc();

// Récupérer l'ID du dossier à modifier
$dossier_id = $_GET['id'] ?? null;
if (!$dossier_id) {
    header('Location: dossier.php');
    exit;
}

// Récupérer les informations du dossier et les images associées
$query_dossier = "SELECT * FROM dossiers_clients WHERE id = " . $conn->real_escape_string($dossier_id);
$result_dossier = $conn->query($query_dossier);
$dossier = $result_dossier->fetch_assoc();

$query_images = "SELECT * FROM images_dossiers WHERE dossier_id = " . $conn->real_escape_string($dossier_id);
$result_images = $conn->query($query_images);

// Récupérer les clients pour le champ de sélection
$query_clients = "SELECT id, nom FROM utilisateurs WHERE role = 'user'";
$result_clients = $conn->query($query_clients);

// Gestion de la soumission du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $date_ouverture = $_POST['date_ouverture'];
    $date_cloture = !empty($_POST['date_cloture']) ? $_POST['date_cloture'] : null;
    $statut = $_POST['statut'];
    $description = $_POST['description'];
    $type_dossier = $_POST['type_dossier'];
    $notes_medicales = $_POST['notes_medicales'];
    $dernier_suivi = date('Y-m-d'); // Mettre à jour avec la date du jour

    $update_query = "UPDATE dossiers_clients SET 
        client_id = '$client_id', 
        date_ouverture = '$date_ouverture', 
        date_cloture = " . ($date_cloture ? "'$date_cloture'" : "NULL") . ", 
        statut = '$statut', 
        description = '$description', 
        type_dossier = '$type_dossier', 
        notes_medicales = '$notes_medicales', 
        dernier_suivi = '$dernier_suivi'
        WHERE id = '$dossier_id'";

    if ($conn->query($update_query) === TRUE) {
        // Suppression d'images sélectionnées
        if (!empty($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $image_id) {
                $query_image_path = "SELECT image_path FROM images_dossiers WHERE id = $image_id";
                $result_image_path = $conn->query($query_image_path);
                $image_path = $result_image_path->fetch_assoc()['image_path'];
                
                if (file_exists($image_path)) {
                    unlink($image_path); // Supprimer le fichier du serveur
                }
                
                $conn->query("DELETE FROM images_dossiers WHERE id = $image_id");
            }
        }

        // Gestion de l'upload de nouvelles images
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'uploads/dossiers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Crée le dossier s'il n'existe pas
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['images']['name'][$key]);
                $target_file = $upload_dir . $file_name;
                $file_type = pathinfo($target_file, PATHINFO_EXTENSION);

                if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $insert_image = "INSERT INTO images_dossiers (dossier_id, image_path) VALUES ('$dossier_id', '$target_file')";
                        $conn->query($insert_image);
                    }
                }
            }
        }
        
        header("Location: dossier.php");
        exit;
    } else {
        echo "Erreur lors de la mise à jour : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Dossier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="modifier_dossier.css">
</head>
<body>
    <div class="grid-container">
    <div class="sidebar">
            <div class="profile">
                <?php if (!empty($utilisateur['image'])): ?>
                    <img src="<?= htmlspecialchars($utilisateur['image']); ?>" alt="Image de l'utilisateur">
                <?php else: ?>
                    <img src="./uploads/default_profile.jpg" alt="Image par défaut" style="width: 100%; height: auto; object-fit: cover; border-radius: 50%;">
                <?php endif; ?>
                <h2><?= htmlspecialchars($utilisateur['nom']); ?></h2>
                <p>Ostéopathe</p>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="tableauBord.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                    <li><a href="admin.php"><i class="fas fa-calendar-alt"></i> Calendrier</a></li>
                    <li><a href="ajout_vacances.php"><i class="fas fa-calendar-plus"></i> Ajouter des Vacances</a></li>
                    <li><a href="dossier.php" class="active"><i class="fas fa-folder"></i> Dossiers Patients</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </nav>
        </div>

        <header>
            <h1>Modifier Dossier</h1>
        </header>
        <div class="main-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Champs pour les informations du dossier -->
                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Sélectionner un client</option>
                        <?php while ($client = $result_clients->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($client['id']); ?>" <?= $client['id'] == $dossier['client_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['nom']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_ouverture">Date d'Ouverture</label>
                    <input type="date" id="date_ouverture" name="date_ouverture" value="<?= htmlspecialchars($dossier['date_ouverture']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="date_cloture">Date de Clôture (optionnel)</label>
                    <input type="date" id="date_cloture" name="date_cloture" value="<?= htmlspecialchars($dossier['date_cloture']); ?>">
                </div>

                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" required>
                        <option value="ouvert" <?= $dossier['statut'] == 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                        <option value="fermé" <?= $dossier['statut'] == 'fermé' ? 'selected' : '' ?>>Fermé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type_dossier">Type de Dossier</label>
                    <input type="text" id="type_dossier" name="type_dossier" value="<?= htmlspecialchars($dossier['type_dossier']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($dossier['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="notes_medicales">Notes Médicales</label>
                    <textarea id="notes_medicales" name="notes_medicales" rows="4"><?= htmlspecialchars($dossier['notes_medicales']); ?></textarea>
                </div>

                <!-- Section pour les images existantes -->
                <h3>Images actuelles</h3>
                <div class="current-images">
                    <?php while ($image = $result_images->fetch_assoc()): ?>
                        <div class="image-item">
                            <img src="<?= htmlspecialchars($image['image_path']); ?>" alt="Image du dossier" style="width: 50px; height: 50px; object-fit: cover; margin: 2px;">
                            <label>
                                <input type="checkbox" name="delete_images[]" value="<?= htmlspecialchars($image['id']); ?>"> Supprimer
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Champ pour ajouter de nouvelles images -->
                <div class="form-group">
    <label for="images">Ajouter de nouvelles images</label>
    <label for="images" class="file-upload-label">Sélectionner des images</label>
    <input type="file" id="images" name="images[]" multiple accept="image/*">
    <p>Vous pouvez sélectionner plusieurs images en appuyant sur Ctrl (ou Command sur Mac) lors de la sélection.</p>
</div>

                <button type="submit" class="btn-submit">Mettre à jour le Dossier</button>
            </form>
        </div>
    </div>
</body>
</html>

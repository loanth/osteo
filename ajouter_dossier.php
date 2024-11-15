<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer le nom de l'utilisateur connecté
$query_utilisateur = "SELECT nom, image FROM utilisateurs WHERE id = " . $_SESSION['utilisateur_id'];
$result_utilisateur = $conn->query($query_utilisateur);
$utilisateur = $result_utilisateur->fetch_assoc();

// Récupérer la liste des clients pour le champ de sélection
$query_clients = "SELECT id, nom FROM utilisateurs WHERE role = 'user'";
$result_clients = $conn->query($query_clients);

// Gestion de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $date_ouverture = $_POST['date_ouverture'];
    $date_cloture = !empty($_POST['date_cloture']) ? $_POST['date_cloture'] : null;
    $statut = $_POST['statut'];
    $description = $_POST['description'];
    $type_dossier = $_POST['type_dossier'];
    $notes_medicales = $_POST['notes_medicales'];
    $dernier_suivi = date('Y-m-d'); // Date actuelle pour le dernier suivi

    // Construction de la requête pour insérer le dossier avec gestion des champs optionnels
    $insert_dossier = "INSERT INTO dossiers_clients (client_id, date_ouverture, date_cloture, statut, description, type_dossier, notes_medicales, dernier_suivi) 
                       VALUES ('$client_id', '$date_ouverture', " . ($date_cloture ? "'$date_cloture'" : "NULL") . ", '$statut', '$description', '$type_dossier', '$notes_medicales', '$dernier_suivi')";
    if ($conn->query($insert_dossier) === TRUE) {
        $dossier_id = $conn->insert_id;
        
        // Gestion de l'upload d'images
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = 'uploads/dossiers/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Créé le dossier s'il n'existe pas
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['images']['name'][$key]);
                $target_file = $upload_dir . $file_name;
                $file_type = pathinfo($target_file, PATHINFO_EXTENSION);

                // Vérification du type de fichier (optionnel)
                if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $insert_image = "INSERT INTO images_dossiers (dossier_id, image_path) VALUES ('$dossier_id', '$target_file')";
                        if (!$conn->query($insert_image)) {
                            echo "Erreur lors de l'insertion de l'image dans la base de données : " . $conn->error;
                        }
                    } else {
                        echo "Erreur lors de l'upload de l'image : " . $_FILES['images']['name'][$key];
                    }
                } else {
                    echo "Le format de fichier " . $file_type . " n'est pas supporté pour le fichier : " . $file_name;
                }
            }
        }
        
        header("Location: dossier.php");
        exit;
    } else {
        echo "Erreur lors de l'insertion du dossier : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Nouveau Dossier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="ajouter_dossier.css">
</head>
<body>
    <div class="grid-container">
        <!-- Sidebar -->
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

        <!-- Main content -->
        <header>
            <h1>Ajouter un Nouveau Dossier</h1>
        </header>
        <div class="main-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">Sélectionner un client</option>
                        <?php while ($client = $result_clients->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($client['id']); ?>"><?= htmlspecialchars($client['nom']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_ouverture">Date d'Ouverture</label>
                    <input type="date" id="date_ouverture" name="date_ouverture" required>
                </div>

                <div class="form-group">
                    <label for="date_cloture">Date de Clôture (optionnel)</label>
                    <input type="date" id="date_cloture" name="date_cloture">
                </div>

                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" required>
                        <option value="ouvert">Ouvert</option>
                        <option value="fermé">Fermé</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type_dossier">Type de Dossier</label>
                    <input type="text" id="type_dossier" name="type_dossier" placeholder="Type de dossier" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Description du dossier" required></textarea>
                </div>

                <div class="form-group">
                    <label for="notes_medicales">Notes Médicales</label>
                    <textarea id="notes_medicales" name="notes_medicales" rows="4" placeholder="Notes médicales (optionnel)"></textarea>
                </div>

                <div class="form-group file-label-container">
    <label for="images" class="file-label">Sélectionner des Images</label>
    <input type="file" id="images" name="images[]" class="file-input" multiple accept="image/*">
    <p class="file-description">Vous pouvez sélectionner plusieurs images en appuyant sur Ctrl (ou Command sur Mac) lors de la sélection.</p>
</div>

                <button type="submit" class="btn-submit">Ajouter le Dossier</button>
            </form>
        </div>
    </div>
    <script>
document.querySelector('.file-label').addEventListener('click', function() {
    document.querySelector('.file-input').click();
});

document.querySelector('.file-input').addEventListener('change', function() {
    const label = document.querySelector('.file-label');
    const fileCount = this.files.length;
    label.textContent = fileCount > 0 ? `${fileCount} fichier(s) sélectionné(s)` : 'Sélectionner des Images';
});
</script>
</body>
</html>

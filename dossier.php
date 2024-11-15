<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
$query_clients_list = "SELECT DISTINCT nom FROM utilisateurs WHERE role = 'user'";
$result_clients_list = $conn->query($query_clients_list);
// Récupérer le nom de l'utilisateur connecté
$query_utilisateur = "SELECT nom, image FROM utilisateurs WHERE id = " . $_SESSION['utilisateur_id'];
$result_utilisateur = $conn->query($query_utilisateur);
$utilisateur = $result_utilisateur->fetch_assoc();

// Initialiser les filtres
$client_name = $_GET['client_name'] ?? '';
$date_ouverture = $_GET['date_ouverture'] ?? '';
$date_cloture = $_GET['date_cloture'] ?? '';
$statut = $_GET['statut'] ?? '';
$type_dossier = $_GET['type_dossier'] ?? '';

// Construire la requête SQL avec les filtres
$query_dossiers = "
    SELECT dossiers_clients.*, utilisateurs.nom AS nom_client 
    FROM dossiers_clients
    LEFT JOIN utilisateurs ON dossiers_clients.client_id = utilisateurs.id
    WHERE 1=1
";

// Ajouter des conditions pour chaque filtre
if (!empty($client_name)) {
    $query_dossiers .= " AND utilisateurs.nom LIKE '%" . $conn->real_escape_string($client_name) . "%'";
}
if (!empty($date_ouverture)) {
    $query_dossiers .= " AND dossiers_clients.date_ouverture = '" . $conn->real_escape_string($date_ouverture) . "'";
}
if (!empty($date_cloture)) {
    $query_dossiers .= " AND dossiers_clients.date_cloture = '" . $conn->real_escape_string($date_cloture) . "'";
}
if (!empty($statut)) {
    $query_dossiers .= " AND dossiers_clients.statut = '" . $conn->real_escape_string($statut) . "'";
}
if (!empty($type_dossier)) {
    $query_dossiers .= " AND dossiers_clients.type_dossier LIKE '%" . $conn->real_escape_string($type_dossier) . "%'";
}

$result_dossiers = $conn->query($query_dossiers);

// Charger les images pour chaque dossier
$dossiers_images = [];
while ($dossier = $result_dossiers->fetch_assoc()) {
    $dossier_id = $dossier['id'];
    $query_images = "SELECT image_path FROM images_dossiers WHERE dossier_id = $dossier_id";
    $result_images = $conn->query($query_images);
    
    $images = [];
    while ($image = $result_images->fetch_assoc()) {
        $images[] = $image['image_path'];
    }
    
    $dossier['images'] = $images;
    $dossiers_images[] = $dossier;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossiers Patients</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="dossier.css">
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
            <h1>Dossiers Patients</h1>
        </header>
        <div class="main-content">
            <!-- Bouton pour ajouter un nouveau dossier -->
            <div class="button-container">
                <a href="ajouter_dossier.php" class="add-dossier-btn"><i class="fas fa-plus"></i> Ajouter un Nouveau Dossier</a>
            </div>
            <div class="filters-form">
                <form method="GET" action="">
                <div class="filter-group">
    <label for="client_name">Nom du Client</label>
    <select id="client_name" name="client_name">
        <option value="">Tous</option>
        <?php while ($client = $result_clients_list->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($client['nom']); ?>" <?= $client_name == $client['nom'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($client['nom']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>
                    <div class="filter-group">
                        <label for="date_ouverture">Date d'Ouverture</label>
                        <input type="date" id="date_ouverture" name="date_ouverture">
                    </div>
                    <div class="filter-group">
                        <label for="date_cloture">Date de Clôture</label>
                        <input type="date" id="date_cloture" name="date_cloture">
                    </div>
                    <div class="filter-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="">Tous</option>
                            <option value="ouvert">Ouvert</option>
                            <option value="cloturé">Cloturé</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="type_dossier">Type de Dossier</label>
                        <input type="text" id="type_dossier" name="type_dossier" placeholder="Type de dossier">
                    </div>
                    <button type="submit">Filtrer</button>
                </form>
            </div>

            <section class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du Client</th>
                <th>Images</th>
                <th>Date d'Ouverture</th>
                <th>Date de Clôture</th>
                <th>Statut</th>
                <th>Description</th>
                <th>Type de Dossier</th>
                <th>Notes Médicales</th>
                <th>Dernier Suivi</th>
                <th>Actions</th> <!-- Nouvelle colonne pour les actions -->
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dossiers_images as $dossier): ?>
                <tr>
                    <td><?= htmlspecialchars($dossier['id']); ?></td>
                    <td><?= htmlspecialchars($dossier['nom_client']); ?></td>
                    <td>
    <?php if (!empty($dossier['images'])): ?>
        <?php foreach ($dossier['images'] as $image): ?>
            <a href="javascript:void(0);" onclick="openModal('<?= htmlspecialchars($image); ?>')">
                <img src="<?= htmlspecialchars($image); ?>" alt="Image du dossier" style="width: 50px; height: 50px; object-fit: cover; margin: 2px;">
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <span>Pas d'image</span>
    <?php endif; ?>
</td>
                    <td data-label="Date d'Ouverture"><?= htmlspecialchars($dossier['date_ouverture']); ?></td>
                    <td data-label="Date de Clôture"><?= htmlspecialchars($dossier['date_cloture']); ?></td>
                    <td data-label="Statut"><?= htmlspecialchars($dossier['statut']); ?></td>
                    <td data-label="Description"><?= htmlspecialchars($dossier['description']); ?></td>
                    <td data-label="Type de Dossier"><?= htmlspecialchars($dossier['type_dossier']); ?></td>
                    <td data-label="Notes Médicales"><?= htmlspecialchars($dossier['notes_medicales']); ?></td>
                    <td data-label="Dernier Suivi"><?= htmlspecialchars($dossier['dernier_suivi']); ?></td>
                    <td data-label="Actions">
                    <a href="generer_pdf.php?id=<?= htmlspecialchars($dossier['id']); ?>" target="_blank" class="pdf-btn">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
    <a href="modifier_dossier.php?id=<?= htmlspecialchars($dossier['id']); ?>" class="edit-btn"><i class="fas fa-edit"></i> Modifier</a>
    <form action="supprimer_dossier.php" method="POST" style="display:inline;">
        <input type="hidden" name="dossier_id" value="<?= htmlspecialchars($dossier['id']); ?>">
        <button type="submit" class="delete-btn" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce dossier ?');">
            <i class="fas fa-trash-alt"></i> Supprimer
        </button>
    </form>
</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
        </div>
    </div>
    <div id="imageModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>
<script>
function openModal(imageSrc) {
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("modalImage");
    modal.style.display = "block";
    modalImg.src = imageSrc;
}

// Fermer la modale lorsqu'on clique sur le bouton "close"
var modal = document.getElementById("imageModal");
var span = document.getElementsByClassName("close")[0];

span.onclick = function() { 
    modal.style.display = "none";
}

// Fermer la modale lorsqu'on clique en dehors de l'image
modal.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>

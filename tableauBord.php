<?php
session_start();
include 'db.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer le nom de l'utilisateur connecté
$query_utilisateur = "SELECT nom, image FROM utilisateurs WHERE id = " . $_SESSION['utilisateur_id'];
$result_utilisateur = $conn->query($query_utilisateur);
$utilisateur = $result_utilisateur->fetch_assoc();

// Récupérer les statistiques nécessaires
$query_total_rdv = "SELECT COUNT(*) as total_rdv FROM rendez_vous";
$result_total_rdv = $conn->query($query_total_rdv);
$total_rdv = $result_total_rdv->fetch_assoc()['total_rdv'];

$query_vacances = "SELECT COUNT(*) as total_vacances FROM vacances WHERE date_debut >= CURDATE() AND date_fin >= CURDATE()";
$result_vacances = $conn->query($query_vacances);
$total_vacances = $result_vacances->fetch_assoc()['total_vacances'];

$query_admins = "SELECT COUNT(*) as total_admins FROM utilisateurs WHERE role = 'admin'";
$result_admins = $conn->query($query_admins);
$total_admins = $result_admins->fetch_assoc()['total_admins'];

$query_users = "SELECT COUNT(*) as total_users FROM utilisateurs WHERE role = 'user'";
$result_users = $conn->query($query_users);
$total_users = $result_users->fetch_assoc()['total_users'];

// Récupérer les rendez-vous par mois
$query_rdv_par_mois = "
    SELECT MONTH(date_rdv) as mois, COUNT(*) as total_rdv
    FROM rendez_vous
    GROUP BY MONTH(date_rdv)
    ORDER BY mois ASC";
$result_rdv_par_mois = $conn->query($query_rdv_par_mois);

$rdv_par_mois = [];
while ($row = $result_rdv_par_mois->fetch_assoc()) {
    $rdv_par_mois[] = $row;
}

// Récupérer tous les rendez-vous pour les autres graphiques
$query_rdv_data = "
    SELECT r.date_rdv, t.nom AS type_nom, t.couleur 
    FROM rendez_vous r 
    JOIN type t ON r.type_id = t.id";
$result_rdv_data = $conn->query($query_rdv_data);
$rendez_vous = [];
while ($row = $result_rdv_data->fetch_assoc()) {
    $rendez_vous[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js/dist/Chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="tableauBord.css">
</head>

<body>
    <div class="grid-container">
        <div class="sidebar">
            <div class="profile">
                <img src="<?= htmlspecialchars($utilisateur['image']); ?>" alt="Image de l'utilisateur">
                <h2><?= htmlspecialchars($utilisateur['nom']); ?></h2>
                <p>Ostéopathe</p>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="tableauBord.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                    <li><a href="admin.php"><i class="fas fa-calendar-alt"></i> Calendrier</a></li>
                    <li><a href="ajout_vacances.php"><i class="fas fa-calendar-plus"></i> Ajouter des Vacances</a></li>
                    <li><a href="dossier.php"><i class="fas fa-folder"></i> Dossiers Patients</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </nav>
        </div>

        <header>
            <h1>Tableau de bord</h1>
        </header>

        <div class="main-content">
            <!-- Section des statistiques -->
            <div class="dashboard-stats">
                <div class="stat-box">
                    <i class="fas fa-users"></i>
                    <h3>Total Rendez-vous</h3>
                    <p><?= $total_rdv; ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Vacances Actuelles</h3>
                    <p><?= $total_vacances; ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-user"></i>
                    <h3>Utilisateurs Admins</h3>
                    <p><?= $total_admins; ?></p>
                </div>
                <div class="stat-box">
                    <i class="fas fa-user-friends"></i>
                    <h3>Utilisateurs Simples</h3>
                    <p><?= $total_users; ?></p>
                </div>
            </div>

            <!-- Section des graphiques -->
            <div class="charts">
                <div class="chart-container">
                    <canvas id="monthlyRdvChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="typesChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="rdvChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        try {
            // Assurez-vous que les données JSON sont bien formées
            const rdvData = <?= json_encode($rendez_vous, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
            const rdvParMoisData = <?= json_encode($rdv_par_mois, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

            console.log("Données rdvData:", rdvData);
            console.log("Données rdvParMoisData:", rdvParMoisData);

            const rdvByDate = {};
            const rdvByType = {};
            const typeColors = {};

            rdvData.forEach(rdv => {
                const date = rdv.date_rdv;
                const type = rdv.type_nom;
                const color = rdv.couleur;

                // Comptage des rendez-vous par date
                rdvByDate[date] = (rdvByDate[date] || 0) + 1;

                // Comptage des rendez-vous par type
                rdvByType[type] = (rdvByType[type] || 0) + 1;

                // Associer les couleurs au type
                if (!typeColors[type]) typeColors[type] = color;
            });

            // Graphique pour les rendez-vous par date
            const rdvCtx = document.getElementById('rdvChart').getContext('2d');
            new Chart(rdvCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(rdvByDate),
                    datasets: [{
                        label: 'Rendez-vous par date',
                        data: Object.values(rdvByDate),
                        backgroundColor: '#E8D5CC',
                        borderColor: '#C49D83',
                        borderWidth: 2
                    }]
                }
            });

            // Graphique pour les types de rendez-vous
            const typesCtx = document.getElementById('typesChart').getContext('2d');
            new Chart(typesCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(rdvByType),
                    datasets: [{
                        label: 'Rendez-vous par type',
                        data: Object.values(rdvByType),
                        backgroundColor: Object.keys(typeColors).map(type => typeColors[type]),
                        borderColor: '#E8D5CC',
                        borderWidth: 1
                    }]
                }
            });

            // Graphique pour les rendez-vous par mois
            const moisLabels = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            const monthlyRdvCtx = document.getElementById('monthlyRdvChart').getContext('2d');
            new Chart(monthlyRdvCtx, {
                type: 'line',
                data: {
                    labels: moisLabels,
                    datasets: [{
                        label: 'Rendez-vous par mois',
                        data: moisLabels.map((mois, index) => {
                            const moisIndex = index + 1;
                            const rdvMois = rdvParMoisData.find(rdv => rdv.mois == moisIndex);
                            return rdvMois ? rdvMois.total_rdv : 0;
                        }),
                        backgroundColor: '#E8D5CC',
                        borderColor: '#C49D83',
                        borderWidth: 2
                    }]
                }
            });

            // Graphique pour la répartition des utilisateurs
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            new Chart(usersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Admins', 'Users'],
                    datasets: [{
                        label: 'Répartition des utilisateurs',
                        data: [<?= $total_admins; ?>, <?= $total_users; ?>],
                        backgroundColor: ['#E8D5CC', '#C49D83'],
                        borderColor: ['#E8D5CC', '#D5CABC'],
                        borderWidth: 1
                    }]
                }
            });

        } catch (error) {
            console.error("Erreur lors du traitement des données JSON :", error);
        }
    </script>
</body>
</html>

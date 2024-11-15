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

// Récupérer tous les rendez-vous avec leur type et couleur
$query_rdv = "
    SELECT r.id, r.date_rdv, r.heure_rdv, r.utilisateur_id, u.nom AS utilisateur_nom, t.nom AS type_nom, t.couleur 
    FROM rendez_vous r 
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN type t ON r.type_id = t.id"; 
$result_rdv = $conn->query($query_rdv);
$rendez_vous = [];
while ($row = $result_rdv->fetch_assoc()) {
    $rendez_vous[] = $row;
}

// Récupérer toutes les vacances
$query_vacances = "SELECT date_debut, date_fin FROM vacances";
$result_vacances = $conn->query($query_vacances);
$vacances = [];
while ($row = $result_vacances->fetch_assoc()) {
    $vacances[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda des Rendez-vous</title>
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="grid-container">
        <!-- Sidebar -->
        <div class="sidebar">
        <div class="profile">
    <?php if (!empty($utilisateur['image'])): ?>
        <img src="<?= htmlspecialchars($utilisateur['image']); ?>" alt="Image de l'utilisateur" >
    <?php else: ?>
        <img src="./uploads/Mar_CAR_0070.jpg" alt="Image par défaut" style="width: 100%; height: auto; object-fit: cover; border-radius: 50%;">
    <?php endif; ?>
    <h2><?= htmlspecialchars($utilisateur['nom']); ?></h2> <!-- Nom dynamique de l'utilisateur -->
    <p>Ostéopathe</p>
</div>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="tableauBord.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                    <li><a href="admin.php" class="active"><i class="fas fa-calendar-alt"></i> Calendrier</a></li>
                    <li><a href="ajout_vacances.php"><i class="fas fa-calendar-plus"></i> Ajouter des Vacances</a></li>
                    <li><a href="dossier.php"><i class="fas fa-folder"></i> Dossiers Patients</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </nav>
        </div>

        <!-- Header -->
        <header>
            <h1>Agenda des Rendez-vous</h1>
        </header>

        <!-- Main content with calendar -->
        <div class="main-content">
            <div id="calendar"></div>
        </div>
    </div>

    <script>
        
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

        var vacances = <?php echo json_encode($vacances); ?>;

        var events = [
            <?php foreach ($rendez_vous as $rdv): ?>
            {
                id: '<?= $rdv['id'] ?>',
                title: '<?= $rdv['type_nom'] ?> - <?= $rdv['utilisateur_nom'] ?>',
                start: '<?= $rdv['date_rdv'] . "T" . $rdv['heure_rdv'] ?>',
                end: '<?= date('Y-m-d\TH:i:s', strtotime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv'] . ' + 45 minutes')) ?>',
                color: '<?= $rdv['couleur'] ?>',
                extendedProps: {
                    utilisateurNom: '<?= $rdv['utilisateur_nom'] ?>'
                }
            },
            <?php endforeach; ?>
            
        ];

        vacances.forEach(function(vacance) {
            events.push({
                title: 'Vacances',
                start: vacance.date_debut + 'T00:00:00',
                end: vacance.date_fin + 'T21:59:59',
                color: '#D5CABC'
            });
        });

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'fr',
            allDaySlot: false,
            slotMinTime: "07:30:00",
            slotMaxTime: "18:45:00",
            slotDuration: '00:45:00',
            height: 'auto',
            hiddenDays: [0, 6],
            events: events,

            eventDidMount: function(info) {
                if (info.event.title !== 'Vacances') {
                    var deleteButton = document.createElement('button');
                    deleteButton.innerHTML = 'supp';
                    deleteButton.classList.add('delete-btn');
                    deleteButton.addEventListener('click', function() {
                        if (confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?')) {
                            calendar.getEventById(info.event.id).remove();

                            $.ajax({
                                type: 'POST',
                                url: 'admin_delete_rdv.php',
                                data: { id: info.event.id },
                                success: function(response) {
                                    if (response === 'success') {
                                        alert('Rendez-vous supprimé avec succès.');
                                    } else {
                                        alert('Erreur lors de la suppression du rendez-vous.');
                                    }
                                }
                            });
                        }
                    });

                    var eventContent = info.el.querySelector('.fc-event-title');
                    if (eventContent) {
                        eventContent.appendChild(deleteButton);
                    }
                }
            }
        });

        calendar.render();
    });
    
    </script>
</body>
</html>

<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer
include 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$utilisateur_connecte = isset($_SESSION['utilisateur_id']);

// Rediriger vers login.php si l'utilisateur n'est pas connecté
if (!$utilisateur_connecte) {
    header('Location: login.php?rdv');
    exit(); // Terminer le script pour éviter le chargement de la page
}

// Définir l'encodage UTF-8 pour l'en-tête HTTP
header('Content-Type: text/html; charset=utf-8');

// Configurer l'encodage UTF-8 pour la connexion MySQL
$conn->set_charset("utf8mb4");

$utilisateur_connecte = isset($_SESSION['utilisateur_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_rdv = $_POST['date_rdv'];
    $heure_rdv = $_POST['heure_rdv'];
    $type_rdv = $_POST['type_rdv'];
    $utilisateur_id = $_SESSION['utilisateur_id'];

    // Prépare la requête d'insertion
    $query = "INSERT INTO rendez_vous (date_rdv, heure_rdv, utilisateur_id, type_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssii', $date_rdv, $heure_rdv, $utilisateur_id, $type_rdv);

    if ($stmt->execute()) {
        // Récupérer l'email et le nom de l'utilisateur pour l'email
        $query_email = "SELECT email, nom FROM utilisateurs WHERE id = ?";
        $stmt_email = $conn->prepare($query_email);
        $stmt_email->bind_param('i', $utilisateur_id);
        $stmt_email->execute();
        $result_email = $stmt_email->get_result();
        $user = $result_email->fetch_assoc();
        $email_user = $user['email'];
        $nom_user = $user['nom'];
    
        if (envoyerEmail($email_user, $nom_user, $date_rdv, $heure_rdv)) {
            echo 'success';
        } else {
            echo 'error';
        }
    } else {
        echo 'error';
    }
    
    $stmt->close();
    $conn->close();
    exit;
}    

// Fonction pour envoyer un email
function envoyerEmail($email, $nom_user, $date_rdv, $heure_rdv) {
    $mail = new PHPMailer(true);

    try {
        configurerSMTP($mail);
        
        $mail->setFrom('osteopatheardeche@gmail.com', 'Osteopathe');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation de votre rendez-vous';
        $mail->Body = "
        <html>
        <head>
        <style>
            .email-container {
                font-family: Arial, sans-serif;
                color: #333;
                background-color: #E8D5CC;
                padding: 20px;
                border-radius: 10px;
            }
            .logo {
                text-align: center;
                margin-bottom: 20px;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4b3e3b;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #777;
            }
            
              a {
                color: #f5efe6; /* Couleur normale du lien */
                text-decoration: none; /* Enlève le soulignement si souhaité */
              }
        </style>
    </head>
        <body>
        <div class='email-container'>
        <div class='logo'>
                    <img src='https://img.freepik.com/vecteurs-premium/modele-illustration-conception-icone-vectorielle-symbole-chiropratique_530822-1454.jpg?w=1380' alt='Logo Ostéopathe' width='150'>
                </div>
            <h1>Confirmation de votre rendez-vous</h1>
            <p>Bonjour <strong>$nom_user</strong>,</p>
            <p>Votre rendez-vous est confirmé pour le <strong>$date_rdv</strong> à <strong>$heure_rdv</strong>.</p>
            <p>Merci pour votre confiance !</p>
            <div class='footer'>
                    <p>Ostéopathe Ardèche - 123 Rue de la Santé, 07000 Ardèche</p>
                    <p><a href='http://localhost/osteo/rdv.php' >www.osteopathe-ardeche.com</a></p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}



// Requête pour récupérer les rendez-vous de l'utilisateur connecté
$query = "SELECT id, date_rdv, heure_rdv FROM rendez_vous WHERE utilisateur_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['utilisateur_id']);
$stmt->execute();
$result = $stmt->get_result();
$rendez_vous = [];
while ($row = $result->fetch_assoc()) {
    $rendez_vous[] = $row;
}

// Requêtes pour récupérer les vacances et types de rendez-vous
$query_vacances = "SELECT date_debut, date_fin FROM vacances";
$result_vacances = $conn->query($query_vacances);
$vacances = [];
while ($row = $result_vacances->fetch_assoc()) {
    $vacances[] = $row;
}

$query_types = "SELECT id, nom FROM type";
$result_types = $conn->query($query_types);

$conn->close();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre Rendez-vous</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
</head>
<body>
    <header>
        <nav>
            <div class="menu-toggle" id="mobile-menu">☰</div>
            <ul class="nav-links" id="nav-links">
                <li><a href="index.php#home">Accueil</a></li>
                <li><a href="index.php#about">À propos</a></li>
                <li><a href="index.php#services">Services</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <?php if ($utilisateur_connecte): ?>
                    <li><a href="account.php" class="btn">Mon Compte</a></li>
                    <li><a href="logout.php" class="btn">Déconnexion</a></li>
                    <?php if ($isAdmin): ?>
                        <li><a href="admin.php" class="btn">Admin</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php" class="btn login-btn">Connexion</a></li>
                    <li><a href="register.php" class="btn signup-btn">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <h1>Prendre Rendez-vous</h1>
    </header>

    <section class="rdv-section">
        <h2>Choisissez un créneau dans le calendrier</h2>
        <div class="rdv-container">
            <div id="calendar"></div>
            <form method="POST" action="rdv.php" id="reservation-form">
                <label for="date_rdv">Date sélectionnée : <span id="selected-date-label"></span></label>
                <input type="hidden" name="date_rdv" id="date_rdv">
                <input type="hidden" name="heure_rdv" id="heure_rdv">

                <label for="type_rdv">Type de consultation :</label>
                <select name="type_rdv" id="type_rdv" required>
                    <option value="">-- Sélectionnez un type --</option>
                    <?php while ($type = $result_types->fetch_assoc()): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['nom']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="heure_rdv">Créneaux disponibles :</label>
                <div id="hour-buttons"></div>
                
                <button type="submit" id="submit-btn" style="display: none;">Valider</button>
            </form>
        </div>
    </section>

    <footer>
        <p>Ostéopathe - Tous droits réservés © 2024</p>
    </footer>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var hourButtonsContainer = document.getElementById('hour-buttons');
    var submitButton = document.getElementById('submit-btn');
    var selectedDateLabel = document.getElementById('selected-date-label');
    var selectedDayCell = null;
    var reservationForm = document.getElementById('reservation-form');

    var vacances = <?php echo json_encode($vacances); ?>;

    function estVacances(date) {
        for (var i = 0; i < vacances.length; i++) {
            var debut = new Date(vacances[i].date_debut);
            var fin = new Date(vacances[i].date_fin);
            if (date >= debut && date <= fin) {
                return true;
            }
        }
        return false;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'fr',
    weekends: true,
    selectable: true,
    validRange: {
        start: new Date().toISOString().split('T')[0] // Définit la date de début comme aujourd'hui
    },
    dayCellClassNames: function(arg) {
        var day = arg.date.getDay();
        if (day === 0 || day === 6) {
            return ['weekend-day'];
        }

        var date = new Date(arg.dateStr);
        if (estVacances(date)) {
            return ['vacation-day'];
        }
    },
    dateClick: function(info) {
        var date = new Date(info.dateStr);
        var day = date.getDay();

        // Vérifie si le jour est un week-end ou une date de vacances
        if (day === 6 || day === 0) {
            alert("Les rendez-vous ne sont pas disponibles le week-end.");
            return;
        }

        if (estVacances(date)) {
            alert("Les rendez-vous ne sont pas disponibles pendant les vacances.");
            return;
        }

        reservationForm.style.display = 'block';
        document.getElementById('date_rdv').value = info.dateStr;
        selectedDateLabel.textContent = info.dateStr;

        if (selectedDayCell) {
            selectedDayCell.style.backgroundColor = '';
        }
        selectedDayCell = info.dayEl;
        selectedDayCell.style.backgroundColor = '#BDA18A';

        hourButtonsContainer.innerHTML = '';

        $.ajax({
            type: 'POST',
            url: 'get_creneaux.php',
            data: { date: info.dateStr },
            success: function(response) {
                var reservedTimes = JSON.parse(response);
                reservedTimes = reservedTimes.map(function(time) {
                    return time.slice(0, 5);
                });

                var dayName = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(date);
                var creneaux = generer_creneaux(dayName);

                creneaux.forEach(function(creneau) {
                    if (!reservedTimes.includes(creneau)) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.classList.add('hour-button');
                        button.textContent = creneau;

                        button.addEventListener('click', function() {
                            document.getElementById('heure_rdv').value = creneau;
                            submitButton.style.display = 'block';
                            document.querySelectorAll('.hour-button').forEach(function(btn) {
                                btn.style.backgroundColor = '#BDA18A';
                            });
                            button.style.backgroundColor = '#8c7c6e';
                        });

                        hourButtonsContainer.appendChild(button);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Erreur lors de la récupération des créneaux : ', status, error);
            }
            });
        },

        eventClick: function(info) {
            var eventId = info.event.id;
            var confirmDelete = confirm("Voulez-vous supprimer ce rendez-vous ?");

            if (confirmDelete) {
                $.ajax({
    url: 'delete_rdv.php',
    type: 'POST',
    data: { id: eventId },
    success: function(response) {
        if (response === 'success') {
            info.event.remove();
            alert("Rendez-vous supprimé avec succès !");
        } else {
            alert(response); // Affiche le message d'erreur retourné par PHP
        }
    },
    error: function(xhr, status, error) {
        console.error('Erreur AJAX:', status, error);
        alert("Erreur de connexion. Veuillez réessayer plus tard.");
    }
});

            }
        }
    });

    var rendez_vous = <?php echo json_encode($rendez_vous); ?>;
    rendez_vous.forEach(function(rdv) {
        calendar.addEvent({
            id: rdv.id,
            title: 'Votre RDV',
            start: rdv.date_rdv + 'T' + rdv.heure_rdv,
            color: '#BDA18A'
        });
    });

    vacances.forEach(function(vacance) {
        var debut = new Date(vacance.date_debut);
        var fin = new Date(vacance.date_fin);

        while (debut <= fin) {
            calendar.addEvent({
                title: 'Indisponible',
                start: debut.toISOString().split('T')[0],
                color: '#FF4E50'
            });

            debut.setDate(debut.getDate() + 1);
        }
    });

    calendar.render();

    $('#reservation-form').on('submit', function(e) {
        e.preventDefault();
        reservationForm.style.display = 'none';

        var date_rdv = document.getElementById('date_rdv').value;
        var heure_rdv = document.getElementById('heure_rdv').value;

        $.ajax({
            type: 'POST',
            url: 'rdv.php',
            data: $(this).serialize(),
            success: function(response) {
                if (response === 'success') {
                    alert('Rendez-vous pris avec succès !');

                    location.reload();
                    
                } else {
                    alert('Erreur lors de la prise du rendez-vous.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', status, error);
            }
        });
    });
});

function generer_creneaux(jour) {
    var creneaux = [];
    var debutMatin = new Date('1970-01-01T07:30:00');
    var finMatin = new Date('1970-01-01T12:00:00');
    var debutApresMidi = new Date('1970-01-01T12:45:00');
    var finApresMidi = new Date('1970-01-01T18:00:00');

    var heure = new Date(debutMatin);
    while (heure < finMatin) {
        creneaux.push(heure.toTimeString().slice(0, 5));
        heure.setMinutes(heure.getMinutes() + 45);
    }

    if (jour !== 'Wednesday') {
        heure = new Date(debutApresMidi);
        while (heure < finApresMidi) {
            creneaux.push(heure.toTimeString().slice(0, 5));
            heure.setMinutes(heure.getMinutes() + 45);
        }
    }

    return creneaux;
}

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    const navLinks = document.getElementById('nav-links');

    mobileMenu.addEventListener('click', function() {
        navLinks.classList.toggle('active');
    });
});
    </script>

    <style>
    .vacation-day {
        background-color: #FFD1DC !important; /* Couleur pour les jours de vacances */
    }

    </style>
</body>
</html>

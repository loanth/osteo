<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer
include 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer le nom de l'utilisateur connecté
$query_utilisateur = "SELECT nom, image FROM utilisateurs WHERE id = " . $_SESSION['utilisateur_id'];
$result_utilisateur = $conn->query($query_utilisateur);
$utilisateur = $result_utilisateur->fetch_assoc();

$message = '';
$erreur = '';
$rdvs = [];

// Fonction pour envoyer un email à un utilisateur via Gmail SMTP
function envoyerEmail($email, $nom_user, $date_rdv) {
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP
        configurerSMTP($mail);

        // Configurations de l'email
        $mail->setFrom('osteopatheardeche@gmail.com', 'Osteopathe');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Annulation de votre rendez-vous';

        // Contenu du mail
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
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
                
                a {
                    color: #f5efe6;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='logo'>
                    <img src='https://img.freepik.com/vecteurs-premium/modele-illustration-conception-icone-vectorielle-symbole-chiropratique_530822-1454.jpg?w=1380' alt='Logo Ostéopathe' width='150'>
                </div>
                <h1>Annulation de votre rendez-vous</h1>
                <p>Bonjour <strong>$nom_user</strong>,</p>
                <p>Nous vous informons que votre rendez-vous prévu le <strong>$date_rdv</strong> a été annulé en raison de la fermeture.</p>
                <p>Nous nous excusons pour la gêne occasionnée et vous invitons à consulter notre <a href='http://osteopathe-ardeche.rf.gd/rdv.php'>site web</a> pour reprogrammer votre rendez-vous ou obtenir plus d'informations.</p>
                <p>Merci pour votre compréhension.</p>
                <p><a href='http://osteopathe-ardeche.rf.gd' class='btn'>Visitez notre site</a></p>
                <div class='footer'>
                    <p>Ostéopathe Ardèche - 123 Rue de la Santé, 07000 Ardèche</p>
                    <p><a href='http://osteopathe-ardeche.rf.gd'>www.osteopathe-ardeche.com</a></p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; // En cas d'erreur d'envoi
    }
}

// Gestion de l'ajout des vacances
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'ajout') {
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];

    // Vérifier s'il y a des rendez-vous existants dans cette période
    $query_rdv = "SELECT r.id, r.date_rdv, r.heure_rdv, u.email, u.nom AS utilisateur_nom 
                  FROM rendez_vous r 
                  JOIN utilisateurs u ON r.utilisateur_id = u.id
                  WHERE date_rdv BETWEEN ? AND ?";
    $stmt_rdv = $conn->prepare($query_rdv);
    $stmt_rdv->bind_param('ss', $date_debut, $date_fin);
    $stmt_rdv->execute();
    $result_rdv = $stmt_rdv->get_result();
    $rdvs = [];
    while ($row = $result_rdv->fetch_assoc()) {
        $rdvs[] = $row;
    }
    $stmt_rdv->close();

    // Si des rendez-vous existent, demander confirmation avec le nombre de RDV
    if (count($rdvs) > 0 && !isset($_POST['confirm'])) {
        $message = "Il y a " . count($rdvs) . " rendez-vous programmés entre ces dates. Voulez-vous vraiment continuer et annuler ces rendez-vous ?";
        $_SESSION['vacances_confirm'] = [
            'date_debut' => $date_debut,
            'date_fin' => $date_fin
        ];
    } else {
        // Ajouter les vacances dans la base de données
        $query = "INSERT INTO vacances (date_debut, date_fin) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $date_debut, $date_fin);

        if ($stmt->execute()) {
            $message = "Les vacances ont été ajoutées avec succès.";
        } else {
            $erreur = "Une erreur est survenue lors de l'ajout des vacances.";
        }

        // Supprimer les rendez-vous concernés et envoyer les emails
        foreach ($rdvs as $rdv) {
            $query_delete = "DELETE FROM rendez_vous WHERE id = ?";
            $stmt_delete = $conn->prepare($query_delete);
            $stmt_delete->bind_param('i', $rdv['id']);
            $stmt_delete->execute();
            $stmt_delete->close();

            // Envoyer un email à l'utilisateur avec le nom et la date du rendez-vous
            envoyerEmail($rdv['email'], $rdv['utilisateur_nom'], $rdv['date_rdv']);
        }

        $stmt->close();
    }
}

// Gestion de la suppression des vacances
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'supprimer') {
    $vacance_id = $_POST['id'];

    // Supprimer la vacance de la base de données
    $query_delete_vac = "DELETE FROM vacances WHERE id = ?";
    $stmt_delete_vac = $conn->prepare($query_delete_vac);
    $stmt_delete_vac->bind_param('i', $vacance_id);

    if ($stmt_delete_vac->execute()) {
        $message = "Les vacances ont été supprimées avec succès.";
    } else {
        $erreur = "Une erreur est survenue lors de la suppression des vacances.";
    }

    $stmt_delete_vac->close();
}

// Récupérer toutes les vacances
$query_vacances = "SELECT id, date_debut, date_fin FROM vacances ORDER BY date_debut DESC";
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
    <title>Gestion des Vacances</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="ajout_vacances.css">
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
                    <li><a href="admin.php"><i class="fas fa-calendar-alt"></i> Calendrier</a></li>
                    <li><a href="ajout_vacances.php" class="active"><i class="fas fa-calendar-plus"></i> Ajouter des Vacances</a></li>
                    <li><a href="dossier.php"><i class="fas fa-folder"></i> Dossiers Patients</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main content starts here -->
        
            <header>
                <h1>Gestion des Vacances</h1>
            </header>
            <div class="main-content">
            <section>
                <h2>Ajout de Période de Vacances</h2>

                <!-- Affichage des messages de succès ou d'erreur -->
                <?php if (isset($message)): ?>
                <p class="success-message"><?= htmlspecialchars($message) ?></p>
                <?php if (isset($rdvs) && count($rdvs) > 0 && !isset($_POST['confirm'])): ?>
                <form method="POST" action="ajout_vacances.php" style="display:inline;">
                    <input type="hidden" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
                    <input type="hidden" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
                    <input type="hidden" name="action" value="ajout">
                    <input type="hidden" name="confirm" value="1">
                    <div class="button-container">
                        <button type="submit" class="btn">Confirmer l'annulation des <?= count($rdvs) ?> rendez-vous</button>
                        <a href="ajout_vacances.php" class="btn cancel-btn">Annuler</a>
                    </div>
                </form>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (isset($erreur)): ?>
                <p class="error-message"><?= htmlspecialchars($erreur) ?></p>
                <?php endif; ?>

                <form method="POST" action="ajout_vacances.php">
                    <div>
                        <label for="date_debut">Date de Début :</label>
                        <input type="date" name="date_debut" id="date_debut" required>
                    </div>

                    <div>
                        <label for="date_fin">Date de Fin :</label>
                        <input type="date" name="date_fin" id="date_fin" required>
                    </div>

                    <input type="hidden" name="action" value="ajout">
                    <button type="submit" class="btn">Ajouter les Vacances</button>
                </form>

                <h2>Liste des Vacances</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date de Début</th>
                                <th>Date de Fin</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vacances as $vacance): ?>
                            <tr>
                                <td><?= htmlspecialchars($vacance['date_debut']) ?></td>
                                <td><?= htmlspecialchars($vacance['date_fin']) ?></td>
                                <td>
                                    <form method="POST" action="ajout_vacances.php" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?= $vacance['id'] ?>">
                                        <input type="hidden" name="action" value="supprimer">
                                        <button type="submit" class="btn delete-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div> <!-- End of main-content -->
    </div> <!-- End of grid-container -->
</body>
</html>

<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer
include 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $rdv_id = $_POST['id'];
    $utilisateur_id = $_SESSION['utilisateur_id'];

    // Récupérer les informations du rendez-vous (date, heure) et l'email de l'utilisateur
    $query = "SELECT r.date_rdv, r.heure_rdv, u.email, u.nom AS utilisateur_nom
              FROM rendez_vous r 
              JOIN utilisateurs u ON r.utilisateur_id = u.id 
              WHERE r.id = ? AND r.utilisateur_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $rdv_id, $utilisateur_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rdv = $result->fetch_assoc();

    if (!$rdv) {
        echo 'error'; // Le rendez-vous n'existe pas ou n'appartient pas à l'utilisateur
        exit;
    }

    // Supprimer le rendez-vous de la base de données
    $query_delete = "DELETE FROM rendez_vous WHERE id = ? AND utilisateur_id = ?";
    $stmt_delete = $conn->prepare($query_delete);
    $stmt_delete->bind_param('ii', $rdv_id, $utilisateur_id);

    if ($stmt_delete->execute()) {
        $emailResult = envoyerEmailSuppression($rdv['email'], $rdv['utilisateur_nom'], $rdv['date_rdv'], $rdv['heure_rdv']);
        
        if ($emailResult === true) {
            echo 'success';
        } else {
            echo $emailResult; // Retourne l'erreur d'envoi d'email
        }
    } else {
        echo 'Erreur lors de la suppression du rendez-vous.';
    }
    
    

    $stmt_delete->close();
} else {
    echo 'error'; // Si la requête n'est pas valide
}

// Fonction pour envoyer un email de confirmation de suppression
function envoyerEmailSuppression($email, $utilisateur_nom, $date_rdv, $heure_rdv) {
    $mail = new PHPMailer(true);

    try {
        configurerSMTP($mail);

        $mail->setFrom('osteopatheardeche@gmail.com', 'Osteopathe');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Annulation de votre rendez-vous';
        $mail->Body = "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; color: #333; background-color: #E8D5CC; padding: 20px; border-radius: 10px; }
                .logo { text-align: center; margin-bottom: 20px; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #777; }
                a { color: #f5efe6; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='logo'>
                    <img src='https://img.freepik.com/vecteurs-premium/modele-illustration-conception-icone-vectorielle-symbole-chiropratique_530822-1454.jpg?w=1380' alt='Logo Ostéopathe' width='150'>
                </div>
                <h1>Annulation de votre rendez-vous</h1>
                <p>Bonjour <strong>{$utilisateur_nom}</strong>,</p>
                <p>Votre rendez-vous prévu le <strong>$date_rdv</strong> à <strong>$heure_rdv</strong> a été annulé.</p>
                <p>Nous restons à votre disposition pour toute autre demande.</p>
                <div class='footer'>
                    <p>Ostéopathe Ardèche - 123 Rue de la Santé, 07000 Ardèche</p>
                    <p><a href='http://osteopathe-ardeche.rf.gd/rdv.php'>www.osteopathe-ardeche.com</a></p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo;
    }
}

?>

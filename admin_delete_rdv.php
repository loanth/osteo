<?php
session_start();
include 'db.php'; // Connexion à la base de données
require 'vendor/autoload.php'; // Charger PHPMailer
include 'email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    echo 'error';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $rdv_id = $_POST['id'];

    // Récupérer les informations du rendez-vous et de l'utilisateur
    $query = "
        SELECT r.id, r.date_rdv, r.heure_rdv, u.email, u.nom AS utilisateur_nom, t.nom AS type_nom 
        FROM rendez_vous r 
        JOIN utilisateurs u ON r.utilisateur_id = u.id
        JOIN type t ON r.type_id = t.id 
        WHERE r.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $rdv_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rdv = $result->fetch_assoc();

    if ($rdv) {
        // Supprimer le rendez-vous
        $query_delete = "DELETE FROM rendez_vous WHERE id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param('i', $rdv_id);
        
        if ($stmt_delete->execute()) {
            // Envoyer un email à l'utilisateur
            envoyerEmail($rdv['email'], $rdv['utilisateur_nom'], $rdv['date_rdv'], $rdv['heure_rdv'], $rdv['type_nom']);
            echo 'success';
        } else {
            echo 'error';
        }

        $stmt_delete->close();
    } else {
        echo 'error';
    }

    $stmt->close();
}

// Fonction pour envoyer un email via Gmail SMTP
function envoyerEmail($email, $utilisateur_nom, $date_rdv, $heure_rdv, $type_nom) {
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
                <p>Bonjour <strong>{$utilisateur_nom}</strong>,</p>
                <p>Nous vous informons que votre rendez-vous prévu le <strong>$date_rdv</strong> à <strong>$heure_rdv</strong> pour <strong>$type_nom</strong> a été annulé.</p>
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

        // Envoyer l'email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>

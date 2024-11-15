<?php
session_start();
include 'db.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $utilisateur_id = $_POST['utilisateur_id'];
    $date_rdv = $_POST['date_rdv'];
    $heure_rdv = $_POST['heure_rdv'];
    $duree = $_POST['duree']; // Durée en minutes

    // Préparer la requête pour ajouter un rendez-vous avec durée
    $query = "INSERT INTO rendez_vous (date_rdv, heure_rdv, duree, utilisateur_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssii', $date_rdv, $heure_rdv, $duree, $utilisateur_id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
}

$conn->close();
?>

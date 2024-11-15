<?php
session_start();
include 'db.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID du dossier a été fourni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dossier_id'])) {
    $dossier_id = intval($_POST['dossier_id']);

    // Supprimer les images associées
    $query_images = "SELECT image_path FROM images_dossiers WHERE dossier_id = $dossier_id";
    $result_images = $conn->query($query_images);

    while ($image = $result_images->fetch_assoc()) {
        $image_path = $image['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path); // Supprimer le fichier d'image du serveur
        }
    }

    // Supprimer les entrées dans la table images_dossiers
    $delete_images_query = "DELETE FROM images_dossiers WHERE dossier_id = $dossier_id";
    $conn->query($delete_images_query);

    // Supprimer le dossier
    $delete_dossier_query = "DELETE FROM dossiers_clients WHERE id = $dossier_id";
    if ($conn->query($delete_dossier_query) === TRUE) {
        header('Location: dossier.php'); // Redirection après la suppression
        exit;
    } else {
        echo "Erreur lors de la suppression du dossier : " . $conn->error;
    }
} else {
    echo "ID de dossier non fourni ou méthode de requête invalide.";
}
?>

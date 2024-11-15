<?php
session_start();
include 'db.php'; // Connexion à la base de données

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['heure_rdv'], $_POST['date_rdv'])) {
    $utilisateur_id = $_SESSION['utilisateur_id'];
    $date_rdv = $_POST['date_rdv'];
    $heure_rdv = $_POST['heure_rdv'];

    // Vérifier si le créneau est déjà réservé
    $query = "SELECT * FROM rendez_vous WHERE date_rdv = ? AND heure_rdv = ? AND statut = 'réservé'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $date_rdv, $heure_rdv);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Si le créneau est déjà réservé, afficher un message d'erreur
        echo "Ce créneau est déjà réservé, veuillez en choisir un autre.";
    } else {
        // Sinon, enregistrer la réservation
        $query = "INSERT INTO rendez_vous (utilisateur_id, date_rdv, heure_rdv, statut) VALUES (?, ?, ?, 'réservé')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iss', $utilisateur_id, $date_rdv, $heure_rdv);
        $stmt->execute();

        // Rediriger vers une page de confirmation
        header('Location: confirmation.php');
        exit;
    }
}
?>

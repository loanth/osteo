<?php
include 'db.php'; // Connexion à la base de données

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['date'])) {
    $date_rdv = $_POST['date']; // Récupère la date envoyée

    // Log pour déboguer
    error_log("Date reçue : " . $date_rdv);

    // Sélectionner tous les créneaux réservés pour cette date
    $query = "SELECT heure_rdv FROM rendez_vous WHERE date_rdv = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $date_rdv);
    $stmt->execute();
    $result = $stmt->get_result();

    $reserved_times = [];
    while ($row = $result->fetch_assoc()) {
        $reserved_times[] = $row['heure_rdv']; // Ajoute l'heure réservée dans un tableau
    }

    // Log pour vérifier ce qui est retourné
    error_log("Créneaux réservés pour la date : " . json_encode($reserved_times));

    // Renvoie les créneaux réservés en JSON
    echo json_encode($reserved_times);
}
?>

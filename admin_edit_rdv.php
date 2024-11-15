<?php
session_start();
include 'db.php'; // Connexion à la base de données

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $rdv_id = $_GET['id'];

    // Récupérer les informations du rendez-vous
    $query = "SELECT * FROM rendez_vous WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $rdv_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rdv = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $date_rdv = $_POST['date_rdv'];
        $heure_rdv = $_POST['heure_rdv'];

        // Mettre à jour le rendez-vous
        $update_query = "UPDATE rendez_vous SET date_rdv = ?, heure_rdv = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('ssi', $date_rdv, $heure_rdv, $rdv_id);

        if ($update_stmt->execute()) {
            header('Location: admin_page.php?success=updated');
        } else {
            echo "Erreur lors de la modification du rendez-vous.";
        }

        $update_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Rendez-vous</title>
</head>
<body>
    <h1>Modifier le Rendez-vous</h1>
    <form method="POST">
        <label for="date_rdv">Date :</label>
        <input type="date" name="date_rdv" value="<?= $rdv['date_rdv'] ?>" required>
        <br>

        <label for="heure_rdv">Heure :</label>
        <input type="time" name="heure_rdv" value="<?= $rdv['heure_rdv'] ?>" required>
        <br>

        <button type="submit">Modifier</button>
    </form>
</body>
</html>

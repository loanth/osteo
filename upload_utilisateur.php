<?php
include 'db.php'; // Connexion à la base de données

// Récupérer les données du formulaire
$nom = $_POST['nom'];
$image = null; // Valeur par défaut si aucune image n'est uploadée

// Vérifier si une image a été uploadée
if (!empty($_FILES['image']['name'])) {
    // Dossier où l'image sera stockée
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Vérifier si le fichier est une image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $target_file; // Si l'upload a réussi, on enregistre le chemin de l'image
        } else {
            echo "Erreur lors de l'upload de l'image.";
        }
    } else {
        echo "Le fichier n'est pas une image.";
    }
}

// Insérer l'utilisateur dans la base de données
$query = "INSERT INTO utilisateurs (nom, image) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $nom, $image); // L'image peut être NULL si non uploadée
if ($stmt->execute()) {
    echo "Utilisateur enregistré avec succès.";
} else {
    echo "Erreur lors de l'enregistrement de l'utilisateur.";
}

$conn->close();
?>

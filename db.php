<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "osteopathe";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

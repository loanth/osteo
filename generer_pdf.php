<?php
session_start();
include 'db.php';
require 'vendor/fpdf.php';

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID du dossier depuis l'URL
$dossier_id = $_GET['id'] ?? null;
if (!$dossier_id) {
    die("ID du dossier manquant.");
}

// Requête pour récupérer les informations du dossier
$query_dossier = "
    SELECT dossiers_clients.*, utilisateurs.nom AS nom_client 
    FROM dossiers_clients
    LEFT JOIN utilisateurs ON dossiers_clients.client_id = utilisateurs.id
    WHERE dossiers_clients.id = " . $conn->real_escape_string($dossier_id);
$result_dossier = $conn->query($query_dossier);
$dossier = $result_dossier->fetch_assoc();

if (!$dossier) {
    die("Dossier introuvable.");
}

// Requête pour récupérer les images associées
$query_images = "SELECT image_path FROM images_dossiers WHERE dossier_id = " . $conn->real_escape_string($dossier_id);
$result_images = $conn->query($query_images);
$images = [];
while ($image = $result_images->fetch_assoc()) {
    $images[] = $image['image_path'];
}

// Création du PDF avec FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(33, 37, 41); // Couleur sombre

// En-tête du PDF
$pdf->Cell(0, 10, utf8_decode('Dossier Patient - Fiche Détaillée'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(50, 50, 50); // Couleur des lignes

// Saut de ligne
$pdf->Ln(10);

// Informations générales
$pdf->Cell(0, 10, 'Informations Generales', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Nom du Client:'), 0);
$pdf->Cell(0, 8, utf8_decode($dossier['nom_client']), 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Date d\'Ouverture:'), 0);
$pdf->Cell(0, 8, $dossier['date_ouverture'], 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Date de Clôture:'), 0);
$pdf->Cell(0, 8, utf8_decode($dossier['date_cloture'] ?: 'N/A'), 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Statut:'), 0);
$pdf->Cell(0, 8, utf8_decode(ucfirst($dossier['statut'])), 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Type de Dossier:'), 0);
$pdf->Cell(0, 8, utf8_decode($dossier['type_dossier']), 0, 1);

// Saut de ligne pour la section suivante
$pdf->Ln(10);

// Description et notes médicales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Details du Dossier', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Description:'), 0);
$pdf->MultiCell(0, 8, utf8_decode($dossier['description']));
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Notes Médicales:'), 0);
$pdf->MultiCell(0, 8, utf8_decode($dossier['notes_medicales'] ?: 'N/A'));

// Dernier suivi
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, utf8_decode('Dernier Suivi:'), 0);
$pdf->Cell(0, 8, $dossier['dernier_suivi'], 0, 1);

// Affichage des images
if (!empty($images)) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Images Associees', 0, 1, 'L');
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);
    foreach ($images as $image) {
        $pdf->Image($image, $pdf->GetX(), $pdf->GetY(), 60, 40);
        $pdf->Ln(45);
    }
}

// Générer le PDF dans le navigateur
$pdf->Output('I', 'Dossier_Patient_' . $dossier_id . '.pdf');
?>

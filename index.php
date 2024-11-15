<?php
session_start();

// Vérifier si l'utilisateur est connecté
$utilisateur_connecte = isset($_SESSION['utilisateur_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; // Vérifie si l'utilisateur est un admin
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ostéopathe - Bien-être & Santé</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>
<body id="home">
    <header>
    <nav>
            <!-- Bouton du menu pour mobile -->
            <div class="menu-toggle" id="mobile-menu">
                ☰ <!-- Icône du menu hamburger -->
            </div>
            
            <ul class="nav-links" id="nav-links">
                <li><a href="#home">Accueil</a></li>
                <li><a href="#about">À propos</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="rdv.php#calendar">Prendre RDV</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if ($utilisateur_connecte): ?>
                    <!-- Si l'utilisateur est connecté -->
                    <li><a href="account.php" class="btn">Mon Compte</a></li>
                    <li><a href="logout.php" class="btn">Déconnexion</a></li>
                    <?php if ($isAdmin): ?>
                        <!-- Si l'utilisateur est un admin -->
                        <li><a href="admin.php" class="btn">Admin</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Si l'utilisateur n'est pas connecté -->
                    <li><a href="login.php" class="btn login-btn">Connexion</a></li>
                    <li><a href="register.php" class="btn signup-btn">Inscription</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="hero">
            <div class="hero-text">
            <div class="hero-text typewriter">
    <h1>Votre Bien-être, Notre Priorité</h1>
    <p>Spécialiste en ostéopathie, nous vous accompagnons vers une meilleure santé.</p>
</div>

                <a href="#services" class="btn">Découvrir nos services</a>
            </div>
        </div>
    </header>

    <section id="about" class="about-section">
        <h2>À propos de Nous</h2>
        <p>Nous sommes spécialisés dans la gestion de la douleur, l'amélioration de la posture et le soin des sportifs. Grâce à des techniques douces et adaptées, nous offrons une approche personnalisée pour chaque patient.</p>
    </section>

    <section id="services" class="services-section">
        <h2>Nos Services</h2>
        <div class="services">
            <div class="service-item">
                <img src="images/service1.webp" alt="Consultation Ostéopathique">
                <h3>Consultation Ostéopathique</h3>
                <p>Une approche douce et adaptée pour soulager vos douleurs.</p>
            </div>
            <div class="service-item">
                <img src="images/osteopole_mal_de_dos-scaled.jpg" alt="Suivi Postural">
                <h3>Suivi Postural</h3>
                <p>Améliorez votre posture et votre qualité de vie.</p>
            </div>
            <div class="service-item">
                <img src="images/osteopathie-sport-large.jpg" alt="Soin des sportifs">
                <h3>Soin des sportifs</h3>
                <p>Optimisez vos performances avec un suivi personnalisé.</p>
            </div>
        </div>
    </section>

    <section id="appointment" class="appointment-section">
        <h2>Prendre Rendez-vous</h2>
        <p>Nous vous proposons des créneaux de consultation adaptés à votre emploi du temps. Sélectionnez une date et une heure disponible pour planifier votre séance avec notre ostéopathe.</p>
        <a href="rdv.php" class="btn">Réserver un créneau</a>
    </section>

    <section id="contact" class="contact-section">
    <h2>Contactez-nous</h2>
    <p>N'hésitez pas à nous contacter pour toute question ou information supplémentaire.</p>
    <a href="mailto:loanthomas07@gmail.com" class="btn">Envoyer un email</a>

    <!-- Carte OpenStreetMap avec Leaflet -->
    <div id="map"></div>
</section>

    <script src="script.js"></script>
    <footer>
        <p>Ostéopathe - Tous droits réservés © 2024</p>
    </footer>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Initialiser la carte avec les nouvelles coordonnées et un niveau de zoom de 15
        var map = L.map('map').setView([45.24227980876825, 4.678192990430802], 15);

        // Charger et afficher les tuiles de la carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Ajouter un marqueur à l'emplacement des coordonnées spécifiées
        L.marker([45.24227980876825, 4.678192990430802]).addTo(map)
            .bindPopup("Votre entreprise se situe ici.")
            .openPopup();
    });
    document.addEventListener("DOMContentLoaded", function () {
        const title = document.querySelector(".typewriter h1");
        const subtitle = document.querySelector(".typewriter p");

        // Retarde l'animation du sous-titre après l'animation du titre
        setTimeout(() => {
            subtitle.style.animation = "typing 3s steps(40, end), blink-caret .75s step-end infinite";
        }, 3500); // Commence après l'animation du titre
    });
</script>

</body>
</html>


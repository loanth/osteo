document.addEventListener("DOMContentLoaded", function() {
    const timeSelect = document.getElementById('time');
    const unavailableDates = ['2024-12-25', '2024-01-01']; // Exemples de jours fériés

    // Création des créneaux horaires de 45 minutes
    const generateTimeSlots = () => {
        const slots = [];
        const startTime = new Date();
        startTime.setHours(7, 30, 0, 0);
        const endTime = new Date();
        endTime.setHours(18, 0, 0, 0);

        while (startTime < endTime) {
            const hours = startTime.getHours().toString().padStart(2, '0');
            const minutes = startTime.getMinutes().toString().padStart(2, '0');
            slots.push(`${hours}:${minutes}`);
            startTime.setMinutes(startTime.getMinutes() + 45);
        }

        return slots;
    };

    const populateTimeSlots = () => {
        const slots = generateTimeSlots();
        slots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot;
            option.textContent = slot;
            timeSelect.appendChild(option);
        });
    };

    populateTimeSlots();
});
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    const navLinks = document.getElementById('nav-links');
    const navLinkItems = document.querySelectorAll('.nav-links li a'); // Sélectionne tous les liens du menu

    // Ouvrir/fermer le menu quand on clique sur le bouton hamburger
    mobileMenu.addEventListener('click', function() {
        navLinks.classList.toggle('active');
    });

    // Fermer le menu quand un lien de navigation est cliqué
    navLinkItems.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.classList.remove('active');
        });
    });

    // Fermer le menu si on clique en dehors de celui-ci
    document.addEventListener('click', function(event) {
        const isClickInsideMenu = navLinks.contains(event.target) || mobileMenu.contains(event.target);

        if (!isClickInsideMenu) {
            navLinks.classList.remove('active');
        }
    });
});




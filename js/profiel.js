document.addEventListener('DOMContentLoaded', function() {
    var toggleIcon = document.querySelector('.toggle-contact-icon');
    var contactPopup = document.getElementById('contact-popup');
    var clientPopup = document.querySelector('.Klanten-popup'); // Popup voor klanten
    var contactPopupContent = document.querySelector('.contact-popup-content');
    var closeBtn = document.querySelector('.close-popup');
    var closeClientPopup = document.querySelector('.close-popup'); // Sluitknop voor klanten popup
    var transitionDuration = 500;

    // Functie om de contact-popup te openen
    function openContactPopup() {
        contactPopupContent.classList.remove('active');
        contactPopup.classList.add('active');
        void contactPopupContent.offsetWidth;
        setTimeout(function() {
            contactPopupContent.classList.add('active');
        }, 10);
    }

    // Functie om de klanten-popup te openen
    function openClientPopup() {
        clientPopup.classList.add('active');
    }

    // Functie om de popup te sluiten
    function closePopup() {
        contactPopupContent.classList.remove('active');
        setTimeout(function() {
            contactPopup.classList.remove('active');
        }, transitionDuration);
    }

    // Openen bij klikken op de toggle-knop voor contactinformatie
    toggleIcon.addEventListener('click', openContactPopup);

    // Openen bij klikken op een knop voor klanten (als je die toevoegt in de HTML)
    document.querySelector('.toggle-klanten-info').addEventListener('click', openClientPopup);

    // Sluiten bij klikken op de sluitknop van de contact-popup
    closeBtn.addEventListener('click', closePopup);

    // Sluiten bij klikken op de sluitknop van de klanten-popup
    closeClientPopup.addEventListener('click', function() {
        clientPopup.classList.remove('active');
    });

    // Sluiten bij klikken buiten de popup (op de overlay)
    clientPopup.addEventListener('click', function(e) {
        if (e.target === clientPopup) { // Check of we buiten de inhoud van de popup hebben geklikt
            clientPopup.classList.remove('active');
        }
    });
});

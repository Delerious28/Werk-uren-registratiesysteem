document.addEventListener('DOMContentLoaded', function() {
    var toggleContactIcon = document.querySelector('.toggle-contact-icon');
    var contactPopup = document.getElementById('contact-popup');
    var toggleKlantenInfo = document.querySelector('.toggle-klanten-info');
    var klantenPopup = document.getElementById('klanten-popup');
    var closeContactBtn = document.getElementById('close-contact');
    var closeKlantenBtn = document.getElementById('close-klant');
    
    // Open de contact-popup via fade-in
    function openContactPopup() {
        contactPopup.classList.add('active');
    }

    // Open de klantinformatie-popup via fade-in
    function openKlantenPopup() {
        klantenPopup.classList.add('active');
    }

    // Sluit de contact-popup via fade-out
    function closeContactPopup() {
        contactPopup.classList.remove('active');
    }

    // Sluit de klantinformatie-popup via fade-out
    function closeKlantenPopup() {
        klantenPopup.classList.remove('active');
    }

    // Eventlisteners
    toggleContactIcon.addEventListener('click', openContactPopup);
    toggleKlantenInfo.addEventListener('click', openKlantenPopup);
    closeContactBtn.addEventListener('click', closeContactPopup);
    closeKlantenBtn.addEventListener('click', closeKlantenPopup);

    // Sluit de popups wanneer er buiten de content wordt geklikt
    contactPopup.addEventListener('click', function(e) {
        if (e.target === contactPopup) {
            closeContactPopup();
        }
    });
    klantenPopup.addEventListener('click', function(e) {
        if (e.target === klantenPopup) {
            closeKlantenPopup();
        }
    });
});

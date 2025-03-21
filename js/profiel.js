document.addEventListener('DOMContentLoaded', function() {
    var toggleContactIcon = document.querySelector('.toggle-contact-icon');
    var contactPopup = document.getElementById('contact-popup');
    var toggleKlantenInfo = document.querySelector('.toggle-klanten-info');
    var klantenPopup = document.getElementById('klanten-popup');
    var closeContactBtn = document.getElementById('close-contact');
    var closeKlantenBtn = document.getElementById('close-klant');

    function togglePopup(popup) {
        if (popup) {
            popup.classList.toggle('active');
        }
    }

    if (toggleContactIcon) toggleContactIcon.addEventListener('click', function() { togglePopup(contactPopup); });
    if (toggleKlantenInfo) toggleKlantenInfo.addEventListener('click', function() { togglePopup(klantenPopup); });
    if (closeContactBtn) closeContactBtn.addEventListener('click', function() { togglePopup(contactPopup); });
    if (closeKlantenBtn) closeKlantenBtn.addEventListener('click', function() { togglePopup(klantenPopup); });

    // Sluiten bij klikken buiten de popup
    [contactPopup, klantenPopup].forEach(function(popup) {
        if (popup) {
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    popup.classList.remove('active');
                }
            });
        }
    });

    // Escape-toets om popups te sluiten
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            if (contactPopup?.classList.contains('active')) contactPopup.classList.remove('active');
            if (klantenPopup?.classList.contains('active')) klantenPopup.classList.remove('active');
        }
    });
});

// Toggle de zichtbaarheid van de contactinformatie in een popup
const toggleIcon = document.querySelector('.toggle-contact-icon');
const contactPopup = document.querySelector('#contact-popup');
const closePopup = document.querySelector('.close-popup');

// Open de popup wanneer op het icoon wordt geklikt
toggleIcon.addEventListener('click', () => {
    contactPopup.style.display = 'flex'; // Popup zichtbaar maken
});

// Sluit de popup wanneer de sluitknop wordt geklikt
closePopup.addEventListener('click', () => {
    contactPopup.style.display = 'none'; // Popup verbergen
});

// Sluit de popup wanneer buiten de popup wordt geklikt
window.addEventListener('click', (event) => {
    if (event.target === contactPopup) {
        contactPopup.style.display = 'none';
    }
});

  // Toggle de zichtbaarheid van de contactinformatie met een slide-in animatie
  const toggleIcon = document.querySelector('.toggle-contact-icon');
  const contactInfo = document.querySelector('.contact-info');

  toggleIcon.addEventListener('click', () => {
      contactInfo.classList.toggle('active');
  }); 
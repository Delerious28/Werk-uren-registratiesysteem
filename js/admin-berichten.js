// admin meldingen zoals: nieuwe gebruiker aanmaken, wissen, wijzigen en de uren accorderen meldingen.
document.addEventListener("DOMContentLoaded", function () {
    const container = document.querySelector('.container');
    const approveMessage = document.querySelector('.accorderen-berichten');
    const newUserMessage = document.querySelector('.new-gebruiker-bericht');
    const failUserMessage = document.querySelector('.fout-gebruiker-bericht');

    if (container) {
        if (approveMessage) {
            container.classList.add('accord-verandering');
            console.log('Verandering toegevoegd');
        } else {
            container.classList.remove('accord-verandering');
            console.log('Verandering verwijderd');
        }

        if (newUserMessage || failUserMessage) {
            container.classList.add('verandering');
        } else {
            container.classList.remove('verandering');
        }
    }

    // Zorg dat de accordeer-melding na 2 sec verdwijnt en container wordt gereset
    setTimeout(function () {
        if (approveMessage) {
            approveMessage.style.transition = "opacity 0.5s";
            approveMessage.style.opacity = "0";

            setTimeout(() => {
                approveMessage.remove();

                // Reset de container-stijl
                if (container) {
                    container.classList.remove('accord-verandering');
                }
            }, 500);
        }
    }, 2000);
});

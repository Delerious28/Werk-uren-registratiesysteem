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
});


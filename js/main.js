document.addEventListener("DOMContentLoaded", function () {
    const previousWeekBtn = document.getElementById('previous-week');
    const nextWeekBtn = document.getElementById('next-week');
    const buttons = document.querySelectorAll('.dag');
    const dateCtn = document.querySelector('.date-ctn');
    const successMessage = document.querySelector(".success-message");
    const failMessage = document.querySelector(".fail-message");

    function updateWeekDate(weekStartDate) {
        const endOfWeek = new Date(weekStartDate);
        endOfWeek.setDate(weekStartDate.getDate() + 6);
        updateDateDisplay(weekStartDate);
    }

    function resetSelection() {
        // Verwijder highlight van de dagknoppen, behoud de current-day markering
        buttons.forEach(btn => {
            if (!btn.classList.contains('current-day')) {
                btn.classList.remove('highlight');
            }
        });
        // Verberg de date container
        dateCtn.style.display = "none";
    }

    // Vorige week knop
    previousWeekBtn.addEventListener('click', function () {
        selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() - 7);
        updateWeekDate(selectedWeekStartDate);
        updateDagButtons(selectedWeekStartDate);
        resetSelection(); // Reset selectie bij wisselen van week
    });

    // Volgende week knop
    nextWeekBtn.addEventListener('click', function () {
        const currentDate = new Date();
        const currentWeekStart = new Date(currentDate.setDate(currentDate.getDate() - currentDate.getDay() + 1)); // Start van de huidige week (maandag)

        if (selectedWeekStartDate.getTime() < currentWeekStart.getTime()) {
            selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() + 7);
            updateWeekDate(selectedWeekStartDate);
            updateDagButtons(selectedWeekStartDate);
            resetSelection(); // Reset selectie bij wisselen van week
        }
    });

    function updateDagButtons(weekStartDate) {
        const dagButtons = document.querySelectorAll('.dag');
        for (let i = 0; i < dagButtons.length; i++) {
            let selectedDate = new Date(weekStartDate);
            selectedDate.setDate(weekStartDate.getDate() + i);
            dagButtons[i].dataset.date = selectedDate.toISOString().split('T')[0];

            // Verwijder de 'highlight' klasse van alle knoppen behalve de huidige dag
            if (dagButtons[i].dataset.date === new Date().toISOString().split('T')[0]) {
                dagButtons[i].classList.add('current-day'); // Voeg de 'current-day' klasse toe voor de huidige dag
            }
        }
    }

    function updateDateDisplay(weekStartDate) {
        const weekNum = getWeekNumber(weekStartDate);
        const month = weekStartDate.toLocaleString('nl-NL', { month: 'long' });
        const year = weekStartDate.getFullYear();
        document.getElementById('week-text').textContent = `Week ${weekNum} - ${month} ${year}`;
    }

    function getWeekNumber(date) {
        const tempDate = new Date(date.getTime());
        tempDate.setHours(0, 0, 0, 0);
        tempDate.setDate(tempDate.getDate() + 4 - (tempDate.getDay() || 7)); // Maandag als start
        const yearStart = new Date(tempDate.getFullYear(), 0, 1);
        return Math.ceil((((tempDate - yearStart) / 86400000) + 1) / 7);
    }

    buttons.forEach((button) => {
        button.addEventListener('click', function () {
            // Als de dag al is geselecteerd, reset de selectie
            if (button.classList.contains('highlight')) {
                button.classList.remove('highlight');
                document.getElementById('selected-day').innerText = '';
                dateCtn.style.display = "none";
                return;
            }

            // Verwijder highlight van alle knoppen, behoud de current-day markering
            buttons.forEach(btn => btn.classList.remove('highlight'));

            // Voeg highlight toe aan de aangeklikte dag
            button.classList.add('highlight');

            button.parentNode.insertBefore(dateCtn, button.nextSibling);
            dateCtn.style.display = "block";

            const selectedDate = new Date(button.dataset.date);
            document.getElementById('date-input').value = selectedDate.toISOString().split('T')[0];
            document.getElementById('selected-day').innerText = `${selectedDate.toLocaleDateString('nl-NL')}`;
        });
    });

    // Handeling van succes en foutmeldingen
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = "opacity 0.5s";
            successMessage.style.opacity = "0";
            setTimeout(() => successMessage.remove(), 500);
        }, 3000); // Verwijder na 3 seconden
    }

    if (failMessage) {
        setTimeout(() => {
            failMessage.style.transition = "opacity 0.5s";
            failMessage.style.opacity = "0";
            setTimeout(() => failMessage.remove(), 500);
        }, 3000); // Verwijder na 3 seconden
    }

    updateWeekDate(selectedWeekStartDate);
    updateDagButtons(selectedWeekStartDate);

});

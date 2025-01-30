document.addEventListener("DOMContentLoaded", function () {
    const previousWeekBtn = document.getElementById('previous-week');
    const nextWeekBtn = document.getElementById('next-week');
    const buttons = document.querySelectorAll('.dag');
    const dateCtn = document.querySelector('.date-ctn');
    const weekDateDiv = document.querySelector('.content-container div');

    function updateWeekDate(weekStartDate) {
        const endOfWeek = new Date(weekStartDate);
        endOfWeek.setDate(weekStartDate.getDate() + 6);
        weekDateDiv.textContent = `${weekStartDate.toLocaleDateString('nl-NL')} - ${endOfWeek.toLocaleDateString('nl-NL')}`;
    }

    // Vorige week knop
    previousWeekBtn.addEventListener('click', function () {
        selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() - 7);
        updateWeekDate(selectedWeekStartDate);
        updateDagButtons(selectedWeekStartDate);
    });

    // Volgende week knop
    nextWeekBtn.addEventListener('click', function () {
        const currentDate = new Date();
        const currentWeekStart = new Date(currentDate.setDate(currentDate.getDate() - currentDate.getDay() + 1)); // Start van de huidige week (maandag)

        // Vergelijk de geselecteerde week met de huidige week
        if (selectedWeekStartDate.getTime() < currentWeekStart.getTime()) {
            selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() + 7);
            updateWeekDate(selectedWeekStartDate);
            updateDagButtons(selectedWeekStartDate);
        }
    });

    // Update dagknoppen op basis van week
    function updateDagButtons(weekStartDate) {
        const dagButtons = document.querySelectorAll('.dag');
        for (let i = 0; i < dagButtons.length; i++) {
            let selectedDate = new Date(weekStartDate);
            selectedDate.setDate(weekStartDate.getDate() + i);
            dagButtons[i].dataset.date = selectedDate.toISOString().split('T')[0];
        }
    }

    // Dagknoppen functionaliteit
    buttons.forEach((button, index) => {
        button.addEventListener('click', function () {
            // Als de knop al geselecteerd is, deselecteer deze en sluit het formulier
            if (button.classList.contains('highlight')) {
                button.classList.remove('highlight');
                document.getElementById('selected-day').innerText = '';  // Leeg de geselecteerde dag
                dateCtn.style.display = "none";  // Sluit het formulier
                return;
            }

            // Deseleccteer alle knoppen
            buttons.forEach(btn => btn.classList.remove('highlight'));

            // Markeer de geselecteerde knop
            button.classList.add('highlight');

            button.parentNode.insertBefore(dateCtn, button.nextSibling);
            dateCtn.style.display = "block";

            const weekdays = ["Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag"];
            const selectedDate = new Date(button.dataset.date);
            document.getElementById('date-input').value = selectedDate.toISOString().split('T')[0];
            document.getElementById('selected-day').innerText = `Geselecteerde dag: ${weekdays[index]} (${selectedDate.toLocaleDateString('nl-NL')})`;
        });
    });

    // Initialiseer de weekknoppen
    updateWeekDate(selectedWeekStartDate);
    updateDagButtons(selectedWeekStartDate);
});

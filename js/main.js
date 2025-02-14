document.addEventListener("DOMContentLoaded", function () {
    const previousWeekBtn = document.getElementById('previous-week');
    const nextWeekBtn = document.getElementById('next-week');
    const buttons = document.querySelectorAll('.dag');
    const dateCtn = document.querySelector('.date-ctn');
    const successMessage = document.querySelector(".success-message");
    const failMessage = document.querySelector(".fail-message");

    let currentlySelectedButton = null; // Variabele om de momenteel geselecteerde dagknop bij te houden

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
        const today = new Date();
        const todayFormatted = today.toISOString().split('T')[0];

        dagButtons.forEach((button, index) => {
            let selectedDate = new Date(weekStartDate);
            selectedDate.setDate(weekStartDate.getDate() + index);
            let selectedDateFormatted = selectedDate.toISOString().split('T')[0];

            button.dataset.date = selectedDateFormatted;

            // Verwijder 'current-day' klasse altijd eerst
            button.classList.remove('current-day');

            // Voeg alleen toe als de geselecteerde datum overeenkomt met vandaag
            if (selectedDateFormatted === todayFormatted) {
                button.classList.add('current-day');
            }
        });
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
            const selectedDate = button.dataset.date;

            // Als de dag al geselecteerd is, verberg dan de date-ctn en verwijder de highlight
            if (currentlySelectedButton === button) {
                resetSelection();
                currentlySelectedButton = null; // Reset de selectie
                return; // Stop verdere uitvoering
            }

            // Verwijder highlight van andere knoppen en voeg toe aan de geselecteerde
            buttons.forEach(btn => btn.classList.remove('highlight'));
            button.classList.add('highlight');

            // Zet de momenteel geselecteerde knop
            currentlySelectedButton = button;

            button.parentNode.insertBefore(dateCtn, button.nextSibling);
            dateCtn.style.display = "block";

            const selectedDayDiv = document.getElementById('selected-day');
            const dateInput = document.getElementById('date-input');
            const indienBtn = document.getElementById('indien-btn');

            dateInput.value = selectedDate;

            // Werk de datum weer in de selectedDayDiv
            const selectedDateFormatted = new Date(selectedDate).toLocaleDateString('nl-NL', { year: 'numeric', month: 'long', day: 'numeric' });

            // Maak een nieuwe div voor de datum
            const dateDiv = document.createElement('div');
            dateDiv.classList.add('date-ctn-datum'); // Optioneel: voeg een klasse toe voor styling
            dateDiv.textContent = selectedDateFormatted;

            // Maak de inhoud van selectedDayDiv leeg en voeg de datumdiv toe
            selectedDayDiv.innerHTML = ''; // Verwijder de vorige inhoud
            selectedDayDiv.appendChild(dateDiv); // Voeg de datum toe

            // Controleer of er al uren zijn ingevoerd voor deze datum
            if (hoursData[selectedDate]) {
                selectedDayDiv.innerHTML += `<div class="ingevoerde-uren">Uren: <strong>${hoursData[selectedDate]}</strong></div>`;
                indienBtn.style.display = "none"; // Verberg de 'Indienen' knop
            } else {
                selectedDayDiv.innerHTML += '<div class="input-icon-div">\n' +
                    '                    <input type="number" name="hours" min="0" max="24" required placeholder="Uren">\n' +
                    '                        <img src="img/uren-icon.png" alt="uren icon" class="uren-icon">\n' +
                    '                    </div>';
                indienBtn.style.display = "block"; // Toon de knop als er nog geen uren zijn
            }
        });
    });

    // Sluit de date-ctn als je ergens anders klikt
    document.addEventListener('click', function (event) {
        if (!dateCtn.contains(event.target) && !event.target.classList.contains('dag')) {
            dateCtn.style.display = "none"; // Verberg de date container
            currentlySelectedButton = null; // Reset de geselecteerde knop
            buttons.forEach(btn => btn.classList.remove('highlight')); // Verwijder highlight van alle knoppen
        }
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

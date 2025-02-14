document.addEventListener("DOMContentLoaded", function () {
    const previousWeekBtn = document.getElementById('previous-week');
    const nextWeekBtn     = document.getElementById('next-week');
    const dayButtons      = document.querySelectorAll('.dag');
    const dateContainer   = document.querySelector('.date-ctn');
    const successMessage  = document.querySelector(".success-message");
    const failMessage     = document.querySelector(".fail-message");

    let currentlySelectedButton = null;
    let selectedWeekStartDate = new Date(); // Wordt vervolgens aangepast naar de maandag van de week

    // Functie: bereken de maandag van de week
    function setWeekStartDate(date) {
        const dayOfWeek = date.getDay();
        // getDay() geeft 0 (zondag) t/m 6 (zaterdag)
        const diff = date.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1);
        date.setDate(diff);
        return date;
    }

    selectedWeekStartDate = setWeekStartDate(new Date(selectedWeekStartDate));

    // Update de weektitel (bv. "Week 42 - oktober 2025")
    function updateWeekDisplay(weekStartDate) {
        const weekNum = getWeekNumber(weekStartDate);
        const month   = weekStartDate.toLocaleString('nl-NL', { month: 'long' });
        const year    = weekStartDate.getFullYear();
        document.getElementById('week-text').textContent = `Week ${weekNum} - ${month} ${year}`;
    }

    // Bereken weeknummer (ISO 8601)
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    // Werk de datum (en dataset) van de dagknoppen bij op basis van de geselecteerde week
    function updateDayButtons(weekStartDate) {
        dayButtons.forEach((button, index) => {
            let dayDate = new Date(weekStartDate);
            dayDate.setDate(weekStartDate.getDate() + index);
            let dayDateFormatted = dayDate.toISOString().split('T')[0];
            button.dataset.date = dayDateFormatted;
            button.classList.remove('current-day');
            const todayFormatted = new Date().toISOString().split('T')[0];
            if (dayDateFormatted === todayFormatted) {
                button.classList.add('current-day');
            }
        });
    }

    // Navigatie: vorige week
    previousWeekBtn.addEventListener('click', function () {
        selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() - 7);
        updateWeekDisplay(selectedWeekStartDate);
        updateDayButtons(selectedWeekStartDate);
        resetSelection();
    });

    // Navigatie: volgende week (niet verder dan de huidige week)
    nextWeekBtn.addEventListener('click', function () {
        const currentDate = new Date();
        const currentWeekStart = setWeekStartDate(new Date(currentDate));
        if (selectedWeekStartDate.getTime() < currentWeekStart.getTime()) {
            selectedWeekStartDate.setDate(selectedWeekStartDate.getDate() + 7);
            updateWeekDisplay(selectedWeekStartDate);
            updateDayButtons(selectedWeekStartDate);
            resetSelection();
        }
    });

    // Reset de selectie: verwijder highlight en verberg de datumcontainer
    function resetSelection() {
        dayButtons.forEach(btn => {
            if (!btn.classList.contains('current-day')) {
                btn.classList.remove('highlight');
            }
        });
        dateContainer.style.display = "none";
        currentlySelectedButton = null;
    }

    // Klik op een dagknop
    dayButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation(); // Voorkom dat het event verder omhoog gaat
            const selectedDate = button.dataset.date;

            // Als deze dag al geselecteerd is, deselecteer dan
            if (currentlySelectedButton === button) {
                resetSelection();
                return;
            }

            // Highlight de geselecteerde knop en deselecteer andere knoppen
            dayButtons.forEach(btn => btn.classList.remove('highlight'));
            button.classList.add('highlight');
            currentlySelectedButton = button;

            // Plaats de datumcontainer direct na de geselecteerde knop en toon deze
            button.parentNode.insertBefore(dateContainer, button.nextSibling);
            dateContainer.style.display = "block";

            const selectedDayDiv = document.getElementById('selected-day');
            const dateInput      = document.getElementById('date-input');
            const submitBtn      = document.getElementById('indien-btn');

            dateInput.value = selectedDate;

            // Converteer de datum naar een gestandaardiseerd formaat
            const formattedDate = new Date(selectedDate).toISOString().split('T')[0];
            const selectedDateFormatted = new Date(selectedDate).toLocaleDateString('nl-NL', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Bouw de inhoud van de datumweergave op
            selectedDayDiv.innerHTML = '';
            let dateDiv = document.createElement('div');
            dateDiv.classList.add('date-ctn-datum');
            dateDiv.textContent = selectedDateFormatted;
            selectedDayDiv.appendChild(dateDiv);

            // Controleer of er al uren zijn ingevoerd voor deze datum via het globale hoursData-object
            if (hoursData[formattedDate]) {
                let hoursDiv = document.createElement('div');
                hoursDiv.classList.add('ingevoerde-uren');
                hoursDiv.innerHTML = `Uren: <strong>${hoursData[formattedDate]}</strong>`;
                selectedDayDiv.appendChild(hoursDiv);
                submitBtn.style.display = "none"; // Verberg de submit-knop als er al uren zijn
            } else {
                let inputDiv = document.createElement('div');
                inputDiv.classList.add('input-icon-div');
                inputDiv.innerHTML = `
                    <input type="number" name="hours" min="0" max="24" required placeholder="Uren">
                    <img src="img/uren-icon.png" alt="uren icon" class="uren-icon">
                `;
                selectedDayDiv.appendChild(inputDiv);
                submitBtn.style.display = "block";
            }
        });
    });

    // Sluit de datumcontainer als er ergens buiten geklikt wordt
    document.addEventListener('click', function (event) {
        if (!dateContainer.contains(event.target) && !event.target.classList.contains('dag')) {
            dateContainer.style.display = "none";
            currentlySelectedButton = null;
            dayButtons.forEach(btn => btn.classList.remove('highlight'));
        }
    });

    // Verwijder succes- en foutmeldingen na 3 seconden
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = "opacity 0.5s";
            successMessage.style.opacity = "0";
            setTimeout(() => successMessage.remove(), 500);
        }, 3000);
    }

    if (failMessage) {
        setTimeout(() => {
            failMessage.style.transition = "opacity 0.5s";
            failMessage.style.opacity = "0";
            setTimeout(() => failMessage.remove(), 500);
        }, 3000);
    }

    // Initialiseer de weergave
    updateWeekDisplay(selectedWeekStartDate);
    updateDayButtons(selectedWeekStartDate);
});

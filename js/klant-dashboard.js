document.addEventListener('DOMContentLoaded', function () {
    // Maanden dynamisch toevoegen aan de dropdown
    const monthSelect = document.getElementById('month-select');
    const currentYear = new Date().getFullYear();
    const months = [
        'Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni',
        'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'
    ];

    // Voeg de maanden toe aan de dropdown
    months.forEach((month, index) => {
        const option = document.createElement('option');
        option.value = `${currentYear}-${String(index + 1).padStart(2, '0')}`;  // YYYY-MM formaat
        option.textContent = month;
        monthSelect.appendChild(option);
    });

    // Functie om de dropdown-content te tonen of verbergen
    function toggleDropdown() {
        const dropdownContent = document.getElementById("dropdown-content");
        dropdownContent.style.display = dropdownContent.style.display === 'none' ? 'block' : 'none';
    }

    // Voeg event listener toe aan de knop om de dropdown te tonen of verbergen
    document.getElementById("dropdown-btn").addEventListener('click', function (event) {
        event.stopPropagation(); // Voorkom dat het venster-klikevent de dropdown meteen sluit
        toggleDropdown();
    });

    // Verberg dropdown zodra op een accordeer-knop wordt geklikt
    document.querySelectorAll(".approve-month").forEach(button => {
        button.addEventListener("click", function () {
            const dropdownContent = document.getElementById("dropdown-content");
            if (dropdownContent) {
                dropdownContent.style.display = "none";
            }
        });
    });

    // Verberg dropdown als er buiten wordt geklikt
    document.addEventListener("click", function (event) {
        const dropdownContent = document.getElementById("dropdown-content");
        const dropdownBtn = document.getElementById("dropdown-btn");

        // Controleer of de klik buiten de dropdown-content en de knop was
        if (dropdownContent && dropdownContent.style.display === "block" &&
            !dropdownContent.contains(event.target) &&
            !dropdownBtn.contains(event.target)) {
            dropdownContent.style.display = "none";
        }
    });

    // Event listener voor de status dropdowns
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function () {
            const hoursId = this.getAttribute('data-hours-id');
            const status = this.value;

            updateStatus(hoursId, status, dropdown);
        });

        // Initialiseer de kleur van de dropdown bij het laden
        updateDropdownColor(dropdown, dropdown.value);
    });

    // Functie om de kleur van de dropdown aan te passen op basis van de status
    function updateDropdownColor(dropdown, status) {
        dropdown.classList.remove('approved', 'rejected', 'pending');
        if (status === 'Approved') {
            dropdown.classList.add('approved');
        } else if (status === 'Rejected') {
            dropdown.classList.add('rejected');
        } else if (status === 'Pending') {
            dropdown.classList.add('pending');
        }
    }

    // Functie voor het bijwerken van de status van uren
    function updateStatus(hoursId, status, dropdown) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `hours_id=${encodeURIComponent(hoursId)}&status=${encodeURIComponent(status)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message);
                    updateDropdownColor(dropdown, status);
                } else {
                    showFailMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Fout bij het bijwerken van de status:', error);
                showFailMessage('Fout bij het bijwerken van de status.');
            });
    }

    // Functie voor het tonen van succesbericht
    function showSuccessMessage(message) {
        showMessage('klant-success-mess', message);
    }

    // Functie voor het tonen van foutbericht
    function showFailMessage(message) {
        showMessage('klant-fail-mess', message);
    }

    // Algemene functie voor het tonen van berichten
    function showMessage(className, message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add(className);  // Voegt de juiste klasse toe (bijv. 'klant-fail-mess' voor foutmeldingen)
        messageElement.textContent = message;
        document.querySelector('.container').prepend(messageElement);

        setTimeout(function() {
            messageElement.remove();
        }, 5000);
    }

    // Event listener voor de goedkeurknop van de maand
    document.querySelector('.approve-month').addEventListener('click', function () {
        const selectedMonth = monthSelect.value;

        if (selectedMonth) {
            approveMonth(selectedMonth);
        } else {
            showFailMessage('Selecteer een maand om te accorderen.');
        }
    });

    // Functie voor het goedkeuren van de geselecteerde maand
    function approveMonth(month) {
        fetch('update-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'month=' + encodeURIComponent(month)
        })
            .then(response => response.json())
            .then(data => {
                // Controleer of de status 'error' is om te bepalen of het een fout is
                if (data.status === 'success') {
                    showSuccessMessage(data.message);
                    setTimeout(function (){
                        location.reload();
                    }, 4000);
                } else {
                    showFailMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Er is een fout opgetreden:', error);
                showFailMessage('Er is een fout opgetreden bij het verzenden van de aanvraag.');
            });
    }

    // Voeg click events toe aan alle elementen met de class view-user-profile
    const userProfileElements = document.querySelectorAll('.view-user-profile');
    userProfileElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            fetchUserProfile(userId);
        });
    });

    // Functie om het gebruikersprofiel op te halen en de pop-up te tonen
    function fetchUserProfile(userId) {
        fetch('gebruiker-profiel-klant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    // Vul de popup velden in
                    document.getElementById('popup-name').textContent = data.name || '';
                    document.getElementById('popup-achternaam').textContent = data.achternaam || '';
                    document.getElementById('popup-email').textContent = data.email || '';
                    document.getElementById('popup-telefoon').textContent = data.telefoon || '';
                    document.getElementById('popup-role').textContent = data.role || '';
                    // Toon de pop-up
                    document.getElementById('gebruikerPopup').style.display = 'block';
                }
            })
            .catch(error => console.error('Fout bij ophalen gebruikersprofiel:', error));
    }

    document.querySelector('.close-popup').addEventListener('click', function () {
        document.getElementById('gebruikerPopup').style.display = 'none';
    });

    // Sluit de popup als je buiten klikt
    document.getElementById('gebruikerPopup').addEventListener('click', function (e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });

});

function updateGebruikerFilter() {
    const userId = document.getElementById("gebruikerFilter").value;
    const urlParams = new URLSearchParams(window.location.search);

    if (userId) {
        urlParams.set("user_id", userId);
    } else {
        urlParams.delete("user_id");
    }

    window.location.search = urlParams.toString();
}


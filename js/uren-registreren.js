document.addEventListener('DOMContentLoaded', function() {
    const duplicateMessage = document.querySelector('.dupliceer-bericht');
    if (duplicateMessage) {
        setTimeout(() => {
            duplicateMessage.remove();
        }, 4000);
    }

    // Verkrijg het modale venster en de sluitknop
    var modal = document.getElementById("urenInformatieModal");
    var span = document.getElementsByClassName("close")[0];

    // Functie om het modale venster te openen
    function openModal(content) {
        document.getElementById("modalContent").innerHTML = content; // Vul het modale venster met inhoud
        modal.style.display = "flex"; // Gebruik flex om het in het midden te plaatsen
    }

    // Functie om het modale venster te sluiten
    span.onclick = function() {
        modal.style.display = "none";
    }

    // Sluit het modale venster als de gebruiker ergens buiten de pop-up klikt
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Functie om de ureninformatie op te halen en in de pop-up te tonen
    function loadUrenInformatie(date) {
        console.log('Laad uren informatie voor datum:', date); // Voeg een log toe voor debugging
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "uren-informatie.php?date=" + date, true); // We roepen een PHP-bestand aan dat de uren opvraagt
        xhr.onload = function() {
            if (xhr.status == 200) {
                openModal(xhr.responseText); // Zet de pop-upinhoud op basis van het antwoord
            } else {
                alert("Er is een fout opgetreden bij het ophalen van de uren.");
            }
        };
        xhr.send();
    }

    // Event listeners toevoegen aan alle info-iconen
    var infoIcons = document.querySelectorAll('.info-icon');
    infoIcons.forEach(function(icon) {
        icon.addEventListener('click', function(e) {
            var date = e.target.closest('a').getAttribute('data-date'); // Haal de datum op uit het data-date attribuut
            loadUrenInformatie(date); // Laad de ureninformatie
        });
    });

    // Voeg hier je bestaande formuliervalidatie toe (zoals eerder gedefinieerd)
    document.getElementById("urenForm").addEventListener("submit", function(event) {
        let isValid = true;

        // Haal de invoervelden op
        let klant = document.querySelector("select[name='klant']");
        let project = document.querySelector("select[name='project']");
        let begin = document.querySelector("select[name='begin']");
        let eind = document.querySelector("select[name='eind']");

        // Haal de foutmelding elementen op
        let errorKlant = document.getElementById("error-klant");
        let errorProject = document.getElementById("error-project");
        let errorBegin = document.getElementById("error-begin");
        let errorEind = document.getElementById("error-eind");

        // Reset foutmeldingen en borders
        errorKlant.textContent = "";
        errorProject.textContent = "";
        errorBegin.textContent = "";
        errorEind.textContent = "";
        klant.style.border = "";
        project.style.border = "";
        begin.style.border = "";
        eind.style.border = "";

        // Validatie
        if (klant.value === "") {
            errorKlant.textContent = "Klant is verplicht.";
            klant.style.border = "2px solid #6D0F10"; // Rode rand voor klant
            isValid = false;
        }
        if (project.value === "") {
            errorProject.textContent = "Project is verplicht.";
            project.style.border = "2px solid #6D0F10"; // Rode rand voor project
            isValid = false;
        }
        if (begin.value === "") {
            errorBegin.textContent = "Starttijd is verplicht.";
            begin.style.border = "2px solid #6D0F10"; // Rode rand voor begintijd
            isValid = false;
        }
        if (eind.value === "") {
            errorEind.textContent = "Eindtijd is verplicht.";
            eind.style.border = "2px solid #6D0F10"; // Rode rand voor eindtijd
            isValid = false;
        }

        // Voorkom het verzenden van het formulier als niet alles correct is ingevuld
        if (!isValid) {
            event.preventDefault();
        }
    });

});

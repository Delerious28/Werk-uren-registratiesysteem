// admin-index.php

document.addEventListener("DOMContentLoaded", function () {
    // Haal de container en berichten op
    const container = document.querySelector('.container');
    const approveMessage = document.querySelector('.accorderen-berichten');
    const newUserMessage = document.querySelector('.new-gebruiker-bericht');
    const failUserMessage = document.querySelector('.fout-gebruiker-bericht');

    // Logica voor de accorderen-berichten
    if (approveMessage) {
        // Voeg de 'verandering' class toe aan de container als het bericht wordt gevonden
        container.classList.add('accord-verandering');
        console.log('Verandering toegevoegd'); // Log als de class wordt toegevoegd
    } else {
        // Verwijder de 'verandering' class als het bericht niet wordt gevonden
        container.classList.remove('accord-verandering');
        console.log('Verandering verwijderd'); // Log als de class wordt verwijderd
    }

    // Logica voor de berichten over nieuwe of mislukte gebruikers
    if (newUserMessage || failUserMessage) {
        // Voeg de 'verandering' class toe aan de container als een bericht aanwezig is
        container.classList.add('verandering');
    } else {
        // Verwijder de 'verandering' class als er geen berichten zijn
        container.classList.remove('verandering');
    }

    // 1. Voeg click listeners toe aan de "Wijzigen" knoppen om de rij als actief te markeren
    document.querySelectorAll(".edit-btn").forEach(function(button) {
        button.addEventListener("click", function(e) {
            e.preventDefault();
            // Verwijder de "active-edit" class van alle rijen
            document.querySelectorAll("tr.active-edit").forEach(function(row) {
                row.classList.remove("active-edit");
            });
            // Markeer de rij met de actieve knop als actief
            var row = button.closest("tr");
            row.classList.add("active-edit");
        });
    });

    // 2. Voeg inline editing toe aan uur cellen, maar alleen als de rij actief is
    var editableCells = [];
    if (filter === "week") {
        // Als de filter "week" is, voeg dan alle bewerkbare cellen toe
        editableCells = document.querySelectorAll("td.uren-row.editable");
    } else if (filter === "vandaag") {
        // Als de filter "vandaag" is, voeg dan de bewerkbare cellen van de "vandaag" tabel toe
        editableCells = document.querySelectorAll('table[data-filter="vandaag"] tbody tr td.editable');
    }

    // Weekweergave, koppel de celindex (relatief ten opzichte van de rij) aan de dagcode
    // Cel 0 is naam; 1: Ma, 2: Di, 3: Wo, 4: Do, 5: Vr.
    var dayMapping = {1: "Ma", 2: "Di", 3: "Wo", 4: "Do", 5: "Vr"};

    editableCells.forEach(function(cell) {
        cell.style.cursor = "pointer";
        cell.addEventListener("click", function(e) {
            // Sta bewerken alleen toe als de rij actief is
            if (!cell.parentNode.classList.contains("active-edit")) return;
            // Voorkom meerdere invoeren in dezelfde cel
            if (cell.querySelector("input")) return;

            var originalValue = cell.textContent.replace(" Totaal", "").trim();
            cell.innerHTML = "";
            var input = document.createElement("input");
            input.type = "number";
            input.min = 0;
            input.max = 24;
            input.value = originalValue;
            input.className = "inline-edit";
            cell.appendChild(input);
            input.focus();

            // Flag om bij te houden of Enter is ingedrukt (bevestigde updates)
            let updateConfirmed = false;

            // Event listener voor het indrukken van Enter om de waarde bij te werken
            input.addEventListener("keydown", function(ev) {
                if (ev.key === "Enter") {
                    ev.preventDefault();
                    updateConfirmed = true;
                    updateValue(cell, input.value);
                    input.blur();
                }
            });

            // Event listener voor wanneer de invoer de focus verliest (waardes niet bevestigd)
            input.addEventListener("blur", function() {
                // Als Enter niet werd ingedrukt, herstel de originele waarde
                if (!updateConfirmed) {
                    cell.textContent = originalValue;
                }
                // Verwijder de "active-edit" klasse van de rij
                cell.parentNode.classList.remove("active-edit");
            });
        });
    });

    // Functie om de waarde bij te werken
    function updateValue(cell, newValue) {
        var row = cell.parentNode;
        var userId = row.getAttribute("data-user-id");
        var day = "";

        // Als de filter "week" is, bepaal de dag op basis van de rij
        if (filter === "week") {
            var cells = Array.from(row.children);
            var cellIndex = cells.indexOf(cell);
            day = dayMapping[cellIndex] || "";
        }

        // Werk de cel onmiddellijk bij met de nieuwe waarde die de gebruiker heeft ingevoerd
        cell.textContent = newValue;

        // Dien het verborgen formulier in om de database bij te werken
        var form = document.getElementById("hiddenUpdateForm");
        form.user_id.value = userId;
        form.filter.value = filter;
        form.day.value = day;
        form.hours.value = newValue; // De nieuwe waarde die door de gebruiker is ingevoerd
        form.submit();
    }
});

// admin-gebruikers.php

// Open de overlay en toon het formulier voor Nieuwe Gebruiker
function showCreateUserForm() {
    document.getElementById('userFormOverlay').style.display = 'flex';
    document.getElementById('editUserForm').style.display = 'none';
    document.getElementById('createUserForm').style.display = 'block';
}

// Open de overlay en toon het formulier voor het bewerken van een gebruiker, vul de velden in
function editUser(id, name, role) {
    document.getElementById('userFormOverlay').style.display = 'flex';
    document.getElementById('createUserForm').style.display = 'none';
    document.getElementById('editUserForm').style.display = 'block';
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_role').value = role;
}

// Sluit de overlay (verberg beide formulieren)
function closeUserForm() {
    document.getElementById('userFormOverlay').style.display = 'none';
}

// Eenvoudige formuliervalidatie om naam en wachtwoordregels te controleren
function validateForm(form) {
    const name = form.querySelector('[name="name"]').value;
    const password = form.querySelector('[name="password"]').value;

    if (/\d/.test(name)) {
        alert('Naam mag geen cijfers bevatten.');
        return false;
    }
    if (password.length > 0 && password.length < 5) {
        alert('Wachtwoord moet meer dan 4 tekens bevatten.');
        return false;
    }
    return true;
}

// Eind admin-gebruikers.php

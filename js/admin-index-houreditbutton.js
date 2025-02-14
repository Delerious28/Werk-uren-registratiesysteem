document.addEventListener("DOMContentLoaded", function() {
    var filter = "<?= $filter ?>";

    // 1. Voeg klikluisteraars toe aan de "Wijzigen" knoppen om hun rij als actief te markeren.
    document.querySelectorAll(".edit-btn").forEach(function(button) {
        button.addEventListener("click", function(e) {
            e.preventDefault();
            // Verwijder de "active-edit" klasse van alle rijen.
            document.querySelectorAll("tr.active-edit").forEach(function(row) {
                row.classList.remove("active-edit");
            });
            // Markeer de rij waarin deze knop zich bevindt als actief.
            var row = button.closest("tr");
            row.classList.add("active-edit");
        });
    });

    // 2. Maak de cellen met uren bewerkbaar, maar alleen als hun rij actief is.
    var editableCells = [];
    if (filter === "week") {
        editableCells = document.querySelectorAll("td.uren-row.editable");
    } else if (filter === "vandaag") {
        editableCells = document.querySelectorAll('table[data-filter="vandaag"] tbody tr td.editable');
    }

    // Voor de weekweergave, koppel celindex (relatief aan rij) aan dagcode.
    // Cel 0 is naam; 1: Ma, 2: Di, 3: Wo, 4: Do, 5: Vr.
    var dayMapping = {1: "Ma", 2: "Di", 3: "Wo", 4: "Do", 5: "Vr"};

    editableCells.forEach(function(cell) {
        cell.style.cursor = "pointer";
        cell.addEventListener("click", function(e) {
            // Sta bewerken alleen toe als de rij actief is.
            if (!cell.parentNode.classList.contains("active-edit")) return;
            // Voorkom dat meerdere invoervelden in dezelfde cel worden gemaakt.
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

            // Vlag om bij te houden of Enter is ingedrukt (bevestiging update)
            let updateConfirmed = false;

            input.addEventListener("keydown", function(ev) {
                if (ev.key === "Enter") {
                    ev.preventDefault();
                    updateConfirmed = true;
                    updateValue(cell, input.value);
                    input.blur();
                }
            });

            input.addEventListener("blur", function() {
                // Als Enter niet is ingedrukt, herstel de oorspronkelijke waarde.
                if (!updateConfirmed) {
                    cell.textContent = originalValue;
                }
                // Verwijder de "active-edit" klasse van de rij.
                cell.parentNode.classList.remove("active-edit");
            });
        });
    });

    // Functie om de nieuwe waarde op te slaan en te verzenden naar de server.
    function updateValue(cell, newValue) {
        var row = cell.parentNode;
        var userId = row.getAttribute("data-user-id");
        var day = "";

        // Als de filter "week" is, bepaal dan de dag aan de hand van de rij.
        if (filter === "week") {
            var cells = Array.from(row.children);
            var cellIndex = cells.indexOf(cell);
            day = dayMapping[cellIndex] || "";
        }

        // Werk direct de cel bij met de nieuwe waarde die de gebruiker heeft ingevoerd.
        cell.textContent = newValue;

        // Verstuur het verborgen formulier om de database te updaten.
        var form = document.getElementById("hiddenUpdateForm");
        form.user_id.value = userId;
        form.filter.value = filter;
        form.day.value = day;
        form.hours.value = newValue; // De nieuwe waarde ingevoerd door de gebruiker
        form.submit();
    }
});

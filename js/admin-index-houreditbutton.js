document.addEventListener("DOMContentLoaded", function() {
    var filter = "<?= $filter ?>";

    // 1. Attach click listeners to "Wijzigen" buttons to mark their row as active.
    document.querySelectorAll(".edit-btn").forEach(function(button) {
        button.addEventListener("click", function(e) {
            e.preventDefault();
            // Remove "active-edit" from any row.
            document.querySelectorAll("tr.active-edit").forEach(function(row) {
                row.classList.remove("active-edit");
            });
            // Mark the row containing this button as active.
            var row = button.closest("tr");
            row.classList.add("active-edit");
        });
    });

    // 2. Attach inline editing to hour cells—but only if their row is active.
    var editableCells = [];
    if (filter === "week") {
        editableCells = document.querySelectorAll("td.uren-row.editable");
    } else if (filter === "vandaag") {
        editableCells = document.querySelectorAll('table[data-filter="vandaag"] tbody tr td.editable');
    }
    // For week view, map cell index (relative to row) to day code.
    // Cell 0 is name; 1: Ma, 2: Di, 3: Wo, 4: Do, 5: Vr.
    var dayMapping = {1: "Ma", 2: "Di", 3: "Wo", 4: "Do", 5: "Vr"};

    editableCells.forEach(function(cell) {
        cell.style.cursor = "pointer";
        cell.addEventListener("click", function(e) {
            // Allow editing only if this cell's row is active.
            if (!cell.parentNode.classList.contains("active-edit")) return;
            // Prevent multiple inputs in one cell.
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

            // Flag to track if Enter was pressed (updates confirmed)
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
                // If Enter was not pressed, revert to original value.
                if (!updateConfirmed) {
                    cell.textContent = originalValue;
                }
                // Remove active-edit class from the row.
                cell.parentNode.classList.remove("active-edit");
            });
        });
    });

    function updateValue(cell, newValue) {
        var row = cell.parentNode;
        var userId = row.getAttribute("data-user-id");
        var day = "";

        // If filter is "week", determine the day from the row
        if (filter === "week") {
            var cells = Array.from(row.children);
            var cellIndex = cells.indexOf(cell);
            day = dayMapping[cellIndex] || "";
        }

        // Immediately update the cell with the new value entered by the user
        cell.textContent = newValue;

        // Submit the hidden form to update the database
        var form = document.getElementById("hiddenUpdateForm");
        form.user_id.value = userId;
        form.filter.value = filter;
        form.day.value = day;
        form.hours.value = newValue; // The new value entered by the user
        form.submit();
    }
});
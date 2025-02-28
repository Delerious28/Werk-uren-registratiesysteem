document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.container');
    const notificationContainer = document.getElementById('notification-container');

    // Event delegation voor toggle buttons
    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('toggle-button')) {
            const targetId = event.target.dataset.target;
            console.log("Toggle button clicked, target:", targetId);
            toggleContainer(targetId);
        }
    });

    // Event delegation voor edit buttons
    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('edit-button')) {
            const fieldName = event.target.dataset.field;
            const currentValue = event.target.dataset.value;
            console.log("Edit button clicked, field:", fieldName, "value:", currentValue);
            editField(fieldName, currentValue);
        }
    });

    // Zorg ervoor dat de bedrijf-container zichtbaar is bij het laden van de pagina
    toggleContainer('bedrijfContainer');
    console.log("Page loaded, showing bedrijfContainer");
});

async function saveField(fieldName) {
    const newValue = document.getElementById(`edit${fieldName}`).value;
    console.log("Saving field:", fieldName, "new value:", newValue);

    // Bepaal het ID van de klant of de andere entiteit (chief/contact)
    const entityId = document.getElementById(fieldName).dataset.id;

    try {
        const response = await fetch('profiel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(newValue)}&id=${encodeURIComponent(entityId)}`,
        });

        console.log("Fetch response:", response);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("JSON response:", data);

        if (data.status === 'success') {
            document.getElementById(fieldName).textContent = newValue;
            console.log("Field updated successfully");
            showNotification('Veld succesvol bijgewerkt!', 'success'); // Succesbericht
        } else {
            showNotification('Er is een fout opgetreden bij het bijwerken van het veld.', 'error'); // Foutbericht
            console.error("Field update failed:", data.message);
        }
    } catch (error) {
        console.error('Er is een probleem met de serverrespons:', error);
        showNotification('Er is een probleem met de serverrespons.', 'error'); // Foutbericht
    }
}

function editField(fieldName, currentValue) {
    const fieldElement = document.getElementById(fieldName);
    fieldElement.innerHTML = `
        <input type="text" id="edit${fieldName}" value="${currentValue}">
        <button onclick="saveField('${fieldName}')">Opslaan</button>
    `;
    console.log("Field made editable:", fieldName);
}

function toggleContainer(containerId) {
    const containers = document.querySelectorAll('.container-section');
    containers.forEach(container => {
        container.style.display = container.id === containerId ? 'flex' : 'none';
    });
    console.log("Toggled container:", containerId);
}

function showNotification(message, type) {
    const notificationContainer = document.getElementById('notification-container');
    notificationContainer.textContent = message;
    notificationContainer.className = `notification ${type}`;
    notificationContainer.style.display = 'block';

    // Verberg de notificatie na 3 seconden
    setTimeout(() => {
        notificationContainer.classList.add('hide'); // Voeg de 'hide' class toe
    }, 3000); // Verberg de melding na 3 seconden

    // Zorg ervoor dat de notificatie na de animatie verdwijnt
    setTimeout(() => {
        notificationContainer.style.display = 'none';
        notificationContainer.classList.remove('hide'); // Verwijder de 'hide' class voor de volgende keer
    }, 3300); // 3300 ms omdat de animatie 300ms duurt
}
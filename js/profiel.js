document.addEventListener('DOMContentLoaded', initializeProfilePage);

function initializeProfilePage() {
    const container = document.querySelector('.container');
    const notificationContainer = document.getElementById('notification-container');
    const userRole = document.body.dataset.userRole;

    setupEventListeners(container, userRole);
    toggleContainer('bedrijfContainer');
    updateEditButtons(userRole);
}

function setupEventListeners(container, userRole) {
    container.addEventListener('click', (event) => {
        const target = event.target;

        if (target.classList.contains('toggle-button')) {
            handleToggleButtonClick(target, userRole);
        } else if (target.classList.contains('edit-button')) {
            handleEditButtonClick(target);
        } else if (target.classList.contains('klant-link')) {
            handleKlantLinkClick(target);
        }
    });
}

function handleToggleButtonClick(target, userRole) {
    const targetId = target.dataset.target;
    console.log("Toggle button clicked, target:", targetId);
    toggleContainer(targetId);
    updateEditButtons(userRole);
}

function handleEditButtonClick(target) {
    const fieldName = target.dataset.field;
    const currentValue = target.dataset.value;
    console.log("Edit button clicked, field:", fieldName, "value:", currentValue);
    editField(fieldName, currentValue);
}

function handleKlantLinkClick(target) {
    event.preventDefault();
    const klantId = target.dataset.klantId;
    console.log("Klant link clicked, klantId:", klantId);
    loadKlantDetails(klantId);
}

function updateEditButtons(userRole) {
    document.querySelectorAll('.edit-button').forEach(button => {
        const field = button.dataset.field;
        const showButton = userRole === 'admin' || (userRole === 'klant' && field.startsWith('klant_'));
        button.style.display = showButton ? 'inline-block' : 'none';
    });
}

async function saveField(fieldName) {
    const newValue = document.getElementById(`edit${fieldName}`).value;
    const entityId = document.getElementById(fieldName).dataset.id;

    try {
        const response = await fetch('profiel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(newValue)}&id=${encodeURIComponent(entityId)}`,
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();

        if (data.status === 'success') {
            document.getElementById(fieldName).textContent = newValue;
            showNotification('Veld succesvol bijgewerkt!', 'success');
        } else {
            showNotification('Er is een fout opgetreden bij het bijwerken van het veld.', 'error');
            console.error("Field update failed:", data.message);
        }
    } catch (error) {
        console.error('Er is een probleem met de serverrespons:', error);
        showNotification('Er is een probleem met de serverrespons.', 'error');
    }
}

function editField(fieldName, currentValue) {
    const fieldElement = document.getElementById(fieldName);
    fieldElement.innerHTML = `
        <input class="input-profiel" type="text" id="edit${fieldName}" value="${currentValue}">
        <button id="opslaan-btn-profiel" onclick="saveField('${fieldName}')">Opslaan</button>
    `;
    console.log("Field made editable:", fieldName);
}

function toggleContainer(containerId) {
    document.querySelectorAll('.container-section').forEach(container => {
        container.style.display = container.id === containerId ? 'flex' : 'none';
    });
    console.log("Toggled container:", containerId);
}

function showNotification(message, type) {
    const notificationContainer = document.getElementById('notification-container');
    notificationContainer.textContent = message;
    notificationContainer.className = `notification ${type}`;
    notificationContainer.style.display = 'block';

    setTimeout(() => notificationContainer.classList.add('hide'), 3000);
    setTimeout(() => {
        notificationContainer.style.display = 'none';
        notificationContainer.classList.remove('hide');
    }, 3300);
}

async function loadKlantDetails(klantId) {
    try {
        const response = await fetch(`profiel.php?action=klantDetails&klantId=${klantId}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.text();

        document.getElementById('klantDetails').innerHTML = data;
        document.getElementById('klantDetails').style.display = 'block';
        updateEditButtons(document.body.dataset.userRole);
    } catch (error) {
        console.error('Er is een probleem bij het laden van de klantdetails:', error);
        showNotification('Er is een probleem bij het laden van de klantdetails.', 'error');
    }
}
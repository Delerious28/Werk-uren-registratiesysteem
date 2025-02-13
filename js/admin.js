// admin-gebruikers.php

// Open the overlay and show the New User form
function showCreateUserForm() {
    document.getElementById('userFormOverlay').style.display = 'flex';
    document.getElementById('editUserForm').style.display = 'none';
    document.getElementById('createUserForm').style.display = 'block';
}

// Open the overlay and show the Edit User form, populating its fields
function editUser(id, name, role) {
    document.getElementById('userFormOverlay').style.display = 'flex';
    document.getElementById('createUserForm').style.display = 'none';
    document.getElementById('editUserForm').style.display = 'block';
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_role').value = role;
}

// Close the overlay (hiding both forms)
function closeUserForm() {
    document.getElementById('userFormOverlay').style.display = 'none';
}

// Simple form validation to check name and password rules
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

document.addEventListener("DOMContentLoaded", function () {
    const container = document.querySelector('.container');
    const newUserMessage = document.querySelector('.new-gebruiker-bericht');
    const failUserMessage = document.querySelector('.fout-gebruiker-bericht');

    if (newUserMessage || failUserMessage) {
        container.classList.add('verandering');
    } else {
        container.classList.remove('verandering');
    }
});

// Eind admin-gebruikers.php
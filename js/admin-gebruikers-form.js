
function showCreateUserForm() {
    const overlay = document.getElementById('userFormOverlay');
    const editForm = document.getElementById('editUserForm');
    const createForm = document.getElementById('createUserForm');

    if (overlay) overlay.style.display = 'flex';
    if (editForm) editForm.style.display = 'none';
    if (createForm) createForm.style.display = 'block';
}

function editUser(id, name, role) {
    const overlay = document.getElementById('userFormOverlay');
    const editForm = document.getElementById('editUserForm');
    const createForm = document.getElementById('createUserForm');

    if (overlay) overlay.style.display = 'flex';
    if (createForm) createForm.style.display = 'none';
    if (editForm) {
        editForm.style.display = 'block';
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_role').value = role;
    }
}

function closeUserForm() {
    const overlay = document.getElementById('userFormOverlay');
    if (overlay) overlay.style.display = 'none';
}

function validateForm(form) {
    const nameField = form.querySelector('[name="name"]');
    const passwordField = form.querySelector('[name="password"]');

    if (!nameField || !passwordField) {
        alert('Formulier is ongeldig.');
        return false;
    }

    const name = nameField.value;
    const password = passwordField.value;

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

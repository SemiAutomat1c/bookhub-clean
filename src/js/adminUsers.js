// Admin Users Management

document.addEventListener('DOMContentLoaded', () => {
    // Load users when switching to users section
    const usersNavItem = document.querySelector('.admin-nav-item[data-section="users"]');
    if (usersNavItem) {
        usersNavItem.addEventListener('click', () => {
            loadAdminUsers();
        });
    }

    // Setup search
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleUserSearch, 300));
    }

    // Setup filters
    const roleFilter = document.getElementById('roleFilter');
    const sortBy = document.getElementById('userSortBy');
    if (roleFilter) roleFilter.addEventListener('change', handleUserFilters);
    if (sortBy) sortBy.addEventListener('change', handleUserFilters);

    // Setup user form
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', handleUserSubmit);
    }

    // Setup modal close handlers
    const userModal = document.getElementById('userFormModal');
    const closeButtons = userModal.querySelectorAll('.close, .cancel-btn');
    closeButtons.forEach(button => {
        button.addEventListener('click', closeUserModal);
    });
});

async function loadAdminUsers() {
    try {
        const searchInput = document.getElementById('userSearch').value;
        const roleFilter = document.getElementById('roleFilter').value;
        const sortBy = document.getElementById('userSortBy').value;

        const response = await fetch(`../api/admin/list_users.php?search=${encodeURIComponent(searchInput)}&role=${encodeURIComponent(roleFilter)}&sort=${encodeURIComponent(sortBy)}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        const tableBody = document.getElementById('adminUsersTableBody');
        tableBody.innerHTML = '';

        if (text === 'SUCCESS|NO_USERS') {
            displayNoUsers();
            return;
        }

        const usersData = text.substring(8).split('\n').filter(line => line.trim());
        
        if (usersData.length === 0) {
            displayNoUsers();
            return;
        }

        usersData.forEach(userData => {
            const [id, username, email, role, created_at, lastLogin, booksCount] = userData.split('|');
            if (id && username) {
                const row = createUserTableRow(id, username, email, role, created_at, lastLogin, booksCount);
                tableBody.appendChild(row);
            }
        });
    } catch (error) {
        console.error('Error loading users:', error);
        displayError('Failed to load users. Please try again.');
    }
}

// Get current user ID from localStorage
function getCurrentUserId() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
        const [userId] = userStr.split(',');
        return parseInt(userId);
    }
    return null;
}

function createUserTableRow(id, username, email, role, created_at, lastLogin, booksCount) {
    const row = document.createElement('tr');
    const currentUserId = getCurrentUserId();
    const isCurrentUser = currentUserId === parseInt(id);
    
    row.innerHTML = `
        <td>${username}${isCurrentUser ? ' (You)' : ''}</td>
        <td>${email}</td>
        <td><span class="role-badge ${role}">${role}</span></td>
        <td>${formatLastLogin(lastLogin)}</td>
        <td><span class="count-badge">${booksCount}</span></td>
        <td>${formatDate(created_at)}</td>
        <td class="table-actions">
            ${isCurrentUser ? 
                `<span class="current-user-notice">Cannot modify own account</span>` :
                `<button class="edit-btn" onclick="editUser(${id}, '${username}', '${email}', '${role}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="delete-btn" onclick="deleteUser(${id}, '${username}')">
                    <i class="fas fa-trash"></i> Delete
                </button>`
            }
        </td>
    `;
    
    if (isCurrentUser) {
        row.classList.add('current-user');
    }
    
    return row;
}

// Helper function to format last login date
function formatLastLogin(lastLogin) {
    if (!lastLogin || lastLogin === 'Never') return 'Never';
    const date = new Date(lastLogin);
    if (isNaN(date.getTime())) return lastLogin;
    
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) return 'Today';
    if (days === 1) return 'Yesterday';
    if (days < 7) return `${days} days ago`;
    return formatDate(lastLogin);
}

// Helper function to format dates consistently
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showAddUserModal() {
    const modal = document.getElementById('userFormModal');
    const form = document.getElementById('userForm');
    const modalTitle = document.getElementById('userModalTitle');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const requiredIndicators = document.querySelectorAll('.required-indicator');

    // Reset form and title
    form.reset();
    form.removeAttribute('data-user-id');
    modalTitle.textContent = 'Add New User';
    
    // Make password fields required for new users
    passwordField.required = true;
    confirmPasswordField.required = true;
    requiredIndicators.forEach(indicator => indicator.style.display = 'inline');

    // Show modal
    modal.style.display = 'block';
}

function editUser(id, username, email, role) {
    const modal = document.getElementById('userFormModal');
    const form = document.getElementById('userForm');
    const modalTitle = document.getElementById('userModalTitle');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const requiredIndicators = document.querySelectorAll('.required-indicator');

    // Set form data
    form.setAttribute('data-user-id', id);
    modalTitle.textContent = 'Edit User';
    document.getElementById('username').value = username;
    document.getElementById('email').value = email;
    document.getElementById('role').value = role;
    
    // Make password fields optional for editing
    passwordField.required = false;
    confirmPasswordField.required = false;
    requiredIndicators.forEach(indicator => indicator.style.display = 'none');

    // Clear password fields
    passwordField.value = '';
    confirmPasswordField.value = '';

    // Show modal
    modal.style.display = 'block';
}

async function deleteUser(id, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch('../api/admin/delete_user.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${id}`
        });

        const text = await response.text();
        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        showMessage('User deleted successfully');
        loadAdminUsers();
    } catch (error) {
        console.error('Error deleting user:', error);
        showMessage('Failed to delete user: ' + error.message);
    }
}

function showMessage(message, isError = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isError ? 'error' : 'success'}`;
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.left = '50%';
    messageDiv.style.transform = 'translateX(-50%)';
    messageDiv.style.padding = '10px 20px';
    messageDiv.style.backgroundColor = isError ? 'var(--danger-color)' : 'var(--primary-color)';
    messageDiv.style.color = 'white';
    messageDiv.style.borderRadius = '5px';
    messageDiv.style.zIndex = '1000';
    messageDiv.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
    
    document.body.appendChild(messageDiv);
    setTimeout(() => messageDiv.remove(), 3000);
}

function displayError(message) {
    showMessage(message, true);
}

async function handleUserSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const userId = form.getAttribute('data-user-id');
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    try {
        // Validate passwords match
        if (password !== confirmPassword) {
            throw new Error('Passwords do not match');
        }

        // Prepare form data
        const formData = new FormData(form);
        const data = {
            username: formData.get('username'),
            email: formData.get('email'),
            role: formData.get('role'),
            full_name: formData.get('full_name')
        };

        // Add password for new users or if password is being changed
        if (password) {
            data.password = password;
        }

        // Determine if this is an add or update operation
        const isNewUser = !userId;
        const endpoint = isNewUser ? '../api/admin/add_user.php' : '../api/admin/update_user.php';

        if (!isNewUser) {
            data.user_id = userId;
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: Object.entries(data).map(([key, value]) => 
                `${encodeURIComponent(key)}=${encodeURIComponent(value)}`
            ).join('&')
        });

        const text = await response.text();
        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        showMessage(isNewUser ? 'User created successfully' : 'User updated successfully');
        closeUserModal();
        loadAdminUsers();
    } catch (error) {
        console.error('Error saving user:', error);
        displayError('Failed to save user: ' + error.message);
    }
}

function closeUserModal() {
    const modal = document.getElementById('userFormModal');
    modal.style.display = 'none';
}

function handleUserSearch() {
    loadAdminUsers();
}

function handleUserFilters() {
    loadAdminUsers();
}

function displayNoUsers() {
    const tableBody = document.getElementById('adminUsersTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="empty-state">
                <p>No users found</p>
            </td>
        </tr>
    `;
} 
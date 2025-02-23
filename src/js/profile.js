document.addEventListener('DOMContentLoaded', () => {
    const profileUsername = document.querySelector('.profile-username');
    const profileEmail = document.querySelector('.profile-email');
    const logoutButton = document.getElementById('logout-button');

    // Check if user is logged in
    const authToken = localStorage.getItem('authToken');
    const userData = localStorage.getItem('userData');

    if (!authToken || !userData) {
        window.location.href = 'sign-in.html';
        return;
    }

    // Display user data
    try {
        const user = JSON.parse(userData);
        profileUsername.textContent = user.username || 'Unknown User';
        profileEmail.textContent = user.email || 'No email provided';
    } catch (error) {
        console.error('Error parsing user data:', error);
        window.location.href = 'sign-in.html';
        return;
    }

    // Handle logout
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            localStorage.removeItem('authToken');
            localStorage.removeItem('userData');
            window.location.href = '../index.html';
        });
    }

    // Handle profile actions
    const editProfileBtn = document.querySelector('.edit-profile-btn');
    const changePasswordBtn = document.querySelector('.change-password-btn');
    const deleteAccountBtn = document.querySelector('.delete-account-btn');

    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', () => {
            alert('Edit profile feature coming soon!');
        });
    }

    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', () => {
            alert('Change password feature coming soon!');
        });
    }

    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', () => {
            alert('Delete account feature coming soon!');
        });
    }
});

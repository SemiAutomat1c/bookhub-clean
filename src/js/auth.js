// Auth state management
let currentUser = null;

// Debug logging function
function debugLog(source, ...args) {
    console.log(`[${source}]`, ...args);
}

// Show error message
function showError(message) {
    debugLog('Error', message);
    const errorDiv = document.getElementById('error-message');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#dc3545';
        errorDiv.style.backgroundColor = '#f8d7da';
        errorDiv.style.padding = '10px';
        errorDiv.style.borderRadius = '4px';
        errorDiv.style.marginBottom = '15px';
    }
}

// Show success message
function showSuccess(message) {
    debugLog('Success', message);
    const successDiv = document.getElementById('success-message');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        successDiv.style.color = '#28a745';
        successDiv.style.backgroundColor = '#d4edda';
        successDiv.style.padding = '10px';
        successDiv.style.borderRadius = '4px';
        successDiv.style.marginBottom = '15px';
    }
}

// Clear messages
function clearMessages() {
    debugLog('Clear', 'Clearing messages');
    const errorDiv = document.getElementById('error-message');
    const successDiv = document.getElementById('success-message');
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
}

// Check auth status
async function checkAuthStatus() {
    debugLog('Auth Check', 'Checking authentication status...');
    try {
        const response = await fetch('/bookhub-1/api/auth/auth_check.php', {
            credentials: 'include'
        });
        
        const text = await response.text();
        debugLog('Auth Check Response', text);
        
        // Parse text response (format: status|message|userData)
        const [status, message, userData] = text.split('|');
        debugLog('Auth Check Parsed', { status, message, userData });
        
        if (status === 'authenticated') {
            const [userId, username, email] = userData.split(',');
            currentUser = {
                id: userId,
                username: username,
                email: email
            };
            localStorage.setItem('user', `${userId},${username},${email}`);
            debugLog('Auth Check Success', currentUser);
            updateProtectedElements(true);
            return true;
        } else {
            debugLog('Auth Check Failed', message);
            currentUser = null;
            localStorage.removeItem('user');
            updateProtectedElements(false);
            return false;
        }
    } catch (error) {
        debugLog('Auth Check Error', error);
        currentUser = null;
        localStorage.removeItem('user');
        updateProtectedElements(false);
        return false;
    }
}

// Login user
async function login(email, password) {
    debugLog('Login', 'Attempting login...', { email });
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        const response = await fetch('/bookhub-1/api/auth/login.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });

        const text = await response.text();
        debugLog('Login Response', text);

        // Parse text response (format: status|message|userData)
        const [status, message, userData] = text.split('|');
        debugLog('Login Parsed', { status, message, userData });

        if (status === 'success') {
            const [userId, username, email] = userData.split(',');
            currentUser = {
                id: userId,
                username: username,
                email: email
            };
            localStorage.setItem('user', `${userId},${username},${email}`);
            debugLog('Login Success', currentUser);
            
            // Update header immediately
            if (typeof updateHeader === 'function') {
                updateHeader(true, currentUser);
            }
            
            showSuccess(message);
            
            // Redirect to home page after successful login
            setTimeout(() => {
                window.location.href = '/bookhub-1/views/index.html';
            }, 1000);
            return true;
        } else {
            debugLog('Login Failed', message);
            showError(message);
            return false;
        }
    } catch (error) {
        debugLog('Login Error', error);
        showError('An error occurred during login. Please try again.');
        return false;
    }
}

// Register function
async function register(fullName, email, password) {
    debugLog('Register', 'Attempting registration...', { fullName, email });
    try {
        const formData = new FormData();
        formData.append('full_name', fullName);
        formData.append('email', email);
        formData.append('password', password);

        const response = await fetch('/bookhub-1/api/auth/register.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });

        const text = await response.text();
        debugLog('Register Response', text);

        // Parse text response (format: status|message|userData)
        const parts = text.split('|');
        const status = parts[0];
        const message = parts[1];
        const userData = parts[2];
        
        debugLog('Register Parsed', { status, message, userData });

        if (status === 'success' && userData) {
            const [userId, username, email] = userData.split(',');
            currentUser = {
                id: userId,
                username: username,
                email: email
            };
            localStorage.setItem('user', `${userId},${username},${email}`);
            debugLog('Register Success', currentUser);
            
            // Update header immediately
            if (typeof updateHeader === 'function') {
                updateHeader(true, currentUser);
            }
            
            showSuccess(message);
            
            // Redirect to home page after successful registration
            setTimeout(() => {
                window.location.href = '/bookhub-1/views/index.html';
            }, 1000);
            return true;
        } else {
            debugLog('Register Failed', message);
            showError(message || 'Registration failed. Please try again.');
            return false;
        }
    } catch (error) {
        debugLog('Register Error', error);
        showError('An error occurred during registration. Please try again.');
        return false;
    }
}

// Logout user
function logout() {
    // Clear user data from localStorage
    localStorage.removeItem('currentUser');
    
    // Remove logged-in class from body
    document.body.classList.remove('logged-in');
    
    // Show a success message
    showMessage('Logged out successfully', false);
    
    // Redirect to sign-in page after a brief delay
    setTimeout(() => {
        window.location.href = '/bookhub-1/views/sign-in.html';
    }, 1000);
}

// Check if user is logged in
function isLoggedIn() {
    const status = currentUser !== null;
    debugLog('Auth Status', status ? 'Logged in' : 'Not logged in', currentUser);
    return status;
}

// Get current user data
function getCurrentUser() {
    if (currentUser) {
        debugLog('Get User', 'Returning current user', currentUser);
        return currentUser;
    }
    const userStr = localStorage.getItem('user');
    if (userStr) {
        try {
            const [userId, username, email] = userStr.split(',');
            currentUser = {
                id: userId,
                username: username,
                email: email
            };
            debugLog('Get User', 'Loaded from storage', currentUser);
            return currentUser;
        } catch (e) {
            debugLog('Get User Error', e);
            return null;
        }
    }
    debugLog('Get User', 'No user found');
    return null;
}

// Update protected elements visibility
function updateProtectedElements(isAuthenticated) {
    debugLog('UI Update', 'Updating protected elements', { isAuthenticated });
    
    // Update header if the function exists
    if (typeof updateHeader === 'function') {
        updateHeader(isAuthenticated, currentUser);
    }

    const protectedElements = document.querySelectorAll('.protected-feature');
    const signedOutElements = document.querySelectorAll('.signed-out-only');
    const userInfoElements = document.querySelectorAll('.user-info');

    // If we're on the sign-in page and logged in, redirect to home
    if (window.location.pathname.includes('sign-in.html') && isAuthenticated) {
        debugLog('UI Update', 'Redirecting to home from sign-in page');
        window.location.href = '/bookhub-1/views/index.html';
        return;
    }

    protectedElements.forEach(element => {
        element.style.display = isAuthenticated ? '' : 'none';
    });

    signedOutElements.forEach(element => {
        element.style.display = isAuthenticated ? 'none' : '';
    });

    if (currentUser) {
        userInfoElements.forEach(element => {
            element.textContent = currentUser.username;
            element.style.display = '';
        });
    }

    debugLog('UI Update', 'Protected elements updated');
}

// Check auth status on page load
document.addEventListener('DOMContentLoaded', async () => {
    debugLog('Init', 'Page loaded, checking auth status...');
    await checkAuthStatus();

    // Handle sign-up form if it exists
    const signUpForm = document.getElementById('signUpForm');
    if (signUpForm) {
        signUpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessages();

            const formData = new FormData(signUpForm);
            
            // Validate password match
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password !== confirmPassword) {
                showError('Passwords do not match');
                return;
            }

            // Validate password strength
            if (password.length < 8) {
                showError('Password must be at least 8 characters long');
                return;
            }

            try {
                await register(formData.get('full_name'), formData.get('email'), password);
            } catch (error) {
                console.error('Sign up error:', error);
            }
        });
    }

    // Handle sign-in form if it exists
    const signInForm = document.getElementById('signInForm');
    if (signInForm) {
        signInForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessages();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                await login(email, password);
            } catch (error) {
                console.error('Sign in error:', error);
            }
        });
    }

    // Add password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const input = toggle.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        });
    });

    // Handle logout buttons
    const logoutButtons = document.querySelectorAll('.logout-button');
    logoutButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    });
});

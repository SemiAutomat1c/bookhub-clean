document.addEventListener('DOMContentLoaded', () => {
    // Check if user is already logged in
    const authToken = localStorage.getItem('authToken');
    if (authToken) {
        window.location.href = '../index.html';
        return;
    }

    // Get form elements
    const signInForm = document.querySelector('.sign-in-form');
    const signUpForm = document.querySelector('.sign-up-form');
    const switchToSignup = document.querySelector('.switch-to-signup');
    const switchToSignin = document.querySelector('.switch-to-signin');
    const container = document.querySelector('.sign-in-container');

    // Toggle between sign in and sign up forms
    if (switchToSignup) {
        switchToSignup.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add('show-signup');
        });
    }

    if (switchToSignin) {
        switchToSignin.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove('show-signup');
        });
    }

    // Handle sign in
    if (signInForm) {
        signInForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = signInForm.querySelector('[name="email"]').value;
            const password = signInForm.querySelector('[name="password"]').value;

            // For demo purposes, just check if email and password are not empty
            if (email && password) {
                // Generate a random token
                const token = Math.random().toString(36).substring(2);
                const userData = {
                    email: email,
                    username: email.split('@')[0] // Use part before @ as username
                };
                
                // Store token and user data
                localStorage.setItem('authToken', token);
                localStorage.setItem('userData', JSON.stringify(userData));
                
                // Redirect to home page
                window.location.href = '../index.html';
            } else {
                alert('Please fill in all fields');
            }
        });
    }

    // Handle sign up
    if (signUpForm) {
        signUpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const fullname = signUpForm.querySelector('[name="fullname"]').value;
            const email = signUpForm.querySelector('[name="email"]').value;
            const password = signUpForm.querySelector('[name="password"]').value;
            const confirmPassword = signUpForm.querySelector('[name="confirm-password"]').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            if (fullname && email && password) {
                // Generate a random token
                const token = Math.random().toString(36).substring(2);
                const userData = {
                    email: email,
                    username: fullname
                };
                
                // Store token and user data
                localStorage.setItem('authToken', token);
                localStorage.setItem('userData', JSON.stringify(userData));
                
                // Redirect to home page
                window.location.href = '../index.html';
            } else {
                alert('Please fill in all fields');
            }
        });
    }
});

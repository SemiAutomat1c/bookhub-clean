// Handle form submission
document.addEventListener('DOMContentLoaded', () => {
    const signInForm = document.querySelector('.sign-in-form');
    const signUpForm = document.querySelector('.sign-up-form');
    const switchToSignup = document.querySelector('.switch-to-signup');
    const switchToSignin = document.querySelector('.switch-to-signin');

    // Initially hide the sign-up form
    if (signUpForm) {
        signUpForm.style.display = 'none';
        signUpForm.parentElement.querySelector('.sign-up-title').style.display = 'none';
        signUpForm.parentElement.querySelector('.sign-up-subtitle').style.display = 'none';
    }

    // Switch between forms
    if (switchToSignup) {
        switchToSignup.addEventListener('click', (e) => {
            e.preventDefault();
            signInForm.style.display = 'none';
            signInForm.parentElement.querySelector('.sign-in-title').style.display = 'none';
            signInForm.parentElement.querySelector('.sign-in-subtitle').style.display = 'none';
            signUpForm.style.display = 'block';
            signUpForm.parentElement.querySelector('.sign-up-title').style.display = 'block';
            signUpForm.parentElement.querySelector('.sign-up-subtitle').style.display = 'block';
        });
    }

    if (switchToSignin) {
        switchToSignin.addEventListener('click', (e) => {
            e.preventDefault();
            signUpForm.style.display = 'none';
            signUpForm.parentElement.querySelector('.sign-up-title').style.display = 'none';
            signUpForm.parentElement.querySelector('.sign-up-subtitle').style.display = 'none';
            signInForm.style.display = 'block';
            signInForm.parentElement.querySelector('.sign-in-title').style.display = 'block';
            signInForm.parentElement.querySelector('.sign-in-subtitle').style.display = 'block';
        });
    }

    // Handle sign in
    if (signInForm) {
        signInForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('../auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'login',
                        email: email,
                        password: password
                    })
                });

                const data = await response.json();
                console.log('Login response:', data);

                if (data.success) {
                    // Store auth token and user data
                    localStorage.setItem('authToken', data.token);
                    localStorage.setItem('userData', JSON.stringify(data.user));
                    localStorage.setItem('isLoggedIn', 'true');
                    
                    // Get return URL from query parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const returnUrl = urlParams.get('returnUrl');
                    
                    // Redirect to return URL or home page
                    if (returnUrl) {
                        window.location.href = decodeURIComponent(returnUrl);
                    } else {
                        window.location.href = '../index.html';
                    }
                } else {
                    alert(data.message || 'Invalid email or password');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during sign in');
            }
        });
    }

    // Handle sign up
    if (signUpForm) {
        signUpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('signup-email').value;
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            try {
                const response = await fetch('../auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'signup',  
                        username: fullname,  
                        email: email,
                        password: password
                    })
                });

                const data = await response.json();
                console.log('Signup response:', data);

                if (data.success) {
                    // Store auth token and user data
                    localStorage.setItem('authToken', data.token);
                    localStorage.setItem('userData', JSON.stringify(data.user));
                    localStorage.setItem('isLoggedIn', 'true');
                    
                    // Get return URL from query parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    const returnUrl = urlParams.get('returnUrl');
                    
                    // Redirect to return URL or home page
                    if (returnUrl) {
                        window.location.href = decodeURIComponent(returnUrl);
                    } else {
                        window.location.href = '../index.html';
                    }
                } else {
                    alert(data.message || 'Registration failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during registration');
            }
        });
    }

    // Function to handle logout
    window.logout = function() {
        localStorage.removeItem('authToken');
        localStorage.removeItem('userData');
        localStorage.removeItem('isLoggedIn');
        document.body.classList.remove('logged-in');
        window.location.href = '/bookhub/views/index.html';
    }

    // Function to check login status
    function checkLoginStatus() {
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        document.body.classList.toggle('logged-in', isLoggedIn);
    }

    checkLoginStatus();
});

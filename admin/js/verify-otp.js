function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const eyeIcon = `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>`;
    const eyeSlashIcon = `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
        </svg>`;

    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = eyeSlashIcon;
    } else {
        input.type = 'password';
        button.innerHTML = eyeIcon;
    }
}

document.getElementById('resetForm').addEventListener('submit', function(e) {
    // Reset error messages
    document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));

    const otp = document.getElementById('otp').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    let isValid = true;
    
    // Validate OTP
    if (!/^\d{6}$/.test(otp)) {
        document.getElementById('otpError').classList.add('show');
        isValid = false;
    }
    
    // Validate password length
    if (newPassword.length < 8) {
        document.getElementById('passwordError').textContent = 'Password must be at least 8 characters';
        document.getElementById('passwordError').classList.add('show');
        isValid = false;
    }

    // Validate password complexity
    if (!/(?=.*\d)(?=.*[a-zA-Z])/.test(newPassword)) {
        document.getElementById('passwordError').textContent = 'Password must contain letters and numbers';
        document.getElementById('passwordError').classList.add('show');
        isValid = false;
    }
    
    // Validate password match
    if (newPassword !== confirmPassword) {
        document.getElementById('confirmError').classList.add('show');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault(); // Stop form submission if validation fails
    }
});

// Real-time validation for password confirmation
document.getElementById('confirmPassword').addEventListener('input', function() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmError = document.getElementById('confirmError');
    
    if (this.value && newPassword !== this.value) {
        confirmError.classList.add('show');
    } else {
        confirmError.classList.remove('show');
    }
});
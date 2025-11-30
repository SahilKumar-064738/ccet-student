let emailForOTP = '';

document.getElementById('requestOtpBtn').addEventListener('click', requestOTP);
document.getElementById('verifyOtpBtn').addEventListener('click', verifyOTP);
document.getElementById('backToEmailBtn').addEventListener('click', backToEmail);

document.getElementById('email').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') requestOTP();
});

document.getElementById('otp').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') verifyOTP();
});

async function requestOTP() {
    const email = document.getElementById('email').value.trim();
    
    if (!email) {
        showLoginAlert('Please enter your email', 'error');
        return;
    }
    
    const btn = document.getElementById('requestOtpBtn');
    btn.disabled = true;
    btn.textContent = 'Sending OTP...';
    
    try {
        await apiRequest('/auth/request-otp.php', {
            method: 'POST',
            body: JSON.stringify({ email })
        });
        
        emailForOTP = email;
        document.getElementById('emailDisplay').textContent = email;
        document.getElementById('emailStep').style.display = 'none';
        document.getElementById('otpStep').style.display = 'block';
        document.getElementById('otp').focus();
        
        showLoginAlert('OTP sent to your email!', 'success');
    } catch (error) {
        showLoginAlert(error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Request OTP';
    }
}

async function verifyOTP() {
    const otp = document.getElementById('otp').value.trim();
    
    if (!otp || otp.length !== 6) {
        showLoginAlert('Please enter 6-digit OTP', 'error');
        return;
    }
    
    const btn = document.getElementById('verifyOtpBtn');
    btn.disabled = true;
    btn.textContent = 'Verifying...';
    
    try {
        await apiRequest('/auth/verify-otp.php', {
            method: 'POST',
            body: JSON.stringify({ 
                email: emailForOTP, 
                otp 
            })
        });
        
        showLoginAlert('Login successful!', 'success');
        setTimeout(() => {
            window.location.href = 'dashboard.html';
        }, 1000);
    } catch (error) {
        showLoginAlert(error.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Verify & Login';
    }
}

function backToEmail() {
    document.getElementById('otpStep').style.display = 'none';
    document.getElementById('emailStep').style.display = 'block';
    document.getElementById('otp').value = '';
    document.getElementById('email').focus();
}

function showLoginAlert(message, type) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
}

async function apiRequest(endpoint, options = {}) {
    try {
        const response = await fetch(`/api${endpoint}`, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ===== TOGGLE PASSWORD =====
document.getElementById('togglePw').addEventListener('click', function () {
  const input = document.getElementById('password');
  const icon = this.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
});

// ===== LOGIN FORM =====
document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const email = document.getElementById('email');
  const password = document.getElementById('password');
  const emailErr = document.getElementById('emailError');
  const passErr = document.getElementById('passwordError');
  let valid = true;

  emailErr.textContent = '';
  passErr.textContent = '';
  email.classList.remove('error');
  password.classList.remove('error');

  if (!email.value.trim()) {
    emailErr.textContent = 'Email address is required.';
    email.classList.add('error');
    valid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailErr.textContent = 'Please enter a valid email address.';
    email.classList.add('error');
    valid = false;
  }

  if (!password.value) {
    passErr.textContent = 'Password is required.';
    password.classList.add('error');
    valid = false;
  }

  if (!valid) return;

  const btn = document.getElementById('loginBtn');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Logging in...</span>';
  btn.disabled = true;

  // Simulate login — replace with real backend call
  setTimeout(() => {
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login Here</span>';
    btn.disabled = false;
    alert('Login functionality will be connected to the backend.');
  }, 1800);
});

// ===== FORGOT PASSWORD MODAL =====
const fpOverlay = document.getElementById('fpOverlay');

document.querySelector('.forgot-link').addEventListener('click', (e) => {
  e.preventDefault();
  fpOverlay.classList.add('open');
});
document.getElementById('fpClose').addEventListener('click', () => fpOverlay.classList.remove('open'));
document.getElementById('fpBack').addEventListener('click', (e) => { e.preventDefault(); fpOverlay.classList.remove('open'); });
fpOverlay.addEventListener('click', (e) => { if (e.target === fpOverlay) fpOverlay.classList.remove('open'); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fpOverlay.classList.remove('open'); });

document.getElementById('fpForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = this.querySelector('button');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sending...</span>';
  btn.disabled = true;
  setTimeout(() => {
    fpOverlay.classList.remove('open');
    btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Send Reset Link</span>';
    btn.disabled = false;
    this.reset();
    alert('Password reset link sent! Please check your email.');
  }, 1800);
});

// ===== REGISTER LINK =====
document.getElementById('goRegister').addEventListener('click', (e) => {
  e.preventDefault();
  window.location.href = 'index.html#get-started';
});

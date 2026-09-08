const API = 'http://localhost/lydo-system/backend/api/admin';

//  helpers 
function showAlert(msg, type = 'error') {
  const el = document.getElementById('loginAlert');
  el.className = 'alert ' + type;
  el.textContent = msg;
  el.style.display = 'block';
}

//  password toggle 
document.getElementById('togglePw').addEventListener('click', function () {
  const inp = document.getElementById('adminPassword');
  const ico = this.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
});

//  login form 
document.getElementById('adminLoginForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const email    = document.getElementById('adminEmail').value.trim();
  const password = document.getElementById('adminPassword').value;
  const btn      = document.getElementById('loginBtn');

  document.getElementById('emailErr').textContent = '';
  document.getElementById('passErr').textContent  = '';
  document.getElementById('loginAlert').style.display = 'none';

  if (!email)    { document.getElementById('emailErr').textContent = 'Email is required.'; return; }
  if (!password) { document.getElementById('passErr').textContent  = 'Password is required.'; return; }

  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Logging in...</span>';

  try {
    const res  = await fetch(API + '/login.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const data = await res.json();

    if (data.success) {
      localStorage.setItem('admin_token', data.token);
      localStorage.setItem('admin_info',  JSON.stringify(data.admin));
      window.location.href = 'dashboard.html';
    } else {
      showAlert(data.message || 'Login failed.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login to Dashboard</span>';
    }
  } catch {
    showAlert('Cannot connect to server. Make sure XAMPP is running.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login to Dashboard</span>';
  }
});

//  forgot password modal 
const fpOverlay = document.getElementById('fpOverlay');
document.getElementById('openForgot').addEventListener('click', e => { e.preventDefault(); fpOverlay.classList.add('open'); });
document.getElementById('fpClose').addEventListener('click', () => fpOverlay.classList.remove('open'));
document.getElementById('fpBack').addEventListener('click',  e => { e.preventDefault(); fpOverlay.classList.remove('open'); });
fpOverlay.addEventListener('click', e => { if (e.target === fpOverlay) fpOverlay.classList.remove('open'); });

document.getElementById('fpForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = this.querySelector('button');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Sending...</span>';
  await new Promise(r => setTimeout(r, 1500));
  fpOverlay.classList.remove('open');
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Send Reset Link</span>';
  this.reset();
  alert('Reset link sent! Check your email.');
});

//  redirect if already logged in 
if (localStorage.getItem('admin_token')) {
  window.location.href = 'dashboard.html';
}

// ===== NAVBAR SCROLL =====
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 50);
  updateActiveLink();
});

// ===== HAMBURGER MENU =====
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  mobileMenu.classList.toggle('open');
  document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
});

document.querySelectorAll('.mobile-link').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('open');
    mobileMenu.classList.remove('open');
    document.body.style.overflow = '';
  });
});

// ===== ACTIVE NAV LINK =====
function updateActiveLink() {
  const sections = document.querySelectorAll('section[id]');
  const scrollY = window.scrollY + 100;
  sections.forEach(section => {
    const top    = section.offsetTop;
    const height = section.offsetHeight;
    const id     = section.getAttribute('id');
    const link   = document.querySelector('.nav-link[href="#' + id + '"]');
    if (link) link.classList.toggle('active', scrollY >= top && scrollY < top + height);
  });
}

// ===== SCROLL ANIMATIONS =====
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => entry.target.classList.add('visible'), i * 100);
    }
  });
}, { threshold: 0.12 });
document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

// ===== REGISTRATION MODAL =====
const registerModal      = document.getElementById('registerModal');
const closeRegisterModal = document.getElementById('closeRegisterModal');

function openModal() {
  registerModal.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  registerModal.classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('openRegisterModal').addEventListener('click', openModal);

const fromLogin = document.getElementById('openRegisterFromLogin');
if (fromLogin) fromLogin.addEventListener('click', (e) => { e.preventDefault(); openModal(); });

closeRegisterModal.addEventListener('click', closeModal);
registerModal.addEventListener('click', (e) => { if (e.target === registerModal) closeModal(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

// Every a[href="#register"] opens the modal
document.querySelectorAll('a[href="#register"]').forEach(a => {
  a.addEventListener('click', (e) => { e.preventDefault(); openModal(); });
});

// ===== SMOOTH SCROLL (skip #register) =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', (e) => {
    const href = anchor.getAttribute('href');
    if (href === '#register') return;
    const target = document.querySelector(href);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// ===== MULTI-STEP FORM =====
let currentStep = 1;
const totalSteps = 5;

function goToStep(n) {
  document.getElementById('step-' + currentStep).classList.remove('active');
  document.querySelectorAll('.step').forEach((s, i) => {
    s.classList.remove('active', 'done');
    if (i + 1 < n) s.classList.add('done');
    if (i + 1 === n) s.classList.add('active');
  });
  document.querySelectorAll('.step-line').forEach((l, i) => {
    l.classList.toggle('done', i + 1 < n);
  });
  currentStep = n;
  document.getElementById('step-' + currentStep).classList.add('active');
  document.getElementById('stepCounter').textContent = 'Step ' + currentStep + ' of ' + totalSteps;
  document.getElementById('prevStep').style.visibility    = currentStep === 1          ? 'hidden'      : 'visible';
  document.getElementById('nextStep').style.display       = currentStep === totalSteps ? 'none'        : 'inline-flex';
  document.getElementById('submitRegister').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
  document.querySelector('.modal-body').scrollTop = 0;
}

document.getElementById('nextStep').addEventListener('click', () => {
  if (currentStep === 1) {
    // Validate password fields before advancing
    const pw  = document.getElementById('r_password').value;
    const cpw = document.getElementById('r_confirm_pw').value;
    let hasError = false;

    if (pw.length > 0 && pw.length < 8) {
      showFieldError(pwInput, 'Password must be at least 8 characters.');
      hasError = true;
    }
    if (cpw.length > 0 && cpw !== pw) {
      showFieldError(cpwInput, 'Passwords do not match.');
      hasError = true;
    }
    if (pw.length === 0) {
      showFieldError(pwInput, 'Password is required.');
      hasError = true;
    }
    if (cpw.length === 0) {
      showFieldError(cpwInput, 'Please confirm your password.');
      hasError = true;
    }
    if (hasError) return;
  }
  if (currentStep < totalSteps) goToStep(currentStep + 1);
});
document.getElementById('prevStep').addEventListener('click', () => {
  if (currentStep > 1) goToStep(currentStep - 1);
});

// ===== AUTO-CALCULATE AGE =====
document.getElementById('r_bdate').addEventListener('change', function () {
  const dob   = new Date(this.value);
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
  document.getElementById('r_age').value = age >= 0 ? age : '';
});

// ===== TOGGLE PASSWORD VISIBILITY =====
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', function () {
    const input = this.closest('.input-icon-wrap').querySelector('input');
    const icon  = this.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  });
});

// ===== LOAD ORGANIZATIONS INTO REGISTRATION DROPDOWN =====
document.addEventListener('DOMContentLoaded', function() {
  fetch('http://localhost/lydo-system/backend/api/organizations.php')
    .then(r => r.json())
    .then(d => {
      const sel = document.getElementById('r_org_name');
      if (!sel || !d.success) return;
      sel.querySelector('option[value="__loading__"]')?.remove();
      const customOpt = sel.querySelector('option[value="__custom__"]');
      d.organizations.forEach(o => {
        const opt = document.createElement('option');
        opt.value = o.name;
        opt.dataset.category = o.category || '';
        opt.textContent = o.name + (o.category ? ' (' + o.category + ')' : '');
        sel.insertBefore(opt, customOpt);
      });
    })
    .catch(() => {
      const sel = document.getElementById('r_org_name');
      if (sel) sel.querySelector('option[value="__loading__"]')?.remove();
    });
});

function handleOrgSelect(sel) {
  const customInput = document.getElementById('r_org_name_custom');
  const orgTypeSelect = document.getElementById('r_org_type');
  if (sel.value === '__custom__') {
    customInput.style.display = 'block';
    customInput.focus();
    if (orgTypeSelect) orgTypeSelect.value = '';
  } else {
    customInput.style.display = 'none';
    customInput.value = '';
    // Auto-fill org type from selected option's category
    const selectedOpt = sel.options[sel.selectedIndex];
    if (orgTypeSelect && selectedOpt && selectedOpt.dataset.category) {
      orgTypeSelect.value = selectedOpt.dataset.category;
    }
  }
}

// ===== PASSWORD REAL-TIME VALIDATION =====
const pwInput  = document.getElementById('r_password');
const cpwInput = document.getElementById('r_confirm_pw');

function showFieldError(input, msg) {
  let err = input.closest('.form-group').querySelector('.field-error');
  if (!err) {
    err = document.createElement('p');
    err.className = 'field-error';
    err.style.cssText = 'color:#e53935;font-size:.78rem;margin-top:4px;display:flex;align-items:center;gap:4px';
    input.closest('.form-group').appendChild(err);
  }
  err.innerHTML = msg ? '<i class="fas fa-exclamation-circle"></i> ' + msg : '';
  input.style.borderColor = msg ? '#e53935' : '';
  input.style.boxShadow   = msg ? '0 0 0 2px rgba(229,57,53,.15)' : '';
}

pwInput.addEventListener('input', function () {
  if (this.value.length > 0 && this.value.length < 8) {
    showFieldError(this, 'Password must be at least 8 characters.');
  } else {
    showFieldError(this, '');
  }
  // Re-check confirm if already typed
  if (cpwInput.value.length > 0) {
    if (cpwInput.value !== this.value) {
      showFieldError(cpwInput, 'Passwords do not match.');
    } else {
      showFieldError(cpwInput, '');
    }
  }
});

cpwInput.addEventListener('input', function () {
  if (this.value.length > 0 && this.value !== pwInput.value) {
    showFieldError(this, 'Passwords do not match.');
  } else {
    showFieldError(this, '');
  }
});

// ===== REAL-TIME VALIDATION FOR ALL REQUIRED FIELDS =====
function addBlurValidation(id, validate) {
  const el = document.getElementById(id);
  if (!el) return;
  const events = el.tagName === 'SELECT' ? ['change'] : ['blur', 'input'];
  events.forEach(evt => {
    el.addEventListener(evt, function () {
      const msg = validate(this.value.trim());
      showFieldError(this, msg);
    });
  });
}

// Step 1 — Personal
addBlurValidation('r_fname',   v => v ? '' : 'First name is required.');
addBlurValidation('r_lname',   v => v ? '' : 'Last name is required.');
addBlurValidation('r_gender',  v => v ? '' : 'Please select a gender.');
addBlurValidation('r_bdate',   v => {
  if (!v) return 'Birthdate is required.';
  const age = new Date().getFullYear() - new Date(v).getFullYear();
  if (age < 15 || age > 30) return 'Age must be between 15 and 30.';
  return '';
});
addBlurValidation('r_civil',   v => v ? '' : 'Please select civil status.');
addBlurValidation('r_contact', v => {
  if (!v) return 'Contact number is required.';
  if (!/^[0-9+\-\s]{7,15}$/.test(v)) return 'Enter a valid contact number.';
  return '';
});
addBlurValidation('r_email',   v => {
  if (!v) return 'Email is required.';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Enter a valid email address.';
  return '';
});
addBlurValidation('r_password', v => {
  if (!v) return 'Password is required.';
  if (v.length < 8) return 'Password must be at least 8 characters.';
  return '';
});

// Step 2 — Address
addBlurValidation('r_barangay', v => v ? '' : 'Please select your barangay.');
addBlurValidation('r_street',   v => v ? '' : 'House number / street is required.');


document.getElementById('registerForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  const consent = document.getElementById('r_consent');
  if (!consent.checked) {
    alert('Please agree to the Data Privacy Policy to continue.');
    return;
  }

  const btn = document.getElementById('submitRegister');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
  btn.disabled  = true;

  const payload = new FormData();

  // Map element IDs to POST field names
  const fields = {
    r_fname:'first_name', r_mname:'middle_name', r_lname:'last_name',
    r_suffix:'suffix', r_gender:'gender', r_bdate:'birthdate', r_age:'age',
    r_civil:'civil_status', r_contact:'contact_number', r_email:'email',
    r_password:'password', r_confirm_pw:'confirm_password',
    r_street:'street', r_barangay:'barangay', r_municipality:'municipality',
    r_province:'province', r_zip:'zip_code',
    r_educ_status:'educational_status', r_school:'school_name',
    r_course:'course_or_grade', r_employment:'employment_status',
    r_org_name:'organization_name', r_org_type:'organization_type',
    r_org_position:'organization_role', r_org_years:'years_membership',
    r_skills:'skills', r_interests:'interests', r_volunteer:'volunteer_availability'
  };

  for (const [id, name] of Object.entries(fields)) {
    const el = document.getElementById(id);
    if (el) {
      // Special handling for org name — use custom input if visible
      if (id === 'r_org_name') {
        const custom = document.getElementById('r_org_name_custom');
        const val = (custom && custom.style.display !== 'none' && custom.value.trim())
          ? custom.value.trim()
          : (el.value === '__custom__' ? '' : el.value);
        payload.append(name, val);
      } else {
        payload.append(name, el.value);
      }
    }
  }

  document.querySelectorAll('input[name="classification"]:checked').forEach(cb => {
    payload.append('youth_classification[]', cb.value);
  });
  document.querySelectorAll('input[name="programs"]:checked').forEach(cb => {
    payload.append('programs_interested[]', cb.value);
  });

  try {
    const res  = await fetch('http://localhost/lydo-system/register.php', {
      method: 'POST', body: payload
    });
    const json = await res.json();

    if (json.success) {
      closeModal();
      goToStep(1);
      this.reset();
      const toast = document.createElement('div');
      toast.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);background:#2e7d32;color:#fff;padding:16px 28px;border-radius:12px;font-weight:600;font-size:.95rem;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2);display:flex;align-items:center;gap:10px;max-width:90vw;text-align:center';
      toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + json.message;
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 7000);
    } else {
      const msg = json.message || 'Registration failed. Please try again.';
      btn.innerHTML = '<i class="fas fa-check"></i> Register Account';
      btn.disabled  = false;

      // Show inline error — jump to step 1 for password errors
      if (msg.toLowerCase().includes('password')) {
        goToStep(1);
        showFieldError(cpwInput, msg);
        cpwInput.closest('.form-group').scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else if (msg.toLowerCase().includes('email')) {
        goToStep(1);
        const emailEl = document.getElementById('r_email');
        if (emailEl) showFieldError(emailEl, msg);
      } else {
        // Show as inline banner at top of modal
        let errBanner = document.getElementById('regErrorBanner');
        if (!errBanner) {
          errBanner = document.createElement('div');
          errBanner.id = 'regErrorBanner';
          errBanner.style.cssText = 'background:#ffebee;color:#c62828;border:1px solid rgba(198,40,40,.2);border-radius:10px;padding:12px 16px;font-size:.88rem;font-weight:500;margin-bottom:12px;display:flex;align-items:center;gap:8px';
          document.querySelector('.modal-body').prepend(errBanner);
        }
        errBanner.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        setTimeout(() => errBanner?.remove(), 5000);
      }
    }
  } catch (err) {
    let errBanner = document.getElementById('regErrorBanner');
    if (!errBanner) {
      errBanner = document.createElement('div');
      errBanner.id = 'regErrorBanner';
      errBanner.style.cssText = 'background:#ffebee;color:#c62828;border:1px solid rgba(198,40,40,.2);border-radius:10px;padding:12px 16px;font-size:.88rem;font-weight:500;margin-bottom:12px;display:flex;align-items:center;gap:8px';
      document.querySelector('.modal-body').prepend(errBanner);
    }
    errBanner.innerHTML = '<i class="fas fa-exclamation-circle"></i> Cannot connect to server. Make sure XAMPP is running.';
    btn.innerHTML = '<i class="fas fa-check"></i> Register Account';
    btn.disabled  = false;
  }
});

// ===== CONTACT FORM =====
document.getElementById('contactForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"]');
  btn.innerHTML = '<i class="fas fa-check"></i> Message Sent!';
  btn.style.background = '#2e7d32';
  btn.disabled = true;
  setTimeout(() => {
    btn.innerHTML    = '<i class="fas fa-paper-plane"></i> Send Message';
    btn.style.background = '';
    btn.disabled     = false;
    e.target.reset();
  }, 3000);
});

// ===== DATA PRIVACY MODAL =====
const privacyModal = document.getElementById('privacyModal');

document.getElementById('openPrivacyModal').addEventListener('click', (e) => {
  e.preventDefault();
  privacyModal.classList.add('open');
});
document.getElementById('closePrivacyModal').addEventListener('click', () => {
  privacyModal.classList.remove('open');
});
document.getElementById('acceptPrivacy').addEventListener('click', () => {
  privacyModal.classList.remove('open');
  document.getElementById('r_consent').checked = true;
});
privacyModal.addEventListener('click', (e) => {
  if (e.target === privacyModal) privacyModal.classList.remove('open');
});

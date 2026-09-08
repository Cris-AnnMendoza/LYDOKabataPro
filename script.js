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
<<<<<<< HEAD
  // Validate current step before moving to next
  const currentStepElement = document.getElementById('step-' + currentStep);
  const requiredFields = currentStepElement.querySelectorAll('[required]');
  let isValid = true;
  let firstInvalidField = null;
  let errorMessage = 'Please fill in all required fields before proceeding.';
  
  requiredFields.forEach(field => {
    // Remove previous error styling
    field.style.borderColor = '';
    
    // Check if field is empty
    if (!field.value || field.value.trim() === '' || (field.tagName === 'SELECT' && field.value === '')) {
      isValid = false;
      field.style.borderColor = '#e53935';
      if (!firstInvalidField) firstInvalidField = field;
      return;
    }
    
    // Validate contact number (11 digits only, no letters)
    if (field.id === 'r_contact') {
      const contactValue = field.value.replace(/\s/g, ''); // Remove spaces
      if (!/^\d{11}$/.test(contactValue)) {
        isValid = false;
        field.style.borderColor = '#e53935';
        errorMessage = 'Contact number must be exactly 11 digits with no letters.';
        if (!firstInvalidField) firstInvalidField = field;
        return;
      }
    }
    
    // Validate email (must contain @)
    if (field.id === 'r_email') {
      if (!field.value.includes('@') || !field.value.includes('.')) {
        isValid = false;
        field.style.borderColor = '#e53935';
        errorMessage = 'Please enter a valid email address (must contain @ and domain).';
        if (!firstInvalidField) firstInvalidField = field;
        return;
      }
    }
  });
  
  if (!isValid) {
    showNotification('warning', 'Invalid Input', errorMessage);
    if (firstInvalidField) {
      firstInvalidField.focus();
      firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return;
  }
  
=======
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
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
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

<<<<<<< HEAD
// ===== LOAD ORGANIZATIONS ON PAGE LOAD =====
async function loadOrganizations() {
  try {
    const response = await fetch('/LYDO/lydo-system/api/get_organizations.php');
    const data = await response.json();
    
    if (data.success && data.organizations) {
      const select = document.getElementById('r_org_name');
      
      // Add each organization as an option
      data.organizations.forEach(org => {
        const option = document.createElement('option');
        option.value = org.name;
        option.textContent = org.name;
        option.setAttribute('data-category', org.category);
        select.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Failed to load organizations:', error);
  }
}

// Load organizations when page loads
document.addEventListener('DOMContentLoaded', loadOrganizations);

// ===== HANDLE ORGANIZATION SELECTION =====
document.getElementById('r_org_name').addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  const categoryDisplay = document.getElementById('org_category_display');
  const categoryInput = document.getElementById('r_org_category');
  
  if (this.value && this.value !== 'None') {
    // Show category if organization is selected
    const category = selectedOption.getAttribute('data-category');
    categoryInput.value = category || '';
    categoryDisplay.style.display = 'block';
  } else {
    // Hide category if "None" or no selection
    categoryDisplay.style.display = 'none';
    categoryInput.value = '';
  }
});

=======
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
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

<<<<<<< HEAD
// ===== REGISTRATION ROLE SELECTION =====
(function() {
  const roleYouth = document.getElementById('r_role_youth');
  const rolePresident = document.getElementById('r_role_president');
  const orgSection = document.getElementById('organization-section');
  const youthOrgSection = document.getElementById('youth-org-section');
  const orgNone = document.getElementById('r_org_none');
  const orgAffiliated = document.getElementById('r_org_affiliated');
  const youthOrgSelect = document.getElementById('youth-org-select');
  const organizationDropdown = document.getElementById('r_organization');
  const youthOrganizationDropdown = document.getElementById('r_youth_organization');

  // Load organizations from database
  async function loadOrganizations() {
    try {
      const response = await fetch('/LYDO/lydo-system/api/get_organizations.php');
      const data = await response.json();
      
      if (data.success && data.organizations) {
        // Populate president organization dropdown
        organizationDropdown.innerHTML = '<option value="">Search and select organization...</option>';
        data.organizations.forEach(org => {
          const option = document.createElement('option');
          option.value = org.id;
          option.textContent = org.name;
          organizationDropdown.appendChild(option);
        });

        // Populate youth organization dropdown
        youthOrganizationDropdown.innerHTML = '<option value="">Search and select organization...</option>';
        data.organizations.forEach(org => {
          const option = document.createElement('option');
          option.value = org.id;
          option.textContent = org.name;
          youthOrganizationDropdown.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Failed to load organizations:', error);
    }
  }

  // Handle role change
  function handleRoleChange() {
    if (rolePresident.checked) {
      // Show organization section for president
      orgSection.style.display = 'block';
      youthOrgSection.style.display = 'none';
      organizationDropdown.required = true;
      youthOrganizationDropdown.required = false;
    } else {
      // Show youth org affiliation section
      orgSection.style.display = 'none';
      youthOrgSection.style.display = 'block';
      organizationDropdown.required = false;
      // Youth org dropdown required only if affiliated
      youthOrganizationDropdown.required = orgAffiliated.checked;
    }
  }

  // Handle youth organization affiliation change
  function handleYouthOrgAffiliation() {
    if (orgAffiliated.checked) {
      youthOrgSelect.style.display = 'block';
      youthOrganizationDropdown.required = true;
    } else {
      youthOrgSelect.style.display = 'none';
      youthOrganizationDropdown.required = false;
      youthOrganizationDropdown.value = '';
    }
  }

  // Event listeners
  roleYouth.addEventListener('change', handleRoleChange);
  rolePresident.addEventListener('change', handleRoleChange);
  orgNone.addEventListener('change', handleYouthOrgAffiliation);
  orgAffiliated.addEventListener('change', handleYouthOrgAffiliation);

  // Load organizations on page load
  loadOrganizations();

  // Initialize
  handleRoleChange();
})();

// ===== PASSWORD STRENGTH CHECKER =====
(function() {
  const passwordInput = document.getElementById('r_password');
  const confirmPasswordInput = document.getElementById('r_confirm_pw');
  const strengthContainer = document.getElementById('password-strength-container');
  const strengthFill = document.getElementById('password-strength-fill');
  const strengthValue = document.getElementById('password-strength-value');
  const strengthHint = document.getElementById('password-strength-hint');
  const matchHint = document.getElementById('password-match-hint');

  // Check if elements exist
  if (!passwordInput || !confirmPasswordInput || !strengthContainer) {
    console.error('Password strength elements not found');
    return;
  }

  function checkPasswordStrength(password) {
    if (!password) {
      return { strength: 'none', score: 0 };
    }

    let score = 0;
    const checks = {
      length: password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[^A-Za-z0-9]/.test(password)
    };

    // Scoring system
    if (checks.length) score++;
    if (checks.lowercase) score++;
    if (checks.uppercase) score++;
    if (checks.number) score++;
    if (checks.special) score++;

    // Determine strength
    let strength = 'weak';
    let message = '';
    
    if (score <= 2) {
      strength = 'weak';
      const missing = [];
      if (!checks.length) missing.push('at least 8 characters');
      if (!checks.uppercase) missing.push('uppercase letters');
      if (!checks.number) missing.push('numbers');
      if (!checks.special) missing.push('special characters (!@#$%^&*)');
      message = 'Weak password. Add ' + missing.join(', ') + ' to make it stronger.';
    } else if (score === 3 || score === 4) {
      strength = 'medium';
      const missing = [];
      if (!checks.uppercase) missing.push('uppercase letters');
      if (!checks.special) missing.push('special characters');
      if (missing.length > 0) {
        message = 'Good! Consider adding ' + missing.join(' and ') + ' for maximum security.';
      } else {
        message = 'Good password strength!';
      }
    } else {
      strength = 'strong';
      message = 'Excellent! Your password is strong and secure.';
    }

    return { strength, score, message, checks };
  }

  function updatePasswordStrength() {
    const password = passwordInput.value;
    
    if (password.length === 0) {
      strengthContainer.style.display = 'none';
      return;
    }

    strengthContainer.style.display = 'block';
    const result = checkPasswordStrength(password);
    
    // Update fill bar
    strengthFill.className = 'password-strength-fill ' + result.strength;
    
    // Update text
    strengthValue.className = 'strength-' + result.strength;
    strengthValue.textContent = result.strength.charAt(0).toUpperCase() + result.strength.slice(1);
    
    // Update hint
    strengthHint.className = 'password-hint ' + result.strength;
    strengthHint.textContent = result.message;
  }

  function checkPasswordMatch() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (confirmPassword.length === 0) {
      matchHint.style.display = 'none';
      return;
    }
    
    matchHint.style.display = 'block';
    
    if (password === confirmPassword) {
      matchHint.className = 'password-hint match';
      matchHint.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match!';
    } else {
      matchHint.className = 'password-hint no-match';
      matchHint.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
    }
  }

  // Real-time password strength checking
  passwordInput.addEventListener('input', updatePasswordStrength);
  passwordInput.addEventListener('keyup', updatePasswordStrength);

  // Real-time password match checking
  confirmPasswordInput.addEventListener('input', checkPasswordMatch);
  confirmPasswordInput.addEventListener('keyup', checkPasswordMatch);
  passwordInput.addEventListener('input', checkPasswordMatch);
})();

// ===== REGISTER FORM SUBMIT =====
=======
// ===== LOAD ORGANIZATIONS INTO REGISTRATION DROPDOWN =====
document.addEventListener('DOMContentLoaded', function() {
  fetch('backend/api/organizations.php')
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


>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
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

<<<<<<< HEAD
  // Get registration role
  const registerAs = document.querySelector('input[name="register_as"]:checked');
  if (registerAs) {
    payload.append('register_as', registerAs.value);
  }

  // Get organization data
  if (registerAs && registerAs.value === 'organization_president') {
    const orgId = document.getElementById('r_organization').value;
    if (orgId) {
      payload.append('organization_id', orgId);
    }
  } else {
    // Youth member organization affiliation
    const orgAffiliation = document.querySelector('input[name="org_affiliation"]:checked');
    if (orgAffiliation && orgAffiliation.value === 'affiliated') {
      const youthOrgId = document.getElementById('r_youth_organization').value;
      if (youthOrgId) {
        payload.append('youth_organization_id', youthOrgId);
      }
    }
  }

=======
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
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
<<<<<<< HEAD
    if (el) payload.append(name, el.value);
=======
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
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
  }

  document.querySelectorAll('input[name="classification"]:checked').forEach(cb => {
    payload.append('youth_classification[]', cb.value);
  });
  document.querySelectorAll('input[name="programs"]:checked').forEach(cb => {
    payload.append('programs_interested[]', cb.value);
  });

  try {
<<<<<<< HEAD
    const res  = await fetch('/LYDO/lydo-system/register.php', {
=======
    const res  = await fetch('register.php', {
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
      method: 'POST', body: payload
    });
    const json = await res.json();

    if (json.success) {
      closeModal();
      goToStep(1);
      this.reset();
      const toast = document.createElement('div');
      toast.style.cssText = 'position:fixed;top:24px;left:50%;transform:translateX(-50%);background:#2e7d32;color:#fff;padding:16px 28px;border-radius:12px;font-weight:600;font-size:.95rem;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.2);display:flex;align-items:center;gap:10px;max-width:90vw;text-align:center';
<<<<<<< HEAD
      toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + json.message + ' <span style="opacity:0.8;font-size:0.85rem;margin-left:8px">Redirecting to login...</span>';
      document.body.appendChild(toast);
      
      // Redirect to appropriate login page after 2 seconds
      setTimeout(() => {
        const registerAs = document.querySelector('input[name="register_as"]:checked');
        if (registerAs && registerAs.value === 'organization_president') {
          window.location.href = '/LYDO/lydo-system/org-president/login.php';
        } else {
          window.location.href = '/LYDO/lydo-system/login.php';
        }
      }, 2000);
    } else {
      alert(json.message || 'Registration failed. Please try again.');
      btn.innerHTML = '<i class="fas fa-check"></i> Register Account';
      btn.disabled  = false;
    }
  } catch (err) {
    alert('Cannot connect to server. Make sure XAMPP is running at http://localhost');
=======
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
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75
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

<<<<<<< HEAD

// ===== FETCH REAL-TIME LANDING PAGE STATS =====
async function updateLandingStats() {
  try {
    console.log('Fetching landing stats...');
    const response = await fetch('/LYDO/get_landing_stats.php');
    const data = await response.json();
    console.log('Stats received:', data);
    
    if (data.success) {
      // Update hero stats
      const statsNums = document.querySelectorAll('.hero-stats .stat-num');
      console.log('Found stat elements:', statsNums.length);
      
      if (statsNums[0]) {
        const currentYouth = parseInt(statsNums[0].textContent) || 0;
        console.log('Updating youth:', currentYouth, '->', data.youth);
        animateNumber(statsNums[0], currentYouth, data.youth);
      }
      if (statsNums[1]) {
        const currentPrograms = parseInt(statsNums[1].textContent.replace('+', '')) || 0;
        console.log('Updating programs:', currentPrograms, '->', data.programs);
        animateNumber(statsNums[1], currentPrograms, data.programs, '+');
      }
      if (statsNums[2]) {
        const currentEvents = parseInt(statsNums[2].textContent.replace('+', '')) || 0;
        console.log('Updating events:', currentEvents, '->', data.events);
        animateNumber(statsNums[2], currentEvents, data.events, '+');
      }
    }
  } catch (error) {
    console.error('Failed to fetch landing stats:', error);
  }
}

// Animate number counting
function animateNumber(element, start, end, suffix = '') {
  const duration = 1000;
  const frameDuration = 1000 / 60;
  const totalFrames = Math.round(duration / frameDuration);
  let frame = 0;
  
  const counter = setInterval(() => {
    frame++;
    const progress = frame / totalFrames;
    const current = Math.round(start + (end - start) * progress);
    element.textContent = current + suffix;
    
    if (frame === totalFrames) {
      clearInterval(counter);
    }
  }, frameDuration);
}

// Fetch stats when page loads
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    updateLandingStats();
    updateLandingEvents();
  });
} else {
  // DOM is already loaded
  updateLandingStats();
  updateLandingEvents();
}

// ===== FETCH REAL-TIME EVENTS =====
async function updateLandingEvents() {
  try {
    console.log('Fetching landing events...');
    const response = await fetch('/LYDO/get_landing_events.php');
    const data = await response.json();
    console.log('Events received:', data);
    
    if (data.success && data.events.length > 0) {
      const eventsGrid = document.querySelector('.events-grid');
      if (!eventsGrid) return;
      
      // Clear existing events
      eventsGrid.innerHTML = '';
      
      // Add real events
      data.events.forEach(event => {
        const eventCard = `
          <div class="event-card animate-on-scroll visible">
            <div class="event-date">
              <span class="event-day">${event.day}</span>
              <span class="event-month">${event.month}</span>
            </div>
            <div class="event-info">
              <span class="event-tag ${event.type.toLowerCase()}">${event.type}</span>
              <h3>${event.name}</h3>
              <p><i class="fas fa-map-marker-alt"></i> ${event.location}</p>
            </div>
          </div>
        `;
        eventsGrid.insertAdjacentHTML('beforeend', eventCard);
      });
    }
  } catch (error) {
    console.error('Failed to fetch landing events:', error);
  }
}


// ===== CONTACT FORM SUBMISSION =====
const contactForm = document.getElementById('contactForm');
if (contactForm) {
  contactForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    const formData = new FormData();
    formData.append('first_name', document.getElementById('fname').value);
    formData.append('last_name', document.getElementById('lname').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('subject', document.getElementById('subject').value);
    formData.append('message', document.getElementById('message').value);
    
    try {
      const response = await fetch('/LYDO/submit_contact.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('✅ ' + data.message);
        contactForm.reset();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (error) {
      console.error('Contact form error:', error);
      alert('❌ Failed to send message. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });
}


// ===== REAL-TIME VALIDATION - CLEAR ERROR ON INPUT =====
document.addEventListener('DOMContentLoaded', function() {
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('input', function(e) {
      const field = e.target;
      if (field.hasAttribute('required') && field.value.trim() !== '') {
        field.style.borderColor = '';
      }
    });
    
    registerForm.addEventListener('change', function(e) {
      const field = e.target;
      if (field.tagName === 'SELECT' && field.hasAttribute('required') && field.value !== '') {
        field.style.borderColor = '';
      }
    });
  }
});


// ===== CUSTOM NOTIFICATION SYSTEM =====
function showNotification(type, title, message) {
  const overlay = document.getElementById('notificationOverlay');
  const icon = document.getElementById('notificationIcon');
  const titleEl = document.getElementById('notificationTitle');
  const messageEl = document.getElementById('notificationMessage');
  
  // Set icon based on type
  const icons = {
    warning: 'fa-exclamation-triangle',
    error: 'fa-times-circle',
    success: 'fa-check-circle'
  };
  
  icon.className = 'notification-icon ' + type;
  icon.innerHTML = '<i class="fas ' + icons[type] + '"></i>';
  titleEl.textContent = title;
  messageEl.textContent = message;
  
  overlay.classList.add('show');
  
  // Auto-close after 3 seconds
  setTimeout(() => {
    closeNotification();
  }, 3000);
}

function closeNotification() {
  document.getElementById('notificationOverlay').classList.remove('show');
}

// Close notification when clicking outside
document.getElementById('notificationOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeNotification();
});


// ===== CONTACT NUMBER - NUMBERS ONLY, MAX 11 DIGITS =====
const contactInput = document.getElementById('r_contact');
if (contactInput) {
  contactInput.addEventListener('input', function(e) {
    // Remove any non-digit characters
    this.value = this.value.replace(/\D/g, '');
    
    // Limit to 11 digits
    if (this.value.length > 11) {
      this.value = this.value.slice(0, 11);
    }
  });
  
  // Prevent pasting non-numeric content
  contactInput.addEventListener('paste', function(e) {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    const numbersOnly = pastedText.replace(/\D/g, '').slice(0, 11);
    this.value = numbersOnly;
  });
}
=======
// ===== LIVE STATS FROM DATABASE =====
fetch('api/stats.php')
  .then(r => r.json())
  .then(d => {
    document.getElementById('stat-youth').textContent    = d.youth.toLocaleString() + '+';
    document.getElementById('stat-programs').textContent = d.programs + '+';
    document.getElementById('stat-events').textContent   = d.events + '+';
  })
  .catch(() => {
    document.getElementById('stat-youth').textContent    = '5,000+';
    document.getElementById('stat-programs').textContent = '30+';
    document.getElementById('stat-events').textContent   = '50+';
  });
>>>>>>> f579e306e8d60a77056a93cfb7823ed4f2e46f75

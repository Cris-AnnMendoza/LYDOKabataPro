const API = 'http://localhost/lydo-system/backend/api/admin';
let adminInfo = null;
let permissions = [];
let confirmCallback = null;

//  auth guard 
const token = localStorage.getItem('admin_token');
if (!token) { window.location.href = 'index.html'; }

function authHeaders() {
  return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token };
}

//  load admin info 
async function loadAdminInfo() {
  try {
    const res  = await fetch(API + '/me.php', { headers: authHeaders() });
    const data = await res.json();
    if (!data.success) { logout(); return; }
    adminInfo   = data.admin;
    permissions = data.admin.permissions || [];
    renderAdminUI();
    applyRoleAccess();
    loadDashboard();
  } catch { logout(); }
}

function renderAdminUI() {
  const initials = adminInfo.full_name.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
  document.getElementById('adminAvatar').textContent   = initials;
  document.getElementById('adminName').textContent     = adminInfo.full_name;
  document.getElementById('adminRole').textContent     = adminInfo.role.replace(/_/g,' ');
  document.getElementById('topbarAdminName').textContent = adminInfo.full_name;
  const rp = document.getElementById('topbarRole');
  rp.textContent  = adminInfo.role.replace(/_/g,' ');
  rp.className    = 'role-pill ' + adminInfo.role;
}

function applyRoleAccess() {
  document.querySelectorAll('.nav-item[data-perm]').forEach(item => {
    const perms = item.dataset.perm.split(',');
    const ok    = perms.some(p => permissions.includes(p.trim()));
    item.classList.toggle('hidden', !ok);
  });
  document.querySelectorAll('.nav-section-label[data-perm]').forEach(el => {
    const ok = permissions.includes(el.dataset.perm);
    el.classList.toggle('hidden', !ok);
  });
}

//  navigation 
function showPage(name) {
  document.querySelectorAll('.page').forEach(p => p.style.display = 'none');
  const pg = document.getElementById('page-' + name);
  if (pg) pg.style.display = 'block';
  document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
  const ni = document.querySelector('.nav-item[data-page="' + name + '"]');
  if (ni) ni.classList.add('active');
  const titles = { dashboard:'Dashboard', users:'Youth Users', programs:'Programs',
    events:'Events', reports:'Reports', export:'Export Data', admins:'Manage Admins',
    logs:'Activity Logs', settings:'Settings' };
  document.getElementById('pageTitle').textContent = titles[name] || name;
  if (name === 'users')  loadUsers();
  if (name === 'admins') loadAdmins();
}

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', e => {
    e.preventDefault();
    showPage(item.dataset.page);
    if (window.innerWidth < 900) document.getElementById('sidebar').classList.remove('open');
  });
});

//  sidebar toggle 
document.getElementById('sidebarToggle').addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
});

//  logout 
function logout() {
  fetch(API + '/logout.php', { method:'POST', headers: authHeaders() }).catch(()=>{});
  localStorage.removeItem('admin_token');
  localStorage.removeItem('admin_info');
  window.location.href = 'index.html';
}
document.getElementById('logoutBtn').addEventListener('click', logout);

//  DASHBOARD 
async function loadDashboard() {
  try {
    const res  = await fetch(API + '/dashboard.php', { headers: authHeaders() });
    const data = await res.json();
    if (!data.success) return;

    document.getElementById('statTotal').textContent    = data.total_users.toLocaleString();
    document.getElementById('statMonth').textContent    = data.new_this_month.toLocaleString();
    document.getElementById('statBarangays').textContent = data.by_barangay.length;

    // Recent users table
    const tbody = document.getElementById('recentBody');
    if (!data.recent_users.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="loading-row">No registrations yet.</td></tr>';
    } else {
      tbody.innerHTML = data.recent_users.map((u,i) => `
        <tr>
          <td>${i+1}</td>
          <td><strong>${u.first_name} ${u.last_name}</strong></td>
          <td>${u.email}</td>
          <td>${u.barangay || ''}</td>
          <td>${new Date(u.created_at).toLocaleDateString('en-PH')}</td>
        </tr>`).join('');
    }

    // Gender chart
    const gWrap = document.getElementById('genderChart');
    const total = data.by_gender.reduce((s,g) => s + parseInt(g.count), 0) || 1;
    const colors = { Male:'#1565c0', Female:'#e91e63', 'Non-binary':'#9c27b0', 'Prefer not to say':'#78909c' };
    gWrap.innerHTML = '<div class="gender-chart-wrap">' +
      data.by_gender.map(g => `
        <div class="gender-item">
          <div class="gender-dot" style="background:${colors[g.gender]||'#90a4ae'}"></div>
          <span class="gender-label">${g.gender || 'Unknown'}</span>
          <span class="gender-count">${g.count}</span>
          <span class="gender-pct">${Math.round(g.count/total*100)}%</span>
        </div>`).join('') + '</div>';

    // Barangay bars
    const maxCount = Math.max(...data.by_barangay.map(b => parseInt(b.count)), 1);
    document.getElementById('barangayBars').innerHTML =
      '<div class="brgy-bar-wrap">' +
      data.by_barangay.map(b => `
        <div class="brgy-bar-item">
          <span class="brgy-bar-label" title="${b.barangay}">${b.barangay}</span>
          <div class="brgy-bar-track">
            <div class="brgy-bar-fill" style="width:${Math.round(b.count/maxCount*100)}%"></div>
          </div>
          <span class="brgy-bar-count">${b.count}</span>
        </div>`).join('') + '</div>';
  } catch(err) { console.error(err); }
}

//  USERS 
let usersPage = 1;
async function loadUsers(page = 1) {
  usersPage = page;
  const search   = document.getElementById('userSearch').value;
  const barangay = document.getElementById('barangayFilter').value;
  const params   = new URLSearchParams({ page, search, barangay });
  const tbody    = document.getElementById('usersBody');
  tbody.innerHTML = '<tr><td colspan="8" class="loading-row">Loading...</td></tr>';
  try {
    const res  = await fetch(`${API}/users.php?${params}`, { headers: authHeaders() });
    const data = await res.json();
    if (!data.users.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No users found.</td></tr>';
      return;
    }
    const canDel = permissions.includes('delete_users');
    tbody.innerHTML = data.users.map((u,i) => `
      <tr>
        <td>${(page-1)*20+i+1}</td>
        <td><strong>${u.first_name} ${u.last_name}</strong></td>
        <td>${u.email}</td>
        <td>${u.gender || ''}</td>
        <td>${u.barangay || ''}</td>
        <td><span class="badge badge-blue">${u.youth_classification ? JSON.parse(u.youth_classification)[0] || u.youth_classification : ''}</span></td>
        <td>${new Date(u.created_at).toLocaleDateString('en-PH')}</td>
        <td>
          <button class="btn-icon view" title="View"><i class="fas fa-eye"></i></button>
          ${canDel ? `<button class="btn-icon del" onclick="deleteUser(${u.id},'${u.first_name} ${u.last_name}')" title="Delete"><i class="fas fa-trash"></i></button>` : ''}
        </td>
      </tr>`).join('');

    // Pagination
    const pg = document.getElementById('usersPagination');
    pg.innerHTML = '';
    for (let i = 1; i <= data.pages; i++) {
      const btn = document.createElement('button');
      btn.className = 'page-btn' + (i === page ? ' active' : '');
      btn.textContent = i;
      btn.onclick = () => loadUsers(i);
      pg.appendChild(btn);
    }
  } catch(err) { console.error(err); }
}

let searchTimer;
document.getElementById('userSearch').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadUsers(1), 400);
});
document.getElementById('barangayFilter').addEventListener('change', () => loadUsers(1));

function deleteUser(id, name) {
  document.getElementById('confirmMsg').textContent = `Delete "${name}"? This cannot be undone.`;
  document.getElementById('confirmModal').classList.add('open');
  confirmCallback = async () => {
    const res  = await fetch(`${API}/users.php?id=${id}`, { method:'DELETE', headers: authHeaders() });
    const data = await res.json();
    document.getElementById('confirmModal').classList.remove('open');
    if (data.success) loadUsers(usersPage);
    else alert(data.message);
  };
}

//  ADMINS 
async function loadAdmins() {
  const tbody = document.getElementById('adminsBody');
  tbody.innerHTML = '<tr><td colspan="8" class="loading-row">Loading...</td></tr>';
  try {
    const res  = await fetch(API + '/admins.php', { headers: authHeaders() });
    const data = await res.json();
    const roleColors = { super_admin:'badge-orange', youth_coordinator:'badge-blue',
                         barangay_admin:'badge-green', staff_encoder:'badge-gray' };
    tbody.innerHTML = data.admins.map((a,i) => `
      <tr>
        <td>${i+1}</td>
        <td><strong>${a.full_name}</strong></td>
        <td>${a.email}</td>
        <td><span class="badge ${roleColors[a.role]||'badge-gray'}">${a.role.replace(/_/g,' ')}</span></td>
        <td>${a.barangay || ''}</td>
        <td>${a.last_login ? new Date(a.last_login).toLocaleString('en-PH') : 'Never'}</td>
        <td><span class="badge ${a.is_active ? 'badge-green' : 'badge-red'}">${a.is_active ? 'Active' : 'Inactive'}</span></td>
        <td>
          <button class="btn-icon del" onclick="deleteAdmin(${a.id},'${a.full_name}')" title="Delete"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  } catch(err) { console.error(err); }
}

// Add admin modal
document.getElementById('openAddAdmin').addEventListener('click', () => {
  document.getElementById('addAdminModal').classList.add('open');
});
document.getElementById('closeAddAdmin').addEventListener('click', () => {
  document.getElementById('addAdminModal').classList.remove('open');
});
document.getElementById('cancelAddAdmin').addEventListener('click', () => {
  document.getElementById('addAdminModal').classList.remove('open');
});
document.getElementById('a_role').addEventListener('change', function () {
  document.getElementById('barangayField').style.display =
    this.value === 'barangay_admin' ? 'block' : 'none';
});

document.getElementById('addAdminForm').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = this.querySelector('.btn-save');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  const body = {
    full_name: document.getElementById('a_name').value,
    email:     document.getElementById('a_email').value,
    password:  document.getElementById('a_password').value,
    role:      document.getElementById('a_role').value,
    barangay:  document.getElementById('a_barangay').value,
  };
  try {
    const res  = await fetch(API + '/admins.php', { method:'POST', headers: authHeaders(), body: JSON.stringify(body) });
    const data = await res.json();
    if (data.success) {
      document.getElementById('addAdminModal').classList.remove('open');
      this.reset();
      loadAdmins();
    } else { alert(data.message); }
  } catch { alert('Server error.'); }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-save"></i> Create Admin';
});

function deleteAdmin(id, name) {
  document.getElementById('confirmMsg').textContent = `Delete admin "${name}"? This cannot be undone.`;
  document.getElementById('confirmModal').classList.add('open');
  confirmCallback = async () => {
    const res  = await fetch(`${API}/admins.php?id=${id}`, { method:'DELETE', headers: authHeaders() });
    const data = await res.json();
    document.getElementById('confirmModal').classList.remove('open');
    if (data.success) loadAdmins();
    else alert(data.message);
  };
}

//  confirm modal 
document.getElementById('confirmAction').addEventListener('click', () => { if (confirmCallback) confirmCallback(); });
document.getElementById('closeConfirm').addEventListener('click',  () => document.getElementById('confirmModal').classList.remove('open'));
document.getElementById('cancelConfirm').addEventListener('click', () => document.getElementById('confirmModal').classList.remove('open'));

//  init 
loadAdminInfo();

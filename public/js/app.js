let currentPin = '';

const pages = {
  dashboard: { title: 'Dashboard', render: renderDashboard },
  customers: { title: 'Customers', render: renderCustomers },
  products: { title: 'Products', render: renderProducts },
  billing: { title: 'Billing', render: renderBilling },
  credits: { title: 'Credits', render: renderCredits },
  reports: { title: 'Reports', render: renderReports },
  analytics: { title: 'Analytics', render: renderAnalytics },
  settings: { title: 'Settings', render: renderSettings }
};

async function init() {
  const d = await apiGet('auth.php?action=check');
  if (!d.logged_in) {
    window.location.href = 'index.html';
    return;
  }
  document.getElementById('userName').textContent = d.user;
  if (!d.pin_verified) {
    document.getElementById('pinScreen').style.display = 'flex';
  } else {
    document.getElementById('mainApp').style.display = 'flex';
    loadPage('dashboard');
    loadNotifications();
    setInterval(loadNotifications, 60000);
  }
}

function pinInput(n) {
  if (currentPin.length >= 6) return;
  currentPin += n;
  updatePinDisplay();
}

function pinClear() {
  currentPin = '';
  updatePinDisplay();
}

function updatePinDisplay() {
  document.getElementById('pinDisplay').textContent = '•'.repeat(currentPin.length) + '○'.repeat(4 - currentPin.length);
}

async function pinSubmit() {
  const fd = new FormData();
  fd.append('action', 'verify_pin');
  fd.append('pin', currentPin);
  const d = await api('auth.php', { method: 'POST', body: fd });
  if (d.ok) {
    document.getElementById('pinScreen').style.display = 'none';
    document.getElementById('mainApp').style.display = 'flex';
    loadPage('dashboard');
    loadNotifications();
    setInterval(loadNotifications, 60000);
  } else {
    document.getElementById('pinError').textContent = d.msg;
    currentPin = '';
    updatePinDisplay();
  }
}

function loadPage(page) {
  if (!pages[page]) return;
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  document.querySelector(`[data-page="${page}"]`)?.classList.add('active');
  document.getElementById('pageTitle').textContent = pages[page].title;
  pages[page].render();
  if (window.innerWidth < 768) document.getElementById('sidebar').classList.remove('open');
}

document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    loadPage(link.dataset.page);
  });
});

async function logout() {
  if (!confirm('Logout?')) return;
  await apiGet('auth.php?action=logout');
  window.location.href = 'index.html';
}

init();
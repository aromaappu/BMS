function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, ch => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[ch]);
}

function money(n) { return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

function toast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

function openModal(title, html) {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = html;
  document.getElementById('modal').classList.add('show');
}

function closeModal() {
  document.getElementById('modal').classList.remove('show');
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

function toggleNotif() {
  document.getElementById('notifDropdown').classList.toggle('show');
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('.notif-wrap')) {
    document.getElementById('notifDropdown')?.classList.remove('show');
  }
});

async function loadNotifications() {
  const d = await apiGet('notifications.php');
  if (!d.ok) return;
  const list = d.data;
  document.getElementById('notifBadge').textContent = list.length;
  const dd = document.getElementById('notifDropdown');
  if (list.length === 0) {
    dd.innerHTML = '<div class="notif-item">No notifications</div>';
    return;
  }
  dd.innerHTML = list.map(n => `
    <div class="notif-item ${n.type}">
      <span class="icon">${n.icon}</span>
      <div style="flex:1">
        <div>${n.msg}</div>
        ${n.data && n.data.phone ? `<button class="btn btn-sm btn-success" style="margin-top:5px" onclick="sendWhatsApp('${n.data.phone}','${n.data.name}','${n.data.bill_no}',${n.data.due},'${n.data.due_date}')">💬 WhatsApp</button>` : ''}
      </div>
    </div>
  `).join('');
}

function sendWhatsApp(phone, name, billNo, amount, dueDate) {
  phone = phone.replace(/\D/g, '');
  if (phone.startsWith('0')) phone = '91' + phone.substring(1);
  if (!phone.startsWith('91')) phone = '91' + phone;
  const msg = `Hello ${name},%0A%0AThis is a friendly reminder regarding your pending payment.%0A%0ABill Number: ${billNo}%0AOutstanding Amount: ₹${amount}%0ADue Date: ${dueDate}%0A%0AKindly complete the payment at your earliest convenience.%0A%0AThank You.`;
  window.open(`https://wa.me/${phone}?text=${msg}`, '_blank');
}

function filterTable(tableId, query) {
  query = query.toLowerCase();
  const rows = document.querySelectorAll(`#${tableId} tbody tr`);
  rows.forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
}
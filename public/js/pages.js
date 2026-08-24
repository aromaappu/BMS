// ============ DASHBOARD ============
async function renderDashboard() {
  const d = await apiGet('dashboard.php');
  if (!d.ok) return;
  document.getElementById('pageContent').innerHTML = `
    <div class="stats">
      <div class="stat-card"><div class="stat-icon purple">💰</div><div class="stat-info"><h4>Today's Sales</h4><div class="value">${money(d.today_sales)}</div></div></div>
      <div class="stat-card"><div class="stat-icon green">📈</div><div class="stat-info"><h4>Today's Profit</h4><div class="value">${money(d.today_profit)}</div></div></div>
      <div class="stat-card"><div class="stat-icon blue">👥</div><div class="stat-info"><h4>Customers</h4><div class="value">${d.customers}</div></div></div>
      <div class="stat-card"><div class="stat-icon pink">📦</div><div class="stat-info"><h4>Products</h4><div class="value">${d.products}</div></div></div>
      <div class="stat-card"><div class="stat-icon orange">💳</div><div class="stat-info"><h4>Total Credit</h4><div class="value">${money(d.credit)}</div></div></div>
      <div class="stat-card"><div class="stat-icon red">⚠️</div><div class="stat-info"><h4>Low Stock</h4><div class="value">${d.low_stock}</div></div></div>
    </div>
    <div class="row">
      <div class="card"><h3>📊 Monthly Sales</h3><div class="chart-container"><canvas id="salesChart"></canvas></div></div>
      <div class="card"><h3>💹 Profit Trend</h3><div class="chart-container"><canvas id="profitChart"></canvas></div></div>
    </div>
    <div class="row">
      <div class="card"><h3>🔥 Best Selling</h3>
        <table><thead><tr><th>Product</th><th>Qty Sold</th></tr></thead>
        <tbody>${d.best.map(b => `<tr><td>${b.name}</td><td><span class="badge-pill badge-success">${b.q}</span></td></tr>`).join('')}</tbody></table>
      </div>
      <div class="card"><h3>🐢 Slow Moving</h3>
        <table><thead><tr><th>Product</th><th>Qty Sold</th></tr></thead>
        <tbody>${d.slow.map(b => `<tr><td>${b.name}</td><td><span class="badge-pill badge-warning">${b.q}</span></td></tr>`).join('')}</tbody></table>
      </div>
    </div>
  `;
  new Chart(salesChart, {
    type: 'bar',
    data: { labels: d.months, datasets: [{ label: 'Sales', data: d.sales_data, backgroundColor: '#6366f1', borderRadius: 8 }] },
    options: { responsive: true, maintainAspectRatio: false }
  });
  new Chart(profitChart, {
    type: 'line',
    data: { labels: d.months, datasets: [{ label: 'Profit', data: d.profit_data, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 }] },
    options: { responsive: true, maintainAspectRatio: false }
  });
}

// ============ CUSTOMERS ============
async function renderCustomers() {
  const d = await apiGet('customers.php');
  document.getElementById('pageContent').innerHTML = `
    <div class="card">
      <div class="toolbar">
        <input type="text" placeholder="🔍 Search customer..." onkeyup="filterTable('custTbl', this.value)">
        <button class="btn btn-primary" onclick="customerForm()">+ Add Customer</button>
      </div>
      <div class="table-wrap">
        <table id="custTbl">
          <thead><tr><th>Name</th><th>Phone</th><th>Address</th><th>GST</th><th>Credit</th><th>Actions</th></tr></thead>
          <tbody>${d.data.map(c => `
            <tr>
              <td><strong>${c.name}</strong></td>
              <td>${c.phone || '-'}</td>
              <td>${c.address || '-'}</td>
              <td>${c.gst_number || '-'}</td>
              <td>${Number(c.credit) > 0 ? `<span class="badge-pill badge-danger">${money(c.credit)}</span>` : '<span class="badge-pill badge-success">₹0</span>'}</td>
              <td>
                <button class="btn btn-sm btn-info" onclick="customerHistory(${c.id}, '${c.name}')">📜</button>
                <button class="btn btn-sm btn-warning" onclick='customerForm(${JSON.stringify(c)})'>✏️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${c.id})">🗑️</button>
              </td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function customerForm(c = {}) {
  openModal(c.id ? 'Edit Customer' : 'Add Customer', `
    <form onsubmit="saveCustomer(event)">
      <input type="hidden" name="id" value="${c.id || ''}">
      <div class="form-group"><label>Name *</label><input name="name" value="${c.name || ''}" required></div>
      <div class="form-group"><label>Phone</label><input name="phone" value="${c.phone || ''}"></div>
      <div class="form-group"><label>Address</label><textarea name="address" rows="2">${c.address || ''}</textarea></div>
      <div class="form-group"><label>GST Number</label><input name="gst_number" value="${c.gst_number || ''}"></div>
      <button type="submit" class="btn btn-primary btn-block">💾 Save</button>
    </form>
  `);
}

async function saveCustomer(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'save');
  const d = await api('customers.php', { method: 'POST', body: fd });
  if (d.ok) { toast('Saved!'); closeModal(); renderCustomers(); }
  else toast(d.msg, 'error');
}

async function deleteCustomer(id) {
  if (!confirm('Delete this customer?')) return;
  await apiGet('customers.php?action=delete&id=' + id);
  toast('Deleted');
  renderCustomers();
}

async function customerHistory(id, name) {
  const d = await apiGet('customers.php?action=history&id=' + id);
  openModal('History - ' + name, `
    <div class="table-wrap">
      <table>
        <thead><tr><th>Bill#</th><th>Date</th><th>Total</th><th>Mode</th></tr></thead>
        <tbody>${d.data.map(b => `<tr><td>${b.bill_no}</td><td>${b.created_at}</td><td>${money(b.total)}</td><td><span class="badge-pill badge-info">${b.payment_mode}</span></td></tr>`).join('')}</tbody>
      </table>
    </div>
  `);
}

// ============ PRODUCTS ============
async function renderProducts() {
  const [prods, cats] = await Promise.all([apiGet('products.php'), apiGet('products.php?action=categories')]);
  window._cats = cats.data;
  document.getElementById('pageContent').innerHTML = `
    <div class="card">
      <div class="toolbar">
        <input type="text" placeholder="🔍 Search..." onkeyup="filterTable('prodTbl', this.value)">
        <select onchange="filterCat(this.value)"><option value="">All Categories</option>${cats.data.map(c => `<option>${c.name}</option>`).join('')}</select>
        <button class="btn btn-primary" onclick="productForm()">+ Add Product</button>
      </div>
      <div class="table-wrap">
        <table id="prodTbl">
          <thead><tr><th>Name</th><th>Category</th><th>Purchase</th><th>Selling</th><th>Stock</th><th>Actions</th></tr></thead>
          <tbody>${prods.data.map(p => {
            const low = p.stock <= p.low_stock_threshold;
            return `<tr data-cat="${p.category_name || ''}">
              <td><strong>${p.name}</strong></td>
              <td><span class="badge-pill badge-info">${p.category_name || '-'}</span></td>
              <td>${money(p.purchase_price)}</td>
              <td>${money(p.selling_price)}</td>
              <td>${low ? `<span class="low-stock">${p.stock} ⚠️</span>` : p.stock}</td>
              <td>
                <button class="btn btn-sm btn-info" onclick="adjustStock(${p.id}, '${p.name}', ${p.stock})">📦</button>
                <button class="btn btn-sm btn-warning" onclick='productForm(${JSON.stringify(p)})'>✏️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id})">🗑️</button>
              </td>
            </tr>`;
          }).join('')}</tbody>
        </table>
      </div>
    </div>
  `;
}

function filterCat(cat) {
  document.querySelectorAll('#prodTbl tbody tr').forEach(r => {
    r.style.display = !cat || r.dataset.cat === cat ? '' : 'none';
  });
}

function productForm(p = {}) {
  openModal(p.id ? 'Edit Product' : 'Add Product', `
    <form onsubmit="saveProduct(event)">
      <input type="hidden" name="id" value="${p.id || ''}">
      <div class="form-group"><label>Product Name *</label><input name="name" value="${p.name || ''}" required></div>
      <div class="form-group"><label>Category</label>
        <select name="category_id">${window._cats.map(c => `<option value="${c.id}" ${p.category_id == c.id ? 'selected' : ''}>${c.name}</option>`).join('')}</select>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Purchase Price</label><input type="number" step="0.01" name="purchase_price" value="${p.purchase_price || 0}" required></div>
        <div class="form-group"><label>Selling Price</label><input type="number" step="0.01" name="selling_price" value="${p.selling_price || 0}" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Stock</label><input type="number" name="stock" value="${p.stock || 0}" required></div>
        <div class="form-group"><label>Low Stock Threshold</label><input type="number" name="low_stock_threshold" value="${p.low_stock_threshold || 5}"></div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">💾 Save</button>
    </form>
  `);
}

async function saveProduct(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'save');
  const d = await api('products.php', { method: 'POST', body: fd });
  if (d.ok) { toast('Saved!'); closeModal(); renderProducts(); }
  else toast(d.msg, 'error');
}

async function deleteProduct(id) {
  if (!confirm('Delete?')) return;
  await apiGet('products.php?action=delete&id=' + id);
  toast('Deleted');
  renderProducts();
}

function adjustStock(id, name, current) {
  openModal('Adjust Stock - ' + name, `
    <p>Current stock: <strong>${current}</strong></p>
    <div class="form-group"><label>Adjustment (+ to add, - to remove)</label>
      <input type="number" id="stockAdj" value="0">
    </div>
    <button class="btn btn-primary btn-block" onclick="saveStock(${id})">Update</button>
  `);
}

async function saveStock(id) {
  const qty = document.getElementById('stockAdj').value;
  const fd = new FormData();
  fd.append('action', 'adjust_stock');
  fd.append('id', id);
  fd.append('qty', qty);
  await api('products.php', { method: 'POST', body: fd });
  toast('Stock updated');
  closeModal();
  renderProducts();
}

// ============ BILLING ============
let cart = [];
let billProducts = [];

async function renderBilling() {
  const [prods, custs, settings] = await Promise.all([
    apiGet('products.php'),
    apiGet('customers.php'),
    apiGet('settings.php')
  ]);
  billProducts = prods.data;
  cart = [];
  window._custs = custs.data;
  window._settings = settings.data;
  
  document.getElementById('pageContent').innerHTML = `
    <div class="billing-grid">
      <div class="card">
        <h3>🛒 Select Products</h3>
        <input type="text" id="prodSearch" placeholder="🔍 Search product..." onkeyup="renderProdList()" style="width:100%;padding:10px;border:2px solid var(--border);border-radius:8px;margin-bottom:15px">
        <div id="prodList" style="max-height:500px;overflow-y:auto"></div>
      </div>
      <div class="card">
        <h3>🧾 Current Bill</h3>
        <div class="form-group"><label>Customer</label>
          <select id="billCustomer"><option value="">Walk-in Customer</option>${custs.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}</select>
        </div>
        <div id="cartItems"></div>
        <div id="cartSummary"></div>
      </div>
    </div>
  `;
  renderProdList();
  renderCart();
}

function renderProdList() {
  const q = (document.getElementById('prodSearch')?.value || '').toLowerCase();
  const list = billProducts.filter(p => p.name.toLowerCase().includes(q) && p.stock > 0);
  document.getElementById('prodList').innerHTML = list.map(p => `
    <div class="cart-item">
      <div><strong>${p.name}</strong><br><small>${money(p.selling_price)} • Stock: ${p.stock}</small></div>
      <button class="btn btn-sm btn-primary" onclick="addToCart(${p.id})">+ Add</button>
    </div>
  `).join('') || '<p style="text-align:center;color:var(--text-light)">No products found</p>';
}

function addToCart(pid) {
  const p = billProducts.find(x => x.id == pid);
  const existing = cart.find(x => x.product_id == pid);
  if (existing) {
    if (existing.qty < p.stock) existing.qty++;
    else return toast('No more stock', 'warning');
  } else {
    cart.push({ product_id: p.id, name: p.name, price: p.selling_price, purchase_price: p.purchase_price, qty: 1, max: p.stock });
  }
  renderCart();
}

function removeFromCart(pid) {
  cart = cart.filter(x => x.product_id != pid);
  renderCart();
}

function updateQty(pid, qty) {
  const item = cart.find(x => x.product_id == pid);
  if (qty <= 0) return removeFromCart(pid);
  if (qty > item.max) return toast('Not enough stock', 'warning');
  item.qty = qty;
  renderCart();
}

function renderCart() {
  const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
  const discount = parseFloat(document.getElementById('billDiscount')?.value || 0);
  const taxRate = window._settings.gst_enabled === '1' ? parseFloat(window._settings.default_gst || 0) : 0;
  const tax = (subtotal - discount) * taxRate / 100;
  const total = subtotal - discount + tax;
  
  document.getElementById('cartItems').innerHTML = cart.length === 0 ? '<p style="text-align:center;color:var(--text-light);padding:20px">Cart is empty</p>' :
    cart.map(i => `
      <div class="cart-item">
        <div style="flex:1"><strong>${i.name}</strong><br><small>${money(i.price)} × ${i.qty}</small></div>
        <div style="display:flex;align-items:center;gap:5px">
          <button class="btn btn-sm btn-secondary" onclick="updateQty(${i.product_id}, ${i.qty - 1})">-</button>
          <span>${i.qty}</span>
          <button class="btn btn-sm btn-secondary" onclick="updateQty(${i.product_id}, ${i.qty + 1})">+</button>
          <button class="btn btn-sm btn-danger" onclick="removeFromCart(${i.product_id})">×</button>
        </div>
      </div>
    `).join('');
  
  document.getElementById('cartSummary').innerHTML = `
    <div class="form-group"><label>Discount</label><input type="number" step="0.01" id="billDiscount" value="${discount}" oninput="renderCart()"></div>
    ${taxRate > 0 ? `<p style="margin-bottom:10px">Tax (${taxRate}%): ${money(tax)}</p>` : ''}
    <div class="form-group"><label>Payment Mode</label>
      <select id="billMode" onchange="document.getElementById('dueDateWrap').style.display=this.value==='credit'?'block':'none'">
        <option value="cash">💵 Cash</option>
        <option value="upi">📱 UPI</option>
        <option value="credit">💳 Credit</option>
      </select>
    </div>
    <div class="form-group" id="dueDateWrap" style="display:none"><label>Due Date</label><input type="date" id="billDueDate"></div>
    <div class="cart-total">
      <div class="total-row"><span>Subtotal:</span><span>${money(subtotal)}</span></div>
      ${discount > 0 ? `<div class="total-row"><span>Discount:</span><span>-${money(discount)}</span></div>` : ''}
      ${tax > 0 ? `<div class="total-row"><span>Tax:</span><span>+${money(tax)}</span></div>` : ''}
      <div class="total-row grand"><span>TOTAL:</span><span>${money(total)}</span></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:15px">
      <button class="btn btn-success" style="flex:1" onclick="submitBill()">✓ Save Bill</button>
      <button class="btn btn-secondary" onclick="cart=[];renderCart()">Clear</button>
    </div>
  `;
}

async function submitBill() {
  if (cart.length === 0) return toast('Cart is empty', 'error');
  const mode = document.getElementById('billMode').value;
  const data = {
    customer_id: document.getElementById('billCustomer').value,
    items: cart,
    discount: parseFloat(document.getElementById('billDiscount').value || 0),
    tax: 0,
    payment_mode: mode,
    due_date: mode === 'credit' ? document.getElementById('billDueDate').value : null
  };
  const d = await apiJson('billing.php?action=create', data);
  if (d.ok) {
    toast('Bill created: ' + d.bill_no);
    window.open('../api/pdf.php?id=' + d.bill_id, '_blank');
    cart = [];
    renderBilling();
    loadNotifications();
  } else toast(d.msg, 'error');
}

// ============ CREDITS ============
async function renderCredits() {
  const d = await apiGet('credits.php');
  document.getElementById('pageContent').innerHTML = `
    <div class="card">
      <h3>💳 Credit Sales</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Bill#</th><th>Customer</th><th>Phone</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>${d.data.map(b => {
            const overdue = b.outstanding > 0 && new Date(b.due_date) < new Date();
            return `<tr style="${overdue ? 'background:#fee2e2' : ''}">
              <td><strong>${b.bill_no}</strong></td>
              <td>${b.name || '-'}</td>
              <td>${b.phone || '-'}</td>
              <td>${money(b.total)}</td>
              <td>${money(b.paid)}</td>
              <td><strong>${money(b.outstanding)}</strong></td>
              <td>${b.due_date || '-'}</td>
              <td>${b.outstanding <= 0 ? '<span class="badge-pill badge-success">Paid</span>' : overdue ? '<span class="badge-pill badge-danger">Overdue</span>' : '<span class="badge-pill badge-warning">Pending</span>'}</td>
              <td>
                ${b.outstanding > 0 ? `<button class="btn btn-sm btn-success" onclick="payCredit(${b.id}, '${b.name}', ${b.outstanding})">💰 Pay</button>` : ''}
                ${b.phone && b.outstanding > 0 ? `<button class="btn btn-sm btn-info" onclick="sendWhatsApp('${b.phone}','${b.name}','${b.bill_no}',${b.outstanding},'${b.due_date}')">💬</button>` : ''}
              </td>
            </tr>`;
          }).join('')}</tbody>
        </table>
      </div>
    </div>
  `;
}

function payCredit(billId, name, outstanding) {
  openModal('Collect Payment - ' + name, `
    <p>Outstanding: <strong>${money(outstanding)}</strong></p>
    <form onsubmit="savePayment(event, ${billId})">
      <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" value="${outstanding}" required></div>
      <div class="form-group"><label>Note</label><input name="note" placeholder="Optional"></div>
      <button type="submit" class="btn btn-success btn-block">💰 Collect</button>
    </form>
  `);
}

async function savePayment(e, billId) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'pay');
  fd.append('bill_id', billId);
  const d = await api('credits.php', { method: 'POST', body: fd });
  if (d.ok) { toast('Payment recorded'); closeModal(); renderCredits(); }
  else toast(d.msg, 'error');
}

// ============ REPORTS ============
async function renderReports() {
  document.getElementById('pageContent').innerHTML = `
    <div class="card">
      <h3>📈 Reports</h3>
      <div class="toolbar">
        <select id="reportType">
          <option value="daily">Daily Sales</option>
          <option value="monthly">Monthly Sales</option>
          <option value="yearly">Yearly Sales</option>
          <option value="product">Product Wise</option>
          <option value="category">Category Wise</option>
          <option value="customer">Customer Wise</option>
          <option value="payment">Payment Method</option>
          <option value="credit">Credit Sales</option>
        </select>
        <input type="date" id="reportFrom" value="${new Date(Date.now() - 30*86400000).toISOString().split('T')[0]}">
        <input type="date" id="reportTo" value="${new Date().toISOString().split('T')[0]}">
        <button class="btn btn-primary" onclick="generateReport()">Generate</button>
        <button class="btn btn-success" onclick="exportCSV()">📥 CSV</button>
      </div>
      <div id="reportResult"></div>
    </div>
  `;
  generateReport();
}

let lastReport = null;
async function generateReport() {
  const type = document.getElementById('reportType').value;
  const from = document.getElementById('reportFrom').value;
  const to = document.getElementById('reportTo').value;
  const d = await apiGet(`reports.php?type=${type}&from=${from}&to=${to}`);
  lastReport = d;
  document.getElementById('reportResult').innerHTML = `
    <div class="row">
      <div class="card"><h3>Chart</h3><div class="chart-container"><canvas id="reportChart"></canvas></div></div>
      <div class="card"><h3>Total: ${money(d.total)}</h3>
        <div class="table-wrap" style="max-height:300px;overflow-y:auto">
          <table>
            <thead><tr><th>Label</th><th>Value</th></tr></thead>
            <tbody>${d.labels.map((l, i) => `<tr><td>${l}</td><td>${money(d.values[i])}</td></tr>`).join('')}</tbody>
          </table>
        </div>
      </div>
    </div>
  `;
  new Chart(reportChart, {
    type: ['product','category','customer','payment'].includes(type) ? 'pie' : 'bar',
    data: { labels: d.labels, datasets: [{ label: 'Amount', data: d.values, backgroundColor: ['#6366f1','#ec4899','#10b981','#f59e0b','#3b82f6','#8b5cf6','#ef4444','#14b8a6'] }] },
    options: { responsive: true, maintainAspectRatio: false }
  });
}

function exportCSV() {
  if (!lastReport) return;
  let csv = 'Label,Value\n';
  lastReport.labels.forEach((l, i) => csv += `"${l}",${lastReport.values[i]}\n`);
  const blob = new Blob([csv], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'report_' + new Date().toISOString().split('T')[0] + '.csv';
  a.click();
  toast('CSV downloaded');
}

// ============ ANALYTICS ============
async function renderAnalytics() {
  const d = await apiGet('dashboard.php');
  document.getElementById('pageContent').innerHTML = `
    <div class="row">
      <div class="card"><h3>📈 Monthly Growth</h3><div class="chart-container"><canvas id="growthChart"></canvas></div></div>
      <div class="card"><h3>💹 Profit Trend</h3><div class="chart-container"><canvas id="profitTrend"></canvas></div></div>
    </div>
    <div class="row">
      <div class="card"><h3>🔥 Top Selling</h3>
        <table><thead><tr><th>Product</th><th>Qty</th></tr></thead>
        <tbody>${d.best.map(b => `<tr><td>${b.name}</td><td><div style="background:linear-gradient(90deg,#6366f1,#ec4899);height:20px;width:${b.q * 20}px;border-radius:10px"></div> ${b.q}</td></tr>`).join('')}</tbody></table>
      </div>
      <div class="card"><h3>🐢 Slow Moving</h3>
        <table><thead><tr><th>Product</th><th>Qty</th></tr></thead>
        <tbody>${d.slow.map(b => `<tr><td>${b.name}</td><td><div style="background:linear-gradient(90deg,#f59e0b,#ef4444);height:20px;width:${Math.max(b.q * 20, 20)}px;border-radius:10px"></div> ${b.q}</td></tr>`).join('')}</tbody></table>
      </div>
    </div>
  `;
  new Chart(growthChart, {
    type: 'line',
    data: { labels: d.months, datasets: [{ label: 'Sales', data: d.sales_data, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.4 }] },
    options: { responsive: true, maintainAspectRatio: false }
  });
  new Chart(profitTrend, {
    type: 'bar',
    data: { labels: d.months, datasets: [{ label: 'Profit', data: d.profit_data, backgroundColor: '#10b981', borderRadius: 8 }] },
    options: { responsive: true, maintainAspectRatio: false }
  });
}

// ============ SETTINGS ============
async function renderSettings() {
  const d = await apiGet('settings.php');
  const s = d.data;
  document.getElementById('pageContent').innerHTML = `
    <div class="row">
      <div class="card">
        <h3>🏢 Business Info</h3>
        <form onsubmit="saveSettings(event)">
          <div class="form-group"><label>Business Name</label><input name="setting_business_name" value="${s.business_name || ''}"></div>
          <div class="form-group"><label>Address</label><textarea name="setting_business_address" rows="2">${s.business_address || ''}</textarea></div>
          <div class="form-group"><label>Phone</label><input name="setting_business_phone" value="${s.business_phone || ''}"></div>
          <div class="form-row">
            <div class="form-group"><label>Enable GST</label>
              <select name="setting_gst_enabled"><option value="0" ${s.gst_enabled === '0' ? 'selected' : ''}>No</option><option value="1" ${s.gst_enabled === '1' ? 'selected' : ''}>Yes</option></select>
            </div>
            <div class="form-group"><label>Default GST %</label><input type="number" name="setting_default_gst" value="${s.default_gst || 18}"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">💾 Save</button>
        </form>
      </div>
      <div class="card">
        <h3>🔐 Security</h3>
        <form onsubmit="changePassword(event)">
          <h4 style="margin:10px 0">Change Password</h4>
          <div class="form-group"><label>Old Password</label><input type="password" name="old" required></div>
          <div class="form-group"><label>New Password</label><input type="password" name="new" required></div>
          <button type="submit" class="btn btn-primary btn-block">Update Password</button>
        </form>
        <hr style="margin:20px 0">
        <form onsubmit="changePin(event)">
          <h4 style="margin:10px 0">Change PIN</h4>
          <div class="form-group"><label>Old PIN</label><input type="password" name="old" maxlength="6" required></div>
          <div class="form-group"><label>New PIN</label><input type="password" name="new" maxlength="6" required></div>
          <button type="submit" class="btn btn-primary btn-block">Update PIN</button>
        </form>
      </div>
    </div>
    <div class="card">
      <h3>💾 Backup & Restore</h3>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-success" onclick="backupDB()">📥 Backup Database</button>
        <label class="btn btn-warning" style="cursor:pointer">
          📤 Restore Database
          <input type="file" accept=".sql" style="display:none" onchange="restoreDB(this)">
        </label>
      </div>
    </div>
  `;
}

async function saveSettings(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'save');
  const d = await api('settings.php', { method: 'POST', body: fd });
  if (d.ok) toast('Settings saved');
  else toast(d.msg, 'error');
}

async function changePassword(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'change_password');
  const d = await api('auth.php', { method: 'POST', body: fd });
  if (d.ok) { toast('Password changed'); e.target.reset(); }
  else toast(d.msg, 'error');
}

async function changePin(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action', 'change_pin');
  const d = await api('auth.php', { method: 'POST', body: fd });
  if (d.ok) { toast('PIN changed'); e.target.reset(); }
  else toast(d.msg, 'error');
}

async function backupDB() {
  const d = await apiGet('settings.php?action=backup');
  if (d.ok) {
    window.open('../uploads/' + d.file, '_blank');
    toast('Backup downloaded');
  }
}

async function restoreDB(input) {
  if (!input.files[0]) return;
  if (!confirm('Restore will overwrite data. Continue?')) return;
  const fd = new FormData();
  fd.append('action', 'restore');
  fd.append('file', input.files[0]);
  const d = await api('settings.php', { method: 'POST', body: fd });
  if (d.ok) toast('Restored. Please re-login.');
  else toast(d.msg, 'error');
}
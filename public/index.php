<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BMS - Login</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="login-bg">
<div class="login-container">
  <div class="login-card">
    <div class="login-logo">🏢</div>
    <h1>Business Management</h1>
    <p class="subtitle">Sign in to continue</p>
    <form id="loginForm">
      <div class="input-group">
        <span class="input-icon">👤</span>
        <input type="text" id="username" placeholder="Username" required>
      </div>
      <div class="input-group">
        <span class="input-icon">🔒</span>
        <input type="password" id="password" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Login →</button>
      <div id="loginError" class="error-msg"></div>
    </form>
  </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData();
  fd.append('action', 'login');
  fd.append('username', document.getElementById('username').value);
  fd.append('password', document.getElementById('password').value);
  const r = await fetch('../api/auth.php', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.ok) window.location.href = 'app.html';
  else document.getElementById('loginError').textContent = d.msg || 'Login failed';
});
</script>
</body>
</html>
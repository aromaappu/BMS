const API = '../api/';

async function api(endpoint, options = {}) {
  const res = await fetch(API + endpoint, options);
  return await res.json();
}

async function apiGet(endpoint) {
  return await api(endpoint);
}

async function apiPost(endpoint, data) {
  const fd = new FormData();
  for (const k in data) fd.append(k, data[k]);
  return await api(endpoint, { method: 'POST', body: fd });
}

async function apiJson(endpoint, data) {
  return await api(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
}
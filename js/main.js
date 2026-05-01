console.log("Cyber Incident System: main.js initializing...");

function escapeHtml(str) {
  if (str === null || str === undefined) return "";
  return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;");
}

function fmtDate(d) {
  if (!d) return "N/A";
  try {
    const date = new Date(d);
    if (isNaN(date.getTime())) return d;
    return date.toLocaleString("en-US", { month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" });
  } catch(e) { return d; }
}

function cap(s) { 
  const val = s ? s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, " ") : "";
  return escapeHtml(val);
}
function badge(val, cls) { return `<span class="badge badge-${escapeHtml(cls || val)}">${cap(val)}</span>`; }

function ajaxPost(url, data) {
  console.log(`POSTing to ${url}`, data);
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  if (window.CSRF_TOKEN) fd.append("csrf_token", window.CSRF_TOKEN);
  return fetch(url, { method: "POST", body: fd })
    .then(r => r.json())
    .catch(e => { console.error("POST Error:", e); return {status:"error",message:"Network error"}; });
}

function ajaxGet(url, params = {}) {
  const qs = new URLSearchParams(params).toString();
  const fullUrl = qs ? `${url}?${qs}` : url;
  console.log(`GETting ${fullUrl}`);
  return fetch(fullUrl)
    .then(r => r.json())
    .catch(e => { console.error("GET Error:", e); return {status:"error", message: "Fetch failed"}; });
}

/* ══════════════════════════════════════════
   CORE LOADERS
══════════════════════════════════════════ */
function loadIncidents(containerId = "inc-list", status = "all", type = "all", severity = "all") {
  const el = document.getElementById(containerId);
  if (!el) return;
  
  ajaxGet("ajax/get_incidents.php", { status, type, severity })
    .then(d => {
      console.log("Incidents received:", d);
      if (d.status === "success") {
        if (!d.incidents || d.incidents.length === 0) {
          el.innerHTML = '<div class="empty-state">No incidents found.</div>';
          return;
        }
        el.innerHTML = d.incidents.map((inc, i) => `
          <div class="inc-row" style="animation-delay:${i * 0.05}s">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
              <span style="font-weight:700;font-family:var(--font-head);color:var(--text-primary);">#${inc.id} — ${escapeHtml(inc.title)}</span>
              <div style="display:flex;gap:6px;">${badge(inc.severity)}${badge(inc.status)}</div>
            </div>
            <div style="font-size:11px;color:var(--text-muted);display:flex;gap:12px;text-transform:uppercase;margin-bottom:10px;">
              <span>📁 ${cap(inc.incident_type)}</span>
              <span>👤 ${escapeHtml(inc.reporter_name)}</span>
              <span>🕐 ${fmtDate(inc.reported_at)}</span>
            </div>
            ${inc.ai_analysis ? `<div style="font-size:12px;color:#a78bfa;background:rgba(107,93,255,0.06);padding:10px;border-left:2px solid var(--neon-purple);margin-bottom:12px;border-radius:0 4px 4px 0;">🤖 ${escapeHtml(inc.ai_analysis)}</div>` : ''}
            <div style="display:flex;justify-content:space-between;align-items:center;">
               <div style="display:flex;align-items:center;gap:10px;">
                  <span style="font-size:10px;color:var(--text-muted);">WORKFLOW:</span>
                  <select class="status-sel" onchange="updateStatus(${inc.id}, this.value)">
                    <option value="open" ${inc.status==='open'?'selected':''}>Open</option>
                    <option value="investigating" ${inc.status==='investigating'?'selected':''}>Investigating</option>
                    <option value="resolved" ${inc.status==='resolved'?'selected':''}>Resolved</option>
                    <option value="closed" ${inc.status==='closed'?'selected':''}>Closed</option>
                  </select>
               </div>
               <button class="delete-btn btn-text-red" onclick="deleteIncident(${inc.id})" style="border:1px solid rgba(255,71,87,0.2) !important; padding:4px 10px; border-radius:4px; font-size: 11px;">🗑 Delete</button>
            </div>
          </div>
        `).join("");
      } else {
        el.innerHTML = `<div class="empty-state">Error: ${d.message || 'Unknown error'}</div>`;
      }
    })
    .catch(err => {
      console.error("loadIncidents Crash:", err);
      el.innerHTML = '<div class="empty-state">Critical script error.</div>';
    });
}

function loadUsers(containerId = "user-table") {
  const el = document.getElementById(containerId);
  if (!el) return;
  ajaxGet("ajax/get_users.php").then(d => {
    if (d.status === "success") {
      el.innerHTML = `
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
          <thead>
            <tr style="text-align:left; color:var(--text-muted); text-transform:uppercase; font-size:10px;">
              <th style="padding:10px; border-bottom:1px solid var(--border);">User</th>
              <th style="padding:10px; border-bottom:1px solid var(--border);">Email</th>
              <th style="padding:10px; border-bottom:1px solid var(--border);">Role</th>
              <th style="padding:10px; border-bottom:1px solid var(--border);">Actions</th>
            </tr>
          </thead>
          <tbody>
            ${d.users.map(u => `
              <tr>
                <td style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05);">${escapeHtml(u.name)}</td>
                <td style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05);">${escapeHtml(u.email)}</td>
                <td style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05);">${badge(u.role, u.role==='admin'?'critical':'resolved')}</td>
                <td style="padding:10px; border-bottom:1px solid rgba(255,255,255,0.05);">
                  ${u.role !== 'admin' ? `<button class="delete-btn btn-text-red" onclick="deleteUser(${u.id})" style="font-size:11px;">🗑 Delete</button>` : '<span style="font-size:10px; color:var(--text-muted)">(Admin)</span>'}
                </td>
              </tr>
            `).join("")}
          </tbody>
        </table>`;
    }
  });
}

function loadFeed(containerId = "feed-list", badgeId = "alert-badge") {
  const el = document.getElementById(containerId);
  if (!el) return;
  ajaxGet("ajax/get_alerts.php").then(d => {
    if (d.status === "success") {
      const bEl = document.getElementById(badgeId);
      if (bEl) { bEl.textContent = d.count; bEl.style.display = d.count > 0 ? "inline-flex" : "none"; }
      if (d.alerts.length === 0) { el.innerHTML = '<div class="empty-state">✅ All systems normal</div>'; return; }
      el.innerHTML = d.alerts.map(a => `
        <div class="feed-item ${a.severity}">
          <div style="display:flex; justify-content:space-between; font-size:10px; margin-bottom:4px;">
            <span style="font-weight:700;">${a.severity.toUpperCase()}</span>
            <span style="color:var(--text-muted);">${fmtDate(a.created_at)}</span>
          </div>
          <div style="font-size:12px; color:var(--text-secondary); line-height:1.4;">${escapeHtml(a.alert_message)}</div>
        </div>
      `).join("");
    }
  });
}

function updateStats() {
  ajaxGet("ajax/get_stats.php").then(d => {
    if (d.status === "success") {
      const map = { "stat-total": d.total, "stat-open": d.open, "stat-investigating": d.investigating, "stat-resolved": d.resolved, "stat-closed": d.closed, "stat-critical": d.critical, "stat-high": d.high };
      Object.entries(map).forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.textContent = val; });
      const dt = document.getElementById("dashboard-stat-total"); if (dt) dt.textContent = d.total;
    }
  });
}

function loadAll() {
  const s = window.cS || 'all', t = window.cT || 'all', sev = window.cSev || 'all';
  loadIncidents("inc-list", s, t, sev);
  updateStats();
  loadFeed();
}

/* ══════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════ */
function updateStatus(id, status) {
  ajaxPost("ajax/update_status.php", { incident_id: id, status: status })
    .then(d => { if (d.status === "success") loadAll(); else alert("Update failed: " + d.message); });
}

function deleteIncident(id) {
  if (!confirm("Confirm deletion of incident #" + id + "?")) return;
  ajaxPost("ajax/delete_incident.php", { incident_id: id })
    .then(d => { if (d.status === "success") loadAll(); else alert("Delete failed: " + d.message); });
}

function deleteUser(id) {
  if (!confirm("Confirm user deletion?")) return;
  ajaxPost("ajax/delete_user.php", { user_id: id })
    .then(d => { if (d.status === "success") loadUsers(); else alert("Delete failed: " + d.message); });
}

function activateFilter(btn) {
  document.querySelectorAll(".filter-pill, .stat-card").forEach(el => el.classList.remove("active"));
  if (btn) btn.classList.add("active");
}

function filterInc(status, type, btn, severity = "all") {
  window.cS = status; window.cT = type; window.cSev = severity;
  activateFilter(btn);
  loadIncidents("inc-list", status, type, severity);
}

function setSeverity(val) {
  document.querySelectorAll('.sev-btn').forEach(b => b.classList.remove('active'));
  const btn = document.querySelector(`.sev-btn[data-val="${val}"]`);
  if (btn) btn.classList.add('active');
  const hid = document.getElementById('severity-hidden');
  if (hid) hid.value = val;
}

function submitIncident() {
  const title = document.getElementById('title')?.value;
  const type = document.getElementById('incident_type')?.value;
  const desc = document.getElementById('description')?.value;
  const sev = document.getElementById('severity-hidden')?.value || 'medium';
  const msgEl = document.getElementById('form-msg');

  if (!title || !type || !desc) {
    if (msgEl) { msgEl.textContent = "Please fill all fields."; msgEl.className = "alert-msg error"; }
    return;
  }

  const btn = document.getElementById('submit-btn');
  if (btn) btn.disabled = true;

  ajaxPost("ajax/report_incident.php", { title, incident_type: type, description: desc, severity: sev })
    .then(d => {
      if (d.status === "success") {
        if (msgEl) { msgEl.textContent = "Incident reported successfully!"; msgEl.className = "alert-msg success"; }
        // Clear form
        document.getElementById('title').value = '';
        document.getElementById('incident_type').value = '';
        document.getElementById('description').value = '';
        setSeverity('medium');
        
        // Show AI result
        const aiBox = document.getElementById('ai-result-box');
        const aiText = document.getElementById('ai-result-text');
        if (aiBox && aiText) {
          aiText.textContent = d.ai_analysis;
          aiBox.style.display = 'block';
        }

        loadAll();
        // Trigger cross-tab sync
        localStorage.setItem('incidentUpdated', Date.now());
      } else {
        if (msgEl) { msgEl.textContent = "Error: " + d.message; msgEl.className = "alert-msg error"; }
      }
    })
    .finally(() => { if (btn) btn.disabled = false; });
}

function updateSidebarStats() {
  // Optional: could call updateStats or similar
}

function initRealTime() {
  if (!!window.EventSource) {
    console.log("SSE: Connecting...");
    const src = new EventSource("ajax/sse_updates.php");
    src.onmessage = (e) => {
      const d = JSON.parse(e.data);
      if (d.action === "refresh") { console.log("SSE: Refresh triggered"); loadAll(); loadUsers(); }
    };
    src.onerror = (e) => { console.warn("SSE Error - possible connection limit reached."); };
  }
}

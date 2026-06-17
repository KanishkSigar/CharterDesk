/* CharterDesk — dashboard logic */
const $ = s => document.querySelector(s);
let allThreads = [];
let currentFilter = 'all';
let currentSort = 'newest';

const STATE_PILL = {
  open:        { cls: 'pill-open',     label: 'Open' },
  negotiating: { cls: 'pill-pending',  label: 'Negotiating' },
  agreed:      { cls: 'pill-agreed',   label: 'Agreed' },
  archived:    { cls: 'pill-archived', label: 'Archived' },
};

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

function fmtDate(s){
  if(!s) return '—';
  const d = new Date(s.replace(' ', 'T'));
  return isNaN(d) ? s : d.toLocaleString(undefined, { dateStyle:'medium', timeStyle:'short' });
}

/* ---------- name persistence (shared key with workspace) ---------- */
(function initName(){
  const saved = localStorage.getItem('ime_name');
  if(saved) $('#who').value = saved;
  $('#who').addEventListener('input', ()=> localStorage.setItem('ime_name', $('#who').value.trim()));
})();

/* ---------- load + render ---------- */
function skeletons(n){ let s=''; for(let i=0;i<n;i++) s+='<div class="skeleton skel-card"></div>'; return s; }

async function load(){
  $('#negList').innerHTML = skeletons(3);
  try{
    const r = await fetch('dashboard_data.php');
    const j = await r.json();
    if(j.status !== 'success'){ $('#negList').innerHTML = `<div class="empty">Could not load: ${escapeHtml(j.message||'error')}</div>`; return; }
    allThreads = j.threads || [];
    renderTiles();
    renderList();
  }catch(e){
    $('#negList').innerHTML = `<div class="empty">Could not reach dashboard_data.php — is XAMPP/MySQL running?</div>`;
  }
}

function renderTiles(){
  $('#tTotal').textContent  = allThreads.length;
  $('#tNeg').textContent    = allThreads.filter(t=>t.state==='negotiating').length;
  $('#tAgreed').textContent = allThreads.filter(t=>t.state==='agreed').length;
}

function renderList(){
  const q = ($('#search').value || '').toLowerCase().trim();
  let rows = allThreads.filter(t =>
    (currentFilter === 'all' || t.state === currentFilter) &&
    (!q || (t.thread_uuid||'').toLowerCase().includes(q) || (t.created_by||'').toLowerCase().includes(q))
  );
  if(currentSort === 'oldest')      rows = rows.slice().reverse();        // backend is newest-first
  else if(currentSort === 'offers') rows = rows.slice().sort((a,b)=> b.offers - a.offers);

  const pane = $('#negList');
  if(rows.length === 0){
    pane.innerHTML = `<div class="empty">${allThreads.length ? 'No negotiations match your search.' : 'No negotiations yet — create one above.'}</div>`;
    return;
  }

  pane.innerHTML = rows.map(t=>{
    const pill = STATE_PILL[t.state] || STATE_PILL.open;
    const uuid = escapeHtml(t.thread_uuid);
    return `
      <div class="neg-card">
        <span class="pill ${pill.cls}">${pill.label}</span>
        <div class="neg-main">
          <div class="neg-title">${escapeHtml(t.title || 'Negotiation')}</div>
          <div class="sub"><span class="uuid">${uuid}</span> · by ${escapeHtml(t.created_by||'Unknown')} · ${fmtDate(t.created_at)}</div>
        </div>
        <div class="neg-meta">
          <div class="neg-stat"><div class="n">${t.offers}</div><div class="l">offers</div></div>
          <div class="neg-stat"><div class="n">v${t.latest_version}</div><div class="l">latest</div></div>
          <div class="neg-stat"><div class="n">${t.locked}</div><div class="l">locked</div></div>
          <div class="neg-actions">
            <button data-open="${uuid}">Open</button>
            <button class="btn-ghost" data-recap="${uuid}">Recap</button>
            <button class="btn-ghost" data-cp="${uuid}">CP PDF</button>
            ${t.state === 'archived'
              ? `<button class="btn-ghost" data-reopen="${uuid}">Reopen</button>`
              : `<button class="btn-ghost" data-archive="${uuid}">Archive</button>`}
            <button class="btn-ghost" data-del="${uuid}" title="Delete">🗑</button>
          </div>
        </div>
      </div>`;
  }).join('');
}

/* ---------- actions ---------- */
async function threadAction(uuid, action){
  const r = await fetch('thread_admin.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ thread_uuid: uuid, action })
  });
  const j = await r.json();
  if(j.status === 'success'){ toast(j.message, 'success'); load(); }
  else toast(j.message || 'Action failed', 'error');
}

$('#negList').addEventListener('click', (e)=>{
  const open  = e.target.closest('[data-open]');
  const recap = e.target.closest('[data-recap]');
  const cp    = e.target.closest('[data-cp]');
  const arch  = e.target.closest('[data-archive]');
  const reop  = e.target.closest('[data-reopen]');
  const del   = e.target.closest('[data-del]');
  if(open)  location.href = 'index.html?uuid=' + encodeURIComponent(open.dataset.open);
  if(recap) window.open('generate_recap.php?uuid=' + encodeURIComponent(recap.dataset.recap), '_blank');
  if(cp)    window.open('generate_cp.php?uuid=' + encodeURIComponent(cp.dataset.cp) + '&pdf=1', '_blank');
  if(arch)  threadAction(arch.dataset.archive, 'archive');
  if(reop)  threadAction(reop.dataset.reopen, 'reopen');
  if(del && confirm('Delete this negotiation and all its offers? This cannot be undone.')) threadAction(del.dataset.del, 'delete');
});

$('#btnNew').onclick = async ()=>{
  const me = ($('#who').value || '').trim();
  if(!me){ alert('Enter your name first.'); return; }
  localStorage.setItem('ime_name', me);
  const title = (prompt('Name this negotiation (e.g. Coal: Kandla to Qasim)', 'New fixture') || '').trim();
  if(title === null) return; // cancelled
  const fd = new FormData();
  fd.append('created_by', me);
  fd.append('title', title || 'Negotiation');
  const r = await fetch('create_thread.php', { method:'POST', body: fd });
  const j = await r.json();
  if(j.status === 'success'){
    location.href = 'index.html?uuid=' + encodeURIComponent(j.thread_uuid);
  } else {
    alert(j.message || 'Failed to create negotiation');
  }
};

$('#btnRefresh').onclick = load;
$('#search').addEventListener('input', renderList);
$('#sort').addEventListener('change', e => { currentSort = e.target.value; renderList(); });
$('#filterChips').addEventListener('click', e => {
  const b = e.target.closest('.fchip'); if(!b) return;
  document.querySelectorAll('.fchip').forEach(c => c.classList.remove('active'));
  b.classList.add('active');
  currentFilter = b.dataset.f;
  renderList();
});

load();

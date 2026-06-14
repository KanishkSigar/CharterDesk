/* ---------- STATE ---------- */
let threadUuid = null;
let offers = [];
let lockedFields = []; // stored in threads.locked_fields
let currentForm = {};
let counterFromOfferId = null;
let pollTimer = null;

/* ---------- FIELD IDS (40) ---------- */
const FIELD_IDS = [
  'role','vessel','gear','class_flag','built_year','last_cargoes','pni','sanctions',
  'cargo','qty','imo','load_port','dis_port','max_draft','open_book',
  'laycan_start','laycan_end','narrow',
  'freight','load_rate','dis_rate','load_rate_opt','demurrage','despatch','laytime',
  'cp_base','cp_custom','edit_riders','riders','add_clause',
  'contact','company','email','notes',
  'commission','nor_policy','holiday_exclusion','fp_clause','doc_window','agent_nomination','eta_notices'
];

const DEFAULT_RIDERS = [
  'Force Majeure',
  'LOI for discharge without B/L',
  'Stevedore Damage Clause',
  'Switch B/L & split B/L terms',
  'Hold readiness clause',
  'Sanctions compliance',
  'Arbitration: London, English law'
];

/* ---------- FIELD GROUPS (for grouped view + validation) ---------- */
const FIELD_GROUPS = [
  { title:'Role & Vessel',  fields:['role','vessel','gear','class_flag','built_year','last_cargoes','pni','sanctions'] },
  { title:'Cargo & Ports',  fields:['cargo','qty','imo','load_port','dis_port','max_draft','open_book'] },
  { title:'Laycan',         fields:['laycan_start','laycan_end','narrow'] },
  { title:'Rates & Laytime',fields:['freight','load_rate','dis_rate','load_rate_opt','demurrage','despatch','laytime'] },
  { title:'CP & Riders',    fields:['cp_base','cp_custom','edit_riders','riders','add_clause'] },
  { title:'Ops & Notices',  fields:['contact','company','email','notes'] },
  { title:'Other terms',    fields:['commission','nor_policy','holiday_exclusion','fp_clause','doc_window','agent_nomination','eta_notices'] },
];

/* Minimum terms required before an offer can be sent */
const REQUIRED = ['vessel','cargo','qty','load_port','dis_port','laycan_start','freight'];

function fieldLabel(fid){ return fid.replace(/_/g,' ').replace(/\b\w/g, c=>c.toUpperCase()); }

/* ---------- HELPERS ---------- */
const $ = s => document.querySelector(s);
function addBubble(html, me=false){
  const d = document.createElement('div');
  d.className = 'bubble' + (me?' me':'');
  d.innerHTML = html;
  $('#chat').appendChild(d);
  $('#chat').scrollTop = $('#chat').scrollHeight;
}
function setThreadInfo(){
  $('#threadInfo').textContent = threadUuid ? `Thread: ${threadUuid}` : 'No thread yet.';
}
function startPolling(){
  stopPolling();
  pollTimer = setInterval(async ()=>{
    if(!threadUuid) return;
    const beforeCnt = offers.length;
    await loadThread(true);
    if(offers.length !== beforeCnt){
      addBubble('🔔 New activity detected. Offers list updated.');
    }
  }, 4000);
}
function stopPolling(){ if(pollTimer){ clearInterval(pollTimer); pollTimer=null; } }

/* ---------- GREETING ---------- */
function greeting(){
  $('#chat').innerHTML='';
  addBubble(`<div style="font-weight:700;font-size:18px">Welcome to CharterDesk.</div>
  <div class="meta">Use the same Thread UUID in both browsers to negotiate live.</div>`);
  addBubble(`Enter your name (left), choose your role, then type <b>start</b>. You can also type <b>offer</b>, <b>counter</b>, <b>accept</b>, or <b>recap</b>.`);
}
greeting();

/* ---------- NAME PERSISTENCE ---------- */
(function initName(){
  const saved = localStorage.getItem('ime_name');
  if(saved){ $('#who').value = saved; }
  $('#who').addEventListener('input', ()=> {
    localStorage.setItem('ime_name', $('#who').value.trim());
  });
})();

/* ---------- RIDER CHIPS ---------- */
(function initRiders(){
  const box = $('#riderChips');
  DEFAULT_RIDERS.forEach(label=>{
    const span = document.createElement('span');
    span.className='chip'; span.textContent=label;
    span.onclick = ()=>{
      const cur = (currentForm.riders || '').split(' | ').filter(Boolean);
      if (cur.includes(label)) {
        currentForm.riders = cur.filter(x=>x!==label).join(' | ');
      } else {
        cur.push(label); currentForm.riders = cur.join(' | ');
      }
      span.style.opacity = cur.includes(label)? '1' : '.5';
    };
    box.appendChild(span);
  });
})();

/* ---------- NORMALIZERS (resilient to API shapes) ---------- */
function getOfferId(o){ return o.id ?? o.offer_id ?? o.offerID ?? o.ID ?? null; }
function getOfferVersion(o){ return o.version ?? o.ver ?? o.v ?? 0; }
function getOfferParty(o){ return o.party ?? o.created_by ?? o.sender ?? 'User'; }
function getOfferRole(o){ return o.role ?? o.party_role ?? o.side ?? 'Unknown'; }
function getOfferData(o){
  if (o.data && typeof o.data === 'string'){ try{ return JSON.parse(o.data); }catch(e){} }
  return o.data ?? o.data_json ?? o.payload ?? {};
}
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

/* ---------- OFFER FORM MODAL ---------- */
const modal = $('#modal'); const closeModal=()=> modal.style.display='none';
$('#x').onclick = closeModal;

function captureForm(){
  currentForm.role = $('#f_role').value;
  currentForm.vessel = $('#vessel').value;
  currentForm.gear = $('#gear').value;
  currentForm.class_flag = $('#class_flag').value;
  currentForm.built_year = $('#built_year').value;
  currentForm.last_cargoes = $('#last_cargoes').value;
  currentForm.pni = $('#pni').value;
  currentForm.sanctions = $('#sanctions').value;

  currentForm.cargo = $('#cargo').value;
  currentForm.qty = $('#qty').value;
  currentForm.imo = $('#imo').value;
  currentForm.load_port = $('#load_port').value;
  currentForm.dis_port = $('#dis_port').value;
  currentForm.max_draft = $('#max_draft').value;
  currentForm.open_book = $('#open_book').value;

  currentForm.laycan_start = $('#laycan_start').value;
  currentForm.laycan_end = $('#laycan_end').value;
  currentForm.narrow = $('#narrow').value;

  currentForm.freight = $('#freight').value;
  currentForm.load_rate = $('#load_rate').value;
  currentForm.dis_rate = $('#dis_rate').value;
  currentForm.load_rate_opt = $('#load_rate_opt').value;
  currentForm.demurrage = $('#demurrage').value;
  currentForm.despatch = $('#despatch').value;
  currentForm.laytime = $('#laytime').value;

  currentForm.cp_base = $('#cp_base').value;
  currentForm.cp_custom = $('#cp_custom').value;
  currentForm.edit_riders = $('#edit_riders').value;
  currentForm.add_clause = $('#add_clause').value;

  currentForm.contact = $('#contact').value;
  currentForm.company = $('#company').value;
  currentForm.email = $('#email').value;
  currentForm.notes = $('#notes').value;

  // sensible defaults for extra fields
  currentForm.commission = currentForm.commission || '2.5% to brokers';
  currentForm.nor_policy = currentForm.nor_policy || 'NOR valid whether in berth or not';
  currentForm.holiday_exclusion = currentForm.holiday_exclusion || 'Holidays excluded unless working';
  currentForm.fp_clause = currentForm.fp_clause || 'Free Pratique delays as per CP';
  currentForm.doc_window = currentForm.doc_window || 'Docs within 4/12 hrs as per port';
  currentForm.agent_nomination = currentForm.agent_nomination || 'Agent at Charterers cost / competitive DA';
  currentForm.eta_notices = currentForm.eta_notices || '5/4/3/2/1 day ETAs';
}

function openSectionFor(fid){
  const el = document.getElementById(fid);
  const det = el && el.closest('details');
  if(det) det.open = true;
}

function validateForm(){
  document.querySelectorAll('#modal .field-error').forEach(el=>el.classList.remove('field-error'));
  const missing = REQUIRED.filter(f => !String(currentForm[f] || '').trim());
  if(missing.length){
    missing.forEach(f=>{ const el = document.getElementById(f); if(el) el.classList.add('field-error'); });
    openSectionFor(missing[0]);
    const first = document.getElementById(missing[0]);
    if(first) first.scrollIntoView({behavior:'smooth', block:'center'});
    $('#formMsg').textContent = 'Please fill required terms: ' + missing.map(fieldLabel).join(', ');
    return false;
  }
  $('#formMsg').textContent = '';
  return true;
}

// Single validated submit: capture -> validate -> (if ok) close + send.
$('#useTerms').onclick = ()=>{
  captureForm();
  if(!validateForm()) return;
  closeModal();
  saveOffer();
};

// Clear the red error state as soon as the user edits a field.
$('#modal').addEventListener('input', e=>{
  if(e.target && e.target.classList) e.target.classList.remove('field-error');
});

/* ---------- VIEW OFFER MODAL (LOCK FIELDS) ---------- */
const viewModal = $('#viewModal'); const closeView=()=> viewModal.style.display='none';
$('#vx').onclick = closeView;

$('#btnLockFields').onclick = async ()=>{
  if(!threadUuid){ alert('No thread loaded.'); return; }
  const selected = [...document.querySelectorAll('.lockBox:checked')].map(cb=>cb.value);
  if(selected.length===0){ alert('Select fields to accept.'); return; }

  const r = await fetch('lock_fields.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ thread_uuid: threadUuid, fields: selected })
  });
  const j = await r.json();
  if(j.status==='success'){
    lockedFields = j.locked_fields || [];
    addBubble(`🔒 Accepted terms locked: ${selected.join(', ')}`);
    await loadThread();
    closeView();
  }else{
    alert(j.message||'Failed to lock fields');
  }
};

/* ---------- LEFT PANE: NEW/LOAD ---------- */
$('#btnNew').onclick = async ()=>{
  const me = ($('#who').value || '').trim();
  if(!me){ alert('Please enter your name (left) before creating a thread.'); return; }
  localStorage.setItem('ime_name', me);

  const fd = new FormData();
  fd.append('created_by', me);
  fd.append('title','Negotiation');
  const r = await fetch('create_thread.php',{method:'POST', body:fd});
  const j = await r.json();
  if(j.status==='success'){
    threadUuid = j.thread_uuid; offers = []; lockedFields = [];
    setThreadInfo(); $('#offersList').innerHTML=''; greeting(); startPolling();
    addBubble('🆕 Thread created. Share the UUID with counterparty to join.');
  }else alert(j.message||'Failed to create thread');
};

$('#btnLoad').onclick = async ()=>{
  const id = $('#uuidInput').value.trim();
  const me = ($('#who').value || '').trim();
  if(!me){ alert('Please enter your name (left) before loading.'); return; }
  localStorage.setItem('ime_name', me);
  if(!id){ alert('Enter a Thread UUID'); return; }
  threadUuid = id;
  await loadThread();
  greeting(); startPolling();
  addBubble('📥 Loaded thread. You’re synced. Type “offer” or use the buttons on the left.');
};

/* ---------- LOAD THREAD ---------- */
async function loadThread(silent=false){
  if(!threadUuid) return;
  try{
    const r = await fetch('get_thread.php?uuid='+encodeURIComponent(threadUuid));
    const j = await r.json();
    if(j.status!=='success'){ if(!silent) alert(j.message||'Load failed'); return; }
    offers = j.offers || [];
    lockedFields = j.locked_fields || [];
    setThreadInfo();
    renderOffers();
  }catch(e){
    if(!silent) console.error(e);
  }
}

/* ---------- RENDER OFFERS (robust) ---------- */
function renderOffers(){
  const pane = document.getElementById('offersList');
  pane.innerHTML = '';

  if (!Array.isArray(offers) || offers.length===0){
    pane.innerHTML = '<div class="muted">No offers yet.</div>';
    return;
  }

  offers.forEach(o=>{
    const id = getOfferId(o);
    const v  = getOfferVersion(o);
    const p  = getOfferParty(o);
    const r  = getOfferRole(o);

    const d = document.createElement('div');
    d.className='offer-item';
    d.innerHTML = `
      <div class="offer-head"><span class="ver">v${v}</span> by ${escapeHtml(p)} (${escapeHtml(r)})</div>
      <div class="offer-toolbar">
        <button data-action="see" data-id="${id}">View</button>
        <button class="btn-ghost" data-action="ctr" data-id="${id}">Counter</button>
        <button class="btn-accent" data-action="acc" data-id="${id}">Accept</button>
      </div>`;
    pane.appendChild(d);
  });
}

/* ---------- Delegated clicks on the offers list ---------- */
document.getElementById('offersList').addEventListener('click', (e)=>{
  const btn = e.target.closest('button[data-action]');
  if(!btn) return;
  const id = btn.dataset.id ? (isNaN(+btn.dataset.id) ? btn.dataset.id : +btn.dataset.id) : null;
  const action = btn.dataset.action;

  if (!id){ alert('Offer id missing from API.'); return; }

  if (action === 'see') openView(id);
  if (action === 'ctr') startCounter(id);
  if (action === 'acc') acceptOffer(id);
});

/* ---------- VIEW OFFER ---------- */
function openView(offer_id){
  const off = offers.find(o => String(getOfferId(o)) === String(offer_id));
  if(!off){ alert('Offer not found (id '+offer_id+'). Check API keys.'); return; }

  const v = getOfferVersion(off);
  const p = getOfferParty(off);
  const r = getOfferRole(off);
  const data = getOfferData(off);

  document.getElementById('viewTitle').textContent = `Offer v${v} by ${p} (${r})`;

  const body = document.getElementById('viewBody');
  body.innerHTML=''; body.setAttribute('data-offer-id', String(offer_id));

  FIELD_GROUPS.forEach(g=>{
    const det = document.createElement('details');
    det.open = true;
    const rows = g.fields.map(fid=>{
      const val = data[fid] ?? '';
      const locked = lockedFields.includes(fid);
      const valHtml = val ? escapeHtml(String(val)).replace(/\n/g,'<br>') : '<span class="muted">—</span>';
      const badge = locked ? ' <span class="lock-badge">locked</span>' : '';
      const attrs = locked ? 'checked disabled' : '';
      return `<div class="kv">
        <div class="k">${fieldLabel(fid)}${badge}</div>
        <div class="v">${valHtml}</div>
        <div><input type="checkbox" class="lockBox" value="${fid}" ${attrs}></div>
      </div>`;
    }).join('');
    det.innerHTML = `<summary>${g.title}</summary>${rows}`;
    body.appendChild(det);
  });

  document.getElementById('viewModal').style.display='flex';
}

/* ---------- VIEW MODAL: select all / clear unlocked checkboxes ---------- */
$('#btnSelectAll').onclick = ()=>
  document.querySelectorAll('#viewBody .lockBox:not(:disabled)').forEach(cb=> cb.checked = true);
$('#btnClearSel').onclick = ()=>
  document.querySelectorAll('#viewBody .lockBox:not(:disabled)').forEach(cb=> cb.checked = false);

/* ---------- COUNTER (disable locked fields) ---------- */
function startCounter(offer_id){
  const off = offers.find(o => String(getOfferId(o)) === String(offer_id));
  if(!off){ alert('Offer not found (id '+offer_id+').'); return; }

  counterFromOfferId = offer_id;
  currentForm = {...getOfferData(off)};
  resetModalInputs(); // sets values

  // disable locked
  setTimeout(()=>{
    const map = {
      vessel:'vessel',gear:'gear',class_flag:'class_flag',built_year:'built_year',last_cargoes:'last_cargoes',pni:'pni',sanctions:'sanctions',
      cargo:'cargo',qty:'qty',imo:'imo',load_port:'load_port',dis_port:'dis_port',max_draft:'max_draft',open_book:'open_book',
      laycan_start:'laycan_start',laycan_end:'laycan_end',narrow:'narrow',
      freight:'freight',load_rate:'load_rate',dis_rate:'dis_rate',load_rate_opt:'load_rate_opt',demurrage:'demurrage',despatch:'despatch',laytime:'laytime',
      cp_base:'cp_base',cp_custom:'cp_custom',edit_riders:'edit_riders',add_clause:'add_clause',
      contact:'contact',company:'company',email:'email',notes:'notes'
    };
    Object.entries(map).forEach(([fid,id])=>{
      const el = document.getElementById(id);
      if(!el) return;
      el.value = currentForm[fid] || '';
      if(lockedFields.includes(fid)){ el.disabled = true; el.style.opacity='.6'; }
    });

    const curR = (currentForm.riders || '').split(' | ').filter(Boolean);
    [...document.querySelectorAll('#riderChips .chip')].forEach(ch=>{
      ch.style.opacity = curR.includes(ch.textContent)? '1':'.5';
      if(lockedFields.includes('riders')){ ch.style.pointerEvents='none'; ch.style.opacity='.6'; }
    });
  }, 0);

  document.getElementById('modal').style.display='flex';
}

/* ---------- Reset modal inputs ---------- */
function resetModalInputs(){
  $('#f_role').value = currentForm.role || $('#role').value;
  const map = {
    vessel:'vessel',gear:'gear',class_flag:'class_flag',built_year:'built_year',last_cargoes:'last_cargoes',pni:'pni',sanctions:'sanctions',
    cargo:'cargo',qty:'qty',imo:'imo',load_port:'load_port',dis_port:'dis_port',max_draft:'max_draft',open_book:'open_book',
    laycan_start:'laycan_start',laycan_end:'laycan_end',narrow:'narrow',
    freight:'freight',load_rate:'load_rate',dis_rate:'dis_rate',load_rate_opt:'load_rate_opt',demurrage:'demurrage',despatch:'despatch',laytime:'laytime',
    cp_base:'cp_base',cp_custom:'cp_custom',edit_riders:'edit_riders',add_clause:'add_clause',
    contact:'contact',company:'company',email:'email',notes:'notes'
  };
  Object.entries(map).forEach(([fid,id])=>{
    const el = document.getElementById(id);
    if(!el) return;
    el.disabled = false; el.style.opacity='1';
    el.value = currentForm[fid] || '';
  });
  const curR = (currentForm.riders || '').split(' | ').filter(Boolean);
  [...document.querySelectorAll('#riderChips .chip')].forEach(ch=>{
    ch.style.pointerEvents='auto';
    ch.style.opacity = curR.includes(ch.textContent)? '1':'.5';
  });
}

/* ---------- CHAT & QUICK ACTIONS ---------- */
$('#send').onclick = handleSend;
$('#msg').addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); handleSend(); } });
$('#quickOffer').onclick = ()=> { counterFromOfferId=null; openOfferFormFresh(); };
$('#btnRecap').onclick = ()=> {
  if(!threadUuid){ alert('Load a thread first.'); return; }
  window.open('generate_recap.php?uuid='+encodeURIComponent(threadUuid),'_blank');
};
$('#btnRecapPDF').onclick = ()=> {
  if(!threadUuid){ alert('Load a thread first.'); return; }
  window.open('generate_recap.php?uuid='+encodeURIComponent(threadUuid)+'&pdf=1','_blank');
};
$('#btnCP').onclick = ()=> {
  if(!threadUuid){ alert('Load a thread first.'); return; }
  window.open('generate_cp.php?uuid='+encodeURIComponent(threadUuid),'_blank');
};
$('#btnCPPDF').onclick = ()=> {
  if(!threadUuid){ alert('Load a thread first.'); return; }
  window.open('generate_cp.php?uuid='+encodeURIComponent(threadUuid)+'&pdf=1','_blank');
};

function handleSend(){
  const txt = $('#msg').value.trim();
  if(!txt) return;
  addBubble(txt, true);
  $('#msg').value='';
  const low = txt.toLowerCase();
  if(low==='start'){
    addBubble('Click “New” to create a thread or “Load” to join one. Then type “offer” to open the form.');
  } else if(low==='offer'){
    if(!threadUuid){ addBubble('Create or load a thread first.', false); return; }
    counterFromOfferId = null;
    openOfferFormFresh();
  } else if(low==='counter'){
    if(!threadUuid){ addBubble('Load a thread first.', false); return; }
    addBubble('Pick an existing offer on the left and click “Counter”. Locked terms will be disabled.');
  } else if(low==='accept'){
    if(!threadUuid){ addBubble('Load a thread first.', false); return; }
    addBubble('Use the Accept button on the latest offer (left).');
  } else if(low==='recap'){
    if(!threadUuid){ addBubble('Load a thread first.', false); return; }
    window.open('generate_recap.php?uuid='+encodeURIComponent(threadUuid),'_blank');
  } else if(low==='cp'){
    if(!threadUuid){ addBubble('Load a thread first.', false); return; }
    window.open('generate_cp.php?uuid='+encodeURIComponent(threadUuid),'_blank');
  } else {
    addBubble('Try: start, offer, counter, accept, recap.');
  }
}

function openOfferFormFresh(){
  currentForm = { role: $('#role').value };
  resetModalInputs();
  modal.style.display='flex';
}

/* ---------- SAVE OFFER ---------- */
async function saveOffer(){
  if(!threadUuid){ alert('No thread'); return; }
  const me = ($('#who').value || '').trim() || 'User';
  localStorage.setItem('ime_name', me);

  const payload = {
    thread_uuid: threadUuid,
    party: me,
    role: $('#role').value,
    data: {...currentForm},
    riders: currentForm.add_clause || ''
  };
  const r = await fetch('save_offer.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  const j = await r.json();
  if(j.status==='success'){
    addBubble('📤 Offer sent.', true);
    await loadThread(true);
  } else alert(j.message||'Error saving offer');
}

/* ---------- ACCEPT OFFER ---------- */
async function acceptOffer(offer_id){
  const me = ($('#who').value || '').trim() || 'User';
  localStorage.setItem('ime_name', me);

  const r = await fetch('accept_offer.php',{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({offer_id, party: me})
  });
  const j = await r.json();
  addBubble(j.message || 'Accepted.');
  if(j.recap_url){ window.open(j.recap_url,'_blank'); }
  await loadThread(true);
}

/* ---------- AUTO-LOAD FROM ?uuid= (deep link from dashboard) ---------- */
(async function autoLoadFromUrl(){
  const uuid = new URLSearchParams(location.search).get('uuid');
  if(!uuid) return;
  $('#uuidInput').value = uuid;
  const me = ($('#who').value || '').trim();
  if(!me){
    addBubble('Enter your name (left), then click <b>Load</b> to open this negotiation.');
    return;
  }
  threadUuid = uuid;
  await loadThread();
  greeting(); startPolling();
  addBubble('📥 Opened negotiation from dashboard. You’re synced.');
})();

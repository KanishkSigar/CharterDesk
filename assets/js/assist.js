/* CharterDesk - AI Assist panel (explain / draft / review) */
(function () {
  const $ = s => document.querySelector(s);
  const modal = $('#assistModal');
  if (!modal) return;

  let mode = 'explain';

  const LABELS = {
    explain: { label: 'Term or clause to explain', ph: "e.g. NOR tendered WIBON, or 'Free Pratique'", needsInput: true },
    draft:   { label: 'Describe the clause to draft', ph: 'e.g. a force majeure clause covering port strikes and weather', needsInput: true },
    review:  { label: '', ph: '', needsInput: false },
  };

  function esc(s){ return String(s).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  // Build a terms summary from the latest offer (globals from app.js).
  function dealContext(){
    if (typeof offers === 'undefined' || !Array.isArray(offers) || !offers.length) return '';
    const d = (typeof getOfferData === 'function') ? getOfferData(offers[offers.length - 1]) : {};
    const keys = ['vessel','cargo','qty','load_port','dis_port','laycan_start','laycan_end',
      'freight','load_rate','dis_rate','demurrage','despatch','laytime','cp_base','riders','add_clause'];
    return keys.filter(k => d[k]).map(k => `${k.replace(/_/g,' ')}: ${d[k]}`).join('\n');
  }

  function applyMode(){
    document.querySelectorAll('.assist-mode').forEach(b => b.classList.toggle('active', b.dataset.mode === mode));
    const cfg = LABELS[mode];
    $('#assistInputWrap').style.display = cfg.needsInput ? '' : 'none';
    if (cfg.needsInput){ $('#assistLabel').textContent = cfg.label; $('#assistInput').placeholder = cfg.ph; }
    $('#assistOutput').className = 'assist-output muted';
    $('#assistOutput').textContent = mode === 'review'
      ? 'Reviews the terms of the latest offer in this thread.'
      : 'Results will appear here.';
  }

  $('#btnAssist').onclick = ()=>{ applyMode(); modal.style.display = 'flex'; };
  $('#assistClose').onclick = ()=> modal.style.display = 'none';
  document.querySelectorAll('.assist-mode').forEach(b => b.onclick = ()=>{ mode = b.dataset.mode; applyMode(); });

  $('#assistRun').onclick = async ()=>{
    const out = $('#assistOutput');
    const payload = { mode, input: ($('#assistInput').value || '').trim(), context: '' };
    if (mode === 'draft' || mode === 'review') payload.context = dealContext();
    if (mode === 'review' && !payload.context){ toast('No offer terms to review yet.', 'error'); return; }

    out.className = 'assist-output muted';
    out.textContent = 'Thinking…';
    try {
      const r = await fetch('assist.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const j = await r.json();
      if (j.status === 'success'){
        out.className = 'assist-output';
        out.innerHTML = esc(j.text).replace(/\n/g, '<br>');
      } else {
        out.className = 'assist-output muted';
        out.textContent = j.message || 'Assist failed.';
        toast(j.message || 'Assist failed.', 'error');
      }
    } catch (e) {
      out.className = 'assist-output muted';
      out.textContent = 'Could not reach assist.php - is the server running?';
    }
  };
})();

/* =====================================================================
 |  QR Studio — Client logic  (drag & drop + smart locked fields)
 |  FIX: token chip double-insert resolved (single delegated handler)
 * ===================================================================== */
(function () {
  'use strict';

  const API = 'api.php';
  let currentFields = [];
  let lastQrName    = 'qr-code';
  let lastPayload   = null;
  let lastRecordId  = null;
  let draggingRow   = null;

  /* ------------------------------ helpers ------------------------------ */
  const $  = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));

  function flash(el, type, text) {
    if (!el) return;
    const cls = type === 'error' ? 'alert-pink alert-error' : 'alert-pink';
    el.innerHTML = `<div class="alert ${cls} py-2 px-3 mb-0">
      <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
      ${escapeHtml(text)}</div>`;
    if (type !== 'error') setTimeout(() => { el.innerHTML = ''; }, 4000);
  }

  /* --------------------- parts that make sense per type ----------------- */
  const TOKEN_PARTS = {
    date:     ['', 'year', 'yy', 'month', 'mm', 'day', 'dd'],
    text:     ['', 'upper', 'lower', 'first', 'last', 'initials', 'length'],
    textarea: ['', 'upper', 'lower', 'first', 'last', 'initials', 'length'],
    email:    ['', 'upper', 'lower', 'first', 'last'],
    tel:      [''],
    number:   [''],
    select:   ['', 'upper', 'lower'],
    locked:   [''],
  };

  const PART_LABELS = {
    '':        'raw',
    year:      'year',
    yy:        'yy',
    month:     'month',
    mm:        'mm',
    day:       'day',
    dd:        'dd',
    upper:     'UPPER',
    lower:     'lower',
    first:     'first',
    last:      'last',
    initials:  'initials',
    length:    'length',
  };

  const TYPE_ICONS = {
    text:     'bi-fonts',
    textarea: 'bi-text-paragraph',
    number:   'bi-123',
    email:    'bi-envelope',
    tel:      'bi-telephone',
    date:     'bi-calendar3',
    select:   'bi-list-ul',
    locked:   'bi-lock-fill',
  };

  /* ==================== FIELD DEFINITIONS (builder) ==================== */
  async function fetchFields() {
    const res  = await fetch(`${API}?action=list_fields`);
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Failed to load fields.');
    currentFields = data.fields || [];
    renderDynamicForm();
    renderFieldList();
    renderFieldRefPalette();
  }

  function renderDynamicForm() {
    const wrap = $('#dynamicFields');
    if (!wrap) return;
    wrap.innerHTML = '';

    if (!currentFields.length) {
      wrap.innerHTML = `<div class="col-12"><div class="alert-pink py-3 px-3 mb-0">
        No fields defined yet — add some in the <b>Field Builder</b> tab.</div></div>`;
      return;
    }

    currentFields.forEach(f => {
      const id = 'f_' + f.field_key;
      let inner = '';

      if (f.field_type === 'textarea') {
        inner = `<textarea class="form-control" id="${id}" name="${f.field_key}"
                    placeholder="${escapeHtml(f.placeholder || '')}"
                    ${f.is_required ? 'required' : ''} rows="3" maxlength="500"></textarea>`;
      } else if (f.field_type === 'select') {
        const opts = (f.options || []).map(o =>
          `<option value="${escapeHtml(o)}">${escapeHtml(o)}</option>`).join('');
        inner = `<select class="form-select" id="${id}" name="${f.field_key}"
                    ${f.is_required ? 'required' : ''}>
                    <option value="">— Select —</option>${opts}</select>`;
      } else if (f.field_type === 'locked') {
        inner = `<input type="text" class="form-control" id="${id}" name="${f.field_key}"
                    value="" placeholder="Auto-generated on save"
                    disabled data-locked="1" data-format="${escapeHtml(f.auto_format || '')}">`;
      } else {
        const t = f.field_type === 'number' ? 'number' : f.field_type;
        inner = `<input type="${t}" class="form-control" id="${id}" name="${f.field_key}"
                    placeholder="${escapeHtml(f.placeholder || '')}"
                    ${f.is_required ? 'required' : ''} maxlength="200">`;
      }

      const colClass = f.field_type === 'textarea' ? 'col-12' : 'col-md-6';

      wrap.insertAdjacentHTML('beforeend', `
        <div class="${colClass}">
          <label class="form-label" for="${id}">${escapeHtml(f.label)}
            ${f.is_required ? '' : '<span class="badge-soft ms-1">optional</span>'}
          </label>
          ${inner}
          <div class="invalid-feedback">${escapeHtml(f.label)} is required.</div>
        </div>
      `);
    });
  }

  /* ============================ FIELD LIST ============================= */
  function renderFieldList() {
    const list = $('#fieldList');
    if (!list) return;

    if (!currentFields.length) {
      list.innerHTML = `<p style="color:var(--muted);" class="mb-0">No fields yet.</p>`;
      return;
    }

    list.innerHTML = currentFields.map(f => {
      const typeBadge = `<span class="badge-pink">${escapeHtml(f.field_type)}</span>`;
      const reqBadge  = f.is_required ? '' : '<span class="badge-soft">optional</span>';
      let extra = '';
      if (f.field_type === 'locked' && f.auto_format) {
        extra = `<div class="field-meta mt-1">
          <i class="bi bi-lock-fill"></i> format: <code>${escapeHtml(f.auto_format)}</code></div>`;
      } else if (f.field_type === 'select' && f.options && f.options.length) {
        extra = `<div class="field-meta mt-1">
          options: ${f.options.map(o => `<span class="badge-soft me-1">${escapeHtml(o)}</span>`).join('')}</div>`;
      }

      return `
      <div class="field-row" draggable="true" data-id="${f.id}">
        <div class="drag-handle" title="Drag to re-order">
          <i class="bi bi-grip-vertical"></i>
        </div>
        <div class="field-info">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span class="field-name">${escapeHtml(f.label)}</span>
            ${typeBadge} ${reqBadge}
          </div>
          <div class="field-meta">key: <code>${escapeHtml(f.field_key)}</code></div>
          ${extra}
        </div>
        <div class="field-actions">
          <button class="icon-btn" data-act="edit"   data-id="${f.id}" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="icon-btn danger" data-act="delete" data-id="${f.id}" title="Delete"><i class="bi bi-trash3"></i></button>
        </div>
      </div>`;
    }).join('');

    $$('#fieldList [data-act="edit"]').forEach(b => b.addEventListener('click', () => {
      const id = Number(b.dataset.id);
      const f = currentFields.find(x => Number(x.id) === id);
      if (f) openFieldEditor(f);
    }));
    $$('#fieldList [data-act="delete"]').forEach(b => b.addEventListener('click', async () => {
      const id = Number(b.dataset.id);
      if (!confirm('Delete this field? Existing records will keep their stored values.')) return;
      const fd = new FormData();
      fd.append('action', 'delete_field');
      fd.append('id', id);
      const res  = await fetch(API, { method:'POST', body: fd });
      const data = await res.json();
      if (data.ok) { await fetchFields(); }
      else alert(data.error || 'Delete failed.');
    }));

    wireDragAndDrop();
  }

  /* ========================= DRAG & DROP =============================== */
  function wireDragAndDrop() {
    const list = $('#fieldList');
    if (!list) return;

    list.querySelectorAll('.field-row').forEach(row => {
      row.addEventListener('dragstart', (e) => {
        draggingRow = row;
        row.classList.add('dragging');
        try { e.dataTransfer.effectAllowed = 'move'; } catch (_) {}
        try { e.dataTransfer.setData('text/plain', row.dataset.id); } catch (_) {}
      });
      row.addEventListener('dragend', async () => {
        if (!draggingRow) return;
        draggingRow.classList.remove('dragging');
        draggingRow = null;
        await persistOrder();
      });
    });

    if (!list.dataset.dndBound) {
      list.dataset.dndBound = '1';

      list.addEventListener('dragover', (e) => {
        if (!draggingRow) return;
        e.preventDefault();
        try { e.dataTransfer.dropEffect = 'move'; } catch (_) {}

        const after = getDragAfterElement(list, e.clientY);
        if (after == null) {
          list.appendChild(draggingRow);
        } else if (after !== draggingRow) {
          list.insertBefore(draggingRow, after);
        }
      });

      list.addEventListener('drop', (e) => {
        if (!draggingRow) return;
        e.preventDefault();
      });
    }
  }

  function getDragAfterElement(container, y) {
    const els = [...container.querySelectorAll('.field-row:not(.dragging)')];
    return els.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child };
      }
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  async function persistOrder() {
    const list = $('#fieldList');
    if (!list) return;
    const ids = Array.from(list.querySelectorAll('.field-row'))
      .map(r => Number(r.dataset.id))
      .filter(n => Number.isFinite(n) && n > 0);

    try {
      const fd = new FormData();
      fd.append('action', 'reorder_fields');
      fd.append('order', JSON.stringify(ids));
      const res  = await fetch(API, { method:'POST', body: fd });
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Failed to save order.');

      const byId = new Map(currentFields.map(f => [Number(f.id), f]));
      currentFields = ids.map(id => byId.get(id)).filter(Boolean);
      currentFields.forEach((f, i) => { f.sort_order = i + 1; });

      renderDynamicForm();
      renderFieldRefPalette();
    } catch (e) {
      alert(e.message || 'Could not save order.');
    }
  }

  /* ---------------------- field editor (right panel) ------------------- */
  function openFieldEditor(f) {
    $('#editorTitle').textContent = 'Edit Field';
    $('#field_id').value           = f ? f.id : '';
    $('#field_label').value        = f ? f.label : '';
    $('#field_key').value          = f ? f.field_key : '';
    $('#field_type').value         = f ? f.field_type : 'text';
    $('#field_placeholder').value  = f ? (f.placeholder || '') : '';
    $('#field_options').value      = f && f.options ? f.options.join('\n') : '';
    $('#field_autoformat').value   = f ? (f.auto_format || '') : '';
    $('#field_sort').value         = f ? f.sort_order : 0;
    $('#field_required').checked   = f ? !!f.is_required : true;
    toggleTypeExtras();
    renderFieldRefPalette();
    $('#fieldMsg').innerHTML = '';
  }

  function resetFieldEditor() {
    openFieldEditor(null);
    $('#editorTitle').textContent = 'Add Field';
  }

  function toggleTypeExtras() {
    const t = $('#field_type').value;
    $('#optsWrap').classList.toggle('d-none', t !== 'select');
    $('#lockWrap').classList.toggle('d-none', t !== 'locked');
  }

  $('#field_type')?.addEventListener('change', toggleTypeExtras);
  $('#newFieldBtn')?.addEventListener('click', resetFieldEditor);
  $('#cancelFieldBtn')?.addEventListener('click', resetFieldEditor);

  /* ------------- render field-reference palette in editor ------------- */
  function renderFieldRefPalette() {
    const palette = $('#fieldRefPalette');
    if (!palette) return;

    const editingId = Number($('#field_id')?.value || 0);
    const others = currentFields.filter(f => Number(f.id) !== editingId);

    if (!others.length) {
      palette.innerHTML = `<p class="mb-0" style="color:var(--muted);font-size:.8rem;">
        Add another field first — then you can reference its value here.
      </p>`;
      return;
    }

    palette.innerHTML = others.map(f => {
      const parts = TOKEN_PARTS[f.field_type] || [''];
      const chips = parts.map(p => {
        const token = p ? `{@${f.field_key}:${p}}` : `{@${f.field_key}}`;
        const label = PART_LABELS[p] || p;
        return `<button type="button" class="token-chip sm"
                        data-token="${escapeHtml(token)}"
                        title="${escapeHtml(token)}">${escapeHtml(label)}</button>`;
      }).join('');

      return `
        <div class="palette-field">
          <span class="palette-field-label">
            <i class="bi ${TYPE_ICONS[f.field_type] || 'bi-dot'}"></i>
            ${escapeHtml(f.label)}
          </span>
          <span class="palette-field-type">${escapeHtml(f.field_type)}</span>
          ${chips}
        </div>`;
    }).join('');

    /* NOTE:
     * No direct click listener is attached here on purpose.
     * The single delegated handler below (document-level) catches every
     * click on `.token-chip` inside #lockWrap, which prevents the token
     * from being inserted twice (the previous double-insert bug).
     */
  }

  /* -------- insert token at cursor in the auto-format input ---------- */
  function insertToken(text) {
    const input = $('#field_autoformat');
    if (!input || !text) return;
    const start = input.selectionStart ?? input.value.length;
    const end   = input.selectionEnd   ?? input.value.length;
    const val   = input.value;
    input.value = val.slice(0, start) + text + val.slice(end);
    const pos = start + text.length;
    input.setSelectionRange(pos, pos);
    input.focus();
  }

  /* ------- single delegated click handler for ALL token chips -------- */
  document.addEventListener('click', (e) => {
    const chip = e.target.closest('#lockWrap .token-chip');
    if (!chip) return;
    e.preventDefault();
    const token = chip.dataset.token || '';
    if (!token) return;
    insertToken(token);
  });

  /* ----------------------- save field ------------------------------- */
  $('#fieldForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'save_field');
    fd.append('id',           $('#field_id').value || '');
    fd.append('label',        $('#field_label').value.trim());
    fd.append('field_key',    $('#field_key').value.trim());
    fd.append('field_type',   $('#field_type').value);
    fd.append('placeholder',  $('#field_placeholder').value.trim());
    fd.append('options',      $('#field_options').value.trim());
    fd.append('auto_format',  $('#field_autoformat').value.trim());
    fd.append('sort_order',   $('#field_sort').value || '0');
    fd.append('is_required',  $('#field_required').checked ? '1' : '0');

    const res  = await fetch(API, { method:'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      flash($('#fieldMsg'), 'ok', data.message || 'Saved.');
      await fetchFields();
      resetFieldEditor();
    } else {
      flash($('#fieldMsg'), 'error', data.error || 'Save failed.');
    }
  });

  /* ============================== RECORDS ============================= */
  async function loadRecords() {
    const grid = $('#recordsGrid');
    grid.innerHTML = `<div class="col-12 text-center py-4">
      <div class="spinner-border spinner-pink" role="status" style="width:2rem;height:2rem;"></div></div>`;
    try {
      const res  = await fetch(`${API}?action=list_records`);
      const data = await res.json();
      renderRecords(data.records || []);
    } catch (err) {
      grid.innerHTML = `<div class="col-12"><div class="alert-pink py-3 px-3 mb-0">
        Could not load records.</div></div>`;
    }
  }

  function renderRecords(records) {
    const grid = $('#recordsGrid');
    if (!records.length) {
      grid.innerHTML = `<div class="col-12 text-center py-4" style="color:var(--muted);">
        <i class="bi bi-inbox" style="font-size:2rem;color:var(--pink);"></i>
        <p class="mt-3 mb-0">No records yet. Generate a QR and press “Save to MySQL &amp; /record”.</p></div>`;
      return;
    }
    grid.innerHTML = records.map(r => `
      <div class="col-md-6 col-xl-4">
        <div class="record-card h-100">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div style="min-width:0;">
              <div class="record-name">${escapeHtml(r.full_name)}</div>
              <div class="record-meta">ID #${r.id}${r.record_code ? ' · ' + escapeHtml(r.record_code) : ''}</div>
            </div>
            <div class="field-actions">
              <button class="icon-btn"        data-act="view"   data-id="${r.id}" title="View"><i class="bi bi-eye"></i></button>
              <button class="icon-btn danger" data-act="delete" data-id="${r.id}" title="Delete"><i class="bi bi-trash3"></i></button>
            </div>
          </div>
          <div class="record-meta mt-auto pt-3">
            <i class="bi bi-clock me-1"></i>${escapeHtml(r.created_at)}
          </div>
        </div>
      </div>`).join('');

    $$('#recordsGrid [data-act="view"]').forEach(b => b.addEventListener('click', () => openViewModal(Number(b.dataset.id))));
    $$('#recordsGrid [data-act="delete"]').forEach(b => b.addEventListener('click', () => deleteRecord(Number(b.dataset.id))));
  }

  async function deleteRecord(id) {
    if (!confirm('Delete this record and its QR image file?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_record');
    fd.append('id', id);
    const res  = await fetch(API, { method:'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { alert(data.error || 'Delete failed.'); return; }
    loadRecords();
  }

  async function openViewModal(id) {
    const modalEl = $('#viewModal');
    const body    = $('#viewModalBody');
    body.innerHTML = `<div class="text-center py-5">
      <div class="spinner-border spinner-pink" role="status" style="width:2rem;height:2rem;"></div></div>`;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    try {
      const res  = await fetch(`${API}?action=get_record&id=${id}`);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Failed.');
      const rec = data.record;
      const rows = Object.keys(rec.record_data || {}).map(k =>
        `<div class="result-row">
           <span class="result-key">${escapeHtml(k)}</span>
           <span class="result-val">${escapeHtml(rec.record_data[k])}</span>
         </div>`).join('');

      const qrBlock = rec.qr_image
        ? `<div class="view-qr"><img src="${escapeHtml(rec.qr_image)}?t=${Date.now()}" alt="QR"></div>`
        : `<div class="view-qr-missing"><i class="bi bi-image" style="font-size:2rem;"></i>
             <p class="mt-2 mb-0">No QR image stored for this record.</p></div>`;

      body.innerHTML = `
        <div class="view-grid">
          <div class="result-grid">${rows || '<p class="mb-0" style="color:var(--muted);">No data</p>'}</div>
          ${qrBlock}
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <span class="badge-pink"><i class="bi bi-hash me-1"></i>ID ${rec.id}</span>
          ${rec.record_code ? `<span class="badge-pink"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(rec.record_code)}</span>` : ''}
          <span class="badge-pink"><i class="bi bi-clock me-1"></i>${escapeHtml(rec.created_at)}</span>
        </div>`;
    } catch (e) {
      body.innerHTML = `<div class="alert-pink alert-error py-3 px-3 mb-0">
        ${escapeHtml(e.message)}</div>`;
    }
  }

  /* ============================ QR PREVIEW =========================== */
  function drawQR(text, filenameBase) {
    const holder = $('#qrHolder');
    const empty  = $('#qrEmpty');
    holder.querySelectorAll('canvas, img').forEach(n => n.remove());
    if (empty) empty.style.display = 'none';
    holder.classList.remove('empty');

    new QRCode(holder, {
      text: text,
      width: 512,
      height: 512,
      colorDark: '#160610',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });

    lastQrName = (filenameBase || 'qr-code').replace(/[^a-z0-9\-_]+/gi, '_')
      .replace(/^_+|_+$/g, '').toLowerCase() || 'qr-code';

    $('#downloadBtn').disabled = false;
    $('#saveBtn').disabled     = false;
    $('#saveMsg').innerHTML    = '';
  }

  function qrPngDataUrl(pad = 48) {
    const src = $('#qrHolder canvas');
    if (!src) return null;
    const out = document.createElement('canvas');
    out.width  = src.width + pad * 2;
    out.height = src.height + pad * 2;
    const ctx = out.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, out.width, out.height);
    ctx.drawImage(src, pad, pad);
    return out.toDataURL('image/png');
  }

  function collectValues() {
    const values = {};
    currentFields.forEach(f => {
      if (f.field_type === 'locked') return;
      const el = document.getElementById('f_' + f.field_key);
      values[f.field_key] = el ? el.value.trim() : '';
    });
    return values;
  }

  /* ============================ GENERATE ============================== */
  $('#qrForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    e.stopPropagation();
    const form = e.currentTarget;

    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      flash($('#formMsg'), 'error', 'Please fill in every required field.');
      return;
    }

    const values = collectValues();

    let previewValues = {};
    try {
      const fd = new FormData();
      fd.append('action', 'peek_next_id');
      fd.append('values', JSON.stringify(values));
      const r = await fetch(API, { method: 'POST', body: fd });
      const d = await r.json();
      if (d.ok) previewValues = d.preview_values || {};
    } catch (_) { /* silent */ }

    const payload = {};
    currentFields.forEach(f => {
      if (f.field_type === 'locked') {
        const val = previewValues[f.field_key]
                 || `[${f.auto_format || '{#####}'}]`;
        payload[f.label] = val;

        const el = document.getElementById('f_' + f.field_key);
        if (el) {
          el.value = val;
          el.classList.remove('is-locked-final');
          el.classList.add('is-preview');
        }
        return;
      }
      payload[f.label] = values[f.field_key] ?? '';
    });

    lastPayload  = payload;
    lastRecordId = null;

    drawQR(JSON.stringify(payload, null, 2),
           payload[findNameLabel()] || 'qr-code');

    flash($('#formMsg'), 'ok',
        'Preview ready. Locked fields show predicted values — real values are assigned when you save.');
  });

  function findNameLabel() {
    const f = currentFields.find(x => x.field_key === 'full_name')
           || currentFields.find(x => /name/i.test(x.field_key))
           || currentFields[0];
    return f ? f.label : null;
  }

  $('#resetBtn')?.addEventListener('click', () => {
    const form = $('#qrForm');
    form.classList.remove('was-validated');
    $('#formMsg').innerHTML = '';
    $('#saveMsg').innerHTML = '';
    const holder = $('#qrHolder');
    holder.querySelectorAll('canvas, img').forEach(n => n.remove());
    $('#qrEmpty').style.display = '';
    holder.classList.add('empty');
    $('#downloadBtn').disabled = true;
    $('#saveBtn').disabled     = true;
    lastRecordId = null;

    currentFields.forEach(f => {
      if (f.field_type === 'locked') {
        const el = document.getElementById('f_' + f.field_key);
        if (el) {
          el.value = '';
          el.classList.remove('is-preview', 'is-locked-final');
        }
      }
    });
  });

  /* ============================ DOWNLOAD ============================= */
  $('#downloadBtn')?.addEventListener('click', () => {
    const url = qrPngDataUrl(48);
    if (!url) return;
    const a = document.createElement('a');
    a.href = url;
    a.download = lastQrName + '.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
  });

  /* ============================== SAVE =============================== */
  $('#saveBtn')?.addEventListener('click', async () => {
    const btn  = $('#saveBtn');
    const msg  = $('#saveMsg');
    const form = $('#qrForm');

    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      flash(msg, 'error', 'Please fix the form before saving.');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Saving…`;

    try {
      const fd = new FormData();
      fd.append('action', 'save_record');
      currentFields.forEach(f => {
        if (f.field_type === 'locked') return;
        const el = document.getElementById('f_' + f.field_key);
        if (el) fd.append(f.field_key, el.value.trim());
      });

      const res1  = await fetch(API, { method:'POST', body: fd });
      const data1 = await res1.json();
      if (!data1.ok) throw new Error(data1.error || 'Save failed.');

      const rec = data1.record;
      lastRecordId = rec.id;

      const finalPayload = data1.payload;
      const qrJson = JSON.stringify(finalPayload, null, 2);

      drawQR(qrJson, rec.full_name || 'qr-code');

      await new Promise(r => setTimeout(r, 60));
      const dataUrl = qrPngDataUrl(48);
      if (dataUrl) {
        const fd2 = new FormData();
        fd2.append('action', 'upload_qr');
        fd2.append('record_id', String(rec.id));
        fd2.append('image', dataUrl);
        const res2  = await fetch(API, { method:'POST', body: fd2 });
        const data2 = await res2.json();
        if (!data2.ok) throw new Error(data2.error || 'QR upload failed.');
      }

      currentFields.forEach(f => {
        if (f.field_type === 'locked') {
          const el = document.getElementById('f_' + f.field_key);
          if (el) {
            el.value = finalPayload[f.label] || '';
            el.classList.remove('is-preview');
            el.classList.add('is-locked-final');
          }
        }
      });

      flash(msg, 'ok', `Saved as “${rec.full_name}” (ID #${rec.id}).`);
      loadRecords();
    } catch (err) {
      flash(msg, 'error', err.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-cloud-arrow-up me-2"></i>Save to MySQL &amp; /record`;
    }
  });

  /* ============================ QR READER ============================ */
  const dropZone  = $('#dropZone');
  const fileInput = $('#qrFile');
  const canvas    = $('#readerCanvas');
  const readRes   = $('#readResult');

  dropZone?.addEventListener('click', () => fileInput.click());
  ['dragenter','dragover'].forEach(ev => dropZone.addEventListener(ev, e => {
    e.preventDefault(); e.stopPropagation(); dropZone.classList.add('dragover');
  }));
  ['dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, e => {
    e.preventDefault(); e.stopPropagation(); dropZone.classList.remove('dragover');
  }));
  dropZone?.addEventListener('drop', e => {
    const f = e.dataTransfer?.files?.[0]; if (f) handleImage(f);
  });
  fileInput?.addEventListener('change', function() { if (this.files[0]) handleImage(this.files[0]); });
  $('#clearReaderBtn')?.addEventListener('click', () => {
    fileInput.value = '';
    readRes.innerHTML = `<p style="color:var(--muted);" class="mb-0">
      <i class="bi bi-info-circle me-2"></i>Upload a QR image to see the decoded content here.</p>`;
  });

  function handleImage(file) {
    if (!file.type || !file.type.startsWith('image/')) {
      return renderError('That file is not an image.');
    }
    readRes.innerHTML = `<div class="text-center py-4">
      <div class="spinner-border spinner-pink" role="status" style="width:2rem;height:2rem;"></div>
      <p class="mt-3 mb-0" style="color:var(--muted);font-size:.9rem;">Scanning image…</p></div>`;

    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = function () {
      try {
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        let w = img.naturalWidth, h = img.naturalHeight;
        const MAX = 1500;
        if (Math.max(w, h) > MAX) {
          const s = MAX / Math.max(w, h); w = Math.round(w * s); h = Math.round(h * s);
        }
        canvas.width = w; canvas.height = h;
        ctx.clearRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        const imageData = ctx.getImageData(0, 0, w, h);
        const code = jsQR(imageData.data, w, h, { inversionAttempts: 'attemptBoth' });
        if (code && code.data) renderDecoded(code.data, file.name);
        else renderError('No QR code detected. Try a clearer image.');
      } catch (e) {
        renderError('Could not process the image.');
      } finally {
        URL.revokeObjectURL(url);
      }
    };
    img.onerror = () => { URL.revokeObjectURL(url); renderError('Could not open image.'); };
    img.src = url;
  }

  function renderDecoded(text, fileName) {
    let parsed = null;
    try { parsed = JSON.parse(text); } catch (_) {}

    let body = '';
    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
      body = '<div class="result-grid">' + Object.keys(parsed).map(k =>
        `<div class="result-row">
           <span class="result-key">${escapeHtml(k)}</span>
           <span class="result-val">${escapeHtml(String(parsed[k]))}</span>
         </div>`).join('') + '</div>';
    } else {
      body = `<pre class="result-raw">${escapeHtml(text)}</pre>`;
    }

    readRes.innerHTML = `
      <div class="result-head"><i class="bi bi-patch-check-fill"></i>QR code decoded successfully</div>
      ${body}
      <div class="d-flex flex-wrap gap-2 mt-3">
        <button class="btn btn-ghost btn-sm px-3" id="copyResult">
          <i class="bi bi-clipboard me-2"></i>Copy Raw Data</button>
        <span class="badge-pink align-self-center">${escapeHtml(fileName || 'image')}</span>
      </div>`;

    $('#copyResult')?.addEventListener('click', function () {
      navigator.clipboard.writeText(text).then(() => {
        this.innerHTML = `<i class="bi bi-check2 me-2"></i>Copied!`;
        setTimeout(() => this.innerHTML = `<i class="bi bi-clipboard me-2"></i>Copy Raw Data`, 1800);
      });
    });
  }

  function renderError(msg) {
    readRes.innerHTML = `
      <div class="result-head error"><i class="bi bi-x-octagon-fill"></i>Scan failed</div>
      <p class="mb-0" style="color:var(--muted);font-size:.92rem;">${escapeHtml(msg)}</p>`;
  }

  /* ============================== BOOT =============================== */
  $('#refreshRecords')?.addEventListener('click', loadRecords);

  (async function init() {
    try { await fetchFields(); } catch (e) { console.error(e); }
    loadRecords();
  })();

})();
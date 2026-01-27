(function () {
  'use strict';

  // -----------------------
  // Meta + config
  // -----------------------
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const meta = (name) => $(`meta[name="${name}"]`);

  const csrf = () => meta('csrf-token')?.getAttribute('content') || '';

  const baseUrl = () => {
    let b = meta('base-url')?.getAttribute('content') || '';
    if (b.length > 1 && b.endsWith('/')) b = b.slice(0, -1);
    return b;
  };

  const escapeHtml = (s) =>
    String(s ?? '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));

  // -----------------------
  // HTTP helpers
  // -----------------------
  async function requestJson(url, { method = 'POST', headers = {}, body } = {}) {
    const res = await fetch(baseUrl() + url, {
      method,
      headers: {
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'fetch',
        ...headers
      },
      body
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || ('Erreur HTTP ' + res.status));
    }
    return data;
  }

  const postJson = (url, obj) =>
    requestJson(url, {
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(obj || {})
    });

  const postForm = (url, formData) =>
    requestJson(url, { body: formData });

  // -----------------------
  // AJAX forms (data-ajax="1")
  // -----------------------
  function bindAjaxForms(rootEl) {
    $$('form[data-ajax="1"]', rootEl).forEach((form) => {
      if (form.__ajaxBound) return;
      form.__ajaxBound = true;

      form.addEventListener('submit', async (ev) => {
        ev.preventDefault();

        const action = form.getAttribute('action') || '';
        if (!action) return;

        try {
          const relative = action.startsWith(baseUrl()) ? action.slice(baseUrl().length) : action;
          const data = await postForm(relative, new FormData(form));

          if (data.refresh) window.location.reload();
          else console.log('OK');
        } catch (err) {
          alert('Erreur: ' + (err.message || err));
        }
      });
    });
  }

  bindAjaxForms(document);

  // -----------------------
  // Modal Manager (stack stores URL + size)
  // -----------------------
  window.ModalManager = (function () {
    let bsModal = null;
    let current = null;      // { url, size }
    const stack = [];        // [{ url, size }, ...]

    async function open(url, opts = {}) {
      const modalEl = document.getElementById('appModal');
      const contentEl = document.getElementById('appModalContent');
      if (!modalEl || !contentEl) throw new Error('ModalHost #appModal introuvable');

      const dialog = modalEl.querySelector('.modal-dialog');
      if (!dialog) throw new Error('Modal dialog introuvable');

      const size = opts.size || 'modal-lg';

      // Size
      dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
      dialog.classList.add(size);

      // Bootstrap instance
      bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

      // History: push previous modal (url+size) if already open
      if (current) stack.push(current);
      current = { url, size };

      // Skeleton
      contentEl.innerHTML = `
        <div class="modal-header">
          <h5 class="modal-title">Chargement...</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body"><div class="text-muted">Veuillez patienter.</div></div>
      `;
      bsModal.show();

      const fullUrl = url.startsWith('http') ? url : (baseUrl() + url);
      const res = await fetch(fullUrl, { headers: { 'X-Requested-With': 'fetch' } });
      const html = await res.text();

      if (!res.ok) throw new Error('Erreur chargement modal (' + res.status + ')');

      contentEl.innerHTML = html;

      // Bind ajax forms inside modal
      bindAjaxForms(contentEl);
    }

    function canBack() {
      return stack.length > 0;
    }

    async function back() {
      if (!canBack()) return;

      const prev = stack.pop();
      current = null; // avoid re-push in open()

      await open(prev.url, { size: prev.size });
    }

    function close() {
      const modalEl = document.getElementById('appModal');
      if (!modalEl) return;

      const inst = bootstrap.Modal.getInstance(modalEl) || bsModal;
      if (inst) inst.hide();

      stack.length = 0;
      current = null;
    }

    // If modal is closed manually, reset state (keeps behavior consistent)
    (function bindHiddenResetOnce() {
      const modalEl = document.getElementById('appModal');
      if (!modalEl || modalEl.__mmBound) return;
      modalEl.__mmBound = true;

      modalEl.addEventListener('hidden.bs.modal', () => {
        stack.length = 0;
        current = null;
      });
    })();

    return { open, close, back, canBack };
  })();

  // -----------------------
  // Global [data-modal] handler (one)
  // -----------------------
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-modal]');
    if (!el) return;

    const t = e.target;
    if (t && /^(INPUT|BUTTON|SELECT|TEXTAREA|LABEL)$/.test(t.tagName)) return;

    e.preventDefault();

    const url = el.getAttribute('data-modal');
    if (!url) return;

    const size = el.getAttribute('data-modal-size') || 'modal-lg';
    window.ModalManager.open(url, { size }).catch((err) => alert('Modal error: ' + err.message));
  });

  // -----------------------
  // AppClasse actions
  // -----------------------
  window.AppClasse = {
    async markAbsence(payload) {
      try {
        await postJson('/api/seances/absence', payload);
        console.log('OK: absence mise à jour');
      } catch (e) {
        alert('Erreur: ' + e.message);
      }
    },

    async attachPartie(payload) {
      try {
        await postJson('/api/seances/partie', payload);
        console.log('OK: partie attachée');
      } catch (e) {
        alert('Erreur: ' + e.message);
      }
    },

    updateObservation(payload) {
      return postJson('/api/seances/observation', payload);
    },

    detachPartie(payload) {
      return postJson('/api/seances/partie/delete', payload);
    },

    updatePoints(payload) {
      return postJson('/api/points/update', payload);
    }
  };

  window.AppClassePoints = {
    async bump(idseance, ideleve, delta) {
      try {
        const data = await window.AppClasse.updatePoints({ idseance, ideleve, delta });
        const el = $('#pts-' + ideleve);
        if (el) el.textContent = data.points;
      } catch (e) {
        console.error(e);
        alert(e.message);
      }
    }
  };

  // -----------------------
  // Timetable helpers
  // -----------------------
  const isoDate = (d) => d.toISOString().slice(0, 10);
  const weekdayNow = () => {
    const x = new Date().getDay();
    return x === 0 ? 7 : x; // 1..7
  };

  function nextDateForWeekday(target) {
    const d = new Date();
    let diff = target - weekdayNow();
    if (diff < 0) diff += 7;
    const out = new Date(d);
    out.setDate(d.getDate() + diff);
    return isoDate(out);
  }

  function collectDaySessions(weekday) {
    const sessions = [];
    $$(`td[data-weekday="${weekday}"] .class-click`).forEach((a) => {
      const tr = a.closest('tr');
      const heured = tr?.dataset?.heured;
      if (!heured) return;

      sessions.push({
        classe_id: parseInt(a.dataset.classeId, 10),
        classe_label: (a.textContent || '').trim(),
        heured
      });
    });

    const m = new Map();
    sessions.forEach((s) => m.set(`${s.classe_id}|${s.heured}`, s));
    return Array.from(m.values());
  }

  function openNewSeanceModal(idclasse, date, heured) {
    const url = `/modals/seances/new?idclasse=${idclasse}&date=${encodeURIComponent(date)}&heured=${encodeURIComponent(heured)}`;
    window.ModalManager.open(url, { size: 'modal-lg' }).catch((err) => alert('Modal error: ' + err.message));
  }

  // -----------------------
  // Bulk confirmation modal
  // -----------------------
  const Bulk = (function () {
    let bs = null;
    let payload = null;

    function ensureHost() {
      const el = $('#bulkSeancesModal');
      if (!el) return null;
      bs = bootstrap.Modal.getOrCreateInstance(el);
      return el;
    }

    async function confirmCreate(date, sessions) {
      const data = await postJson('/api/seances/create-bulk', {
        date,
        sessions: sessions.map((s) => ({ classe_id: s.classe_id, heured: s.heured }))
      });

      const host = $('#bulkSeancesModal');
      if (host) {
        const rows = $$('#bulkRows tr', host);
        (data.results || []).forEach((r, i) => {
          const td = rows[i]?.children?.[2];
          if (!td) return;

          if (r.created) {
            td.textContent = 'Créée';
            td.className = 'text-success';
          } else {
            td.textContent = (r.reason === 'exists') ? 'Déjà existante' : 'Ignorée';
            td.className = 'text-warning';
          }
        });
      }

      if (data.refresh) window.location.reload();
      return data;
    }

    function show(date, sessions) {
      const host = ensureHost();
      if (!host) {
        if (!confirm(`Créer ${sessions.length} séance(s) pour le ${date} ?`)) return;
        return confirmCreate(date, sessions);
      }

      payload = { date, sessions };
      $('#bulkDate', host).textContent = date;

      const tbody = $('#bulkRows', host);
      tbody.innerHTML = '';
      sessions.forEach((s) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHtml(s.classe_label)}</td><td>${escapeHtml(s.heured)}</td><td class="text-muted">—</td>`;
        tbody.appendChild(tr);
      });

      bs.show();
    }

    function bind() {
      const host = $('#bulkSeancesModal');
      if (!host) return;

      const btn = $('#bulkConfirm', host);
      if (!btn || btn.__bound) return;
      btn.__bound = true;

      btn.addEventListener('click', async () => {
        if (!payload?.sessions?.length) return;

        btn.disabled = true;
        try {
          await confirmCreate(payload.date, payload.sessions);
        } catch (err) {
          alert('Erreur: ' + (err.message || err));
        } finally {
          btn.disabled = false;
        }
      });
    }

    return { show, bind };
  })();

  Bulk.bind();

  // Click class => "Nouvelle séance"
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.class-click');
    if (!a) return;

    e.preventDefault();

    const tr = a.closest('tr');
    const heured = tr?.dataset?.heured || '';
    const idclasse = parseInt(a.dataset.classeId, 10);
    if (!idclasse || !heured) return;

    openNewSeanceModal(idclasse, isoDate(new Date()), heured);
  });

  // Click day header => bulk
  document.addEventListener('click', (e) => {
    const th = e.target.closest('.day-header');
    if (!th) return;

    const weekday = parseInt(th.dataset.weekday, 10);
    if (!weekday) return;

    const date = nextDateForWeekday(weekday);
    const sessions = collectDaySessions(weekday);

    if (!sessions.length) {
      alert('Aucune classe trouvée pour ce jour dans l’emploi du temps.');
      return;
    }

    Bulk.show(date, sessions);
  });

  // -----------------------
  // Tags create/save (close if opened from page, back if opened from another modal)
  // -----------------------
  document.addEventListener('click', async (e) => {
    const btnCreate = e.target.closest('#btnTagCreate');
    if (btnCreate) {
      e.preventDefault();

      const labelEl = $('#tagNewLabel');
      const colorEl = $('#tagNewColor');
      const err = $('#tagErr');
      const ok = $('#tagOk');

      const tag = (labelEl?.value || '').trim();
      const color = (colorEl?.value || 'secondary').trim();

      err.classList.add('d-none');
      ok.classList.add('d-none');

      if (!tag) {
        err.textContent = 'Le tag est vide.';
        err.classList.remove('d-none');
        return;
      }

      const data = await postJson('/api/tags/create', { tag, color }).catch((ex) => {
        err.textContent = ex.message || 'Erreur lors de la création du tag.';
        err.classList.remove('d-none');
        return null;
      });
      if (!data || !data.ok) return;

      const list = $('#tagsList');
      if (list) {
        const id = Number(data.id);
        const label = document.createElement('label');
        label.className = 'd-flex align-items-center gap-2';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'form-check-input js-tag-check';
        input.value = String(id);
        input.checked = true;

        const badge = document.createElement('span');
        badge.className = `badge text-bg-${data.color || 'secondary'}`;
        badge.textContent = data.tag; // safe via textContent

        label.appendChild(input);
        label.appendChild(badge);
        list.appendChild(label);
      }

      if (labelEl) labelEl.value = '';
      ok.textContent = 'Tag créé.';
      ok.classList.remove('d-none');
      return;
    }

    const btnSave = e.target.closest('#btnTagSave');
    if (btnSave) {
      e.preventDefault();

      const ideleve = Number(btnSave.dataset.ideleve);
      const err = $('#tagErr');
      const ok = $('#tagOk');

      err.classList.add('d-none');
      ok.classList.add('d-none');

      const tagIds = $$('.js-tag-check:checked')
        .map((x) => Number(x.value))
        .filter(Number.isFinite);

      const data = await postJson('/api/eleves/tags', { ideleve, tags: tagIds }).catch((ex) => {
        err.textContent = ex.message || 'Erreur lors de l’enregistrement.';
        err.classList.remove('d-none');
        return null;
      });
      if (!data || !data.ok) return;

      ok.textContent = 'Tags enregistrés.';
      ok.classList.remove('d-none');

      // Required behavior:
      // - if opened from another modal => go back (reload previous modal with its original size)
      // - if opened from a page => close modal
      setTimeout(() => {
        if (window.ModalManager && window.ModalManager.canBack()) {
          window.ModalManager.back().catch(() => window.ModalManager.close());
        } else if (window.ModalManager) {
          window.ModalManager.close();
        }
      }, 150);
    }
  });

  document.addEventListener('input', (e) => {
    const tr = e.target.closest('tr[data-idmodule]');
    if (!tr) return;

    const c = tr.querySelector('.js-nb-cours');
    const x = tr.querySelector('.js-nb-exo');
    const t = tr.querySelector('.js-nb-total');
    if (!c || !x || !t) return;

    const cours = Number(c.value || 0);
    const exo = Number(x.value || 0);
    t.textContent = (cours + exo).toFixed(1);
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#btnNotebookSave');
    if (!btn) return;

    e.preventDefault();

    const err = document.getElementById('nbErr');
    const ok = document.getElementById('nbOk');
    err?.classList.add('d-none');
    ok?.classList.add('d-none');

    const idacademicrecords = Number(btn.dataset.idacademic);

    const items = [...document.querySelectorAll('tr[data-idmodule]')].map(tr => {
      const idmodule = Number(tr.dataset.idmodule);
      const cours = Number(tr.querySelector('.js-nb-cours')?.value || 0);
      const exercices = Number(tr.querySelector('.js-nb-exo')?.value || 0);
      return { idmodule, cours, exercices };
    });

    try {
      await postJson('/api/eleves/notebook/update', { idacademicrecords, items });
      if (ok) { ok.textContent = 'Enregistré.'; ok.classList.remove('d-none'); }
    } catch (ex) {
      if (err) { err.textContent = ex.message || 'Erreur'; err.classList.remove('d-none'); }
    }
  });

})();
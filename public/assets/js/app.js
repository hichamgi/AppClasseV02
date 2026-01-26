(function () {
  'use strict';

  // -----------------------
  // Meta helpers
  // -----------------------
  function meta(name) {
    return document.querySelector(`meta[name="${name}"]`);
  }

  function csrf() {
    const m = meta('csrf-token');
    return m ? (m.getAttribute('content') || '') : '';
  }

  function baseUrl() {
    const m = meta('base-url');
    let b = m ? (m.getAttribute('content') || '') : '';
    if (b.length > 1 && b.endsWith('/')) b = b.slice(0, -1);
    return b;
  }

  // -----------------------
  // HTTP helpers
  // -----------------------
  async function postJson(url, body) {
    const res = await fetch(baseUrl() + url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'fetch'
      },
      body: JSON.stringify(body || {})
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      const msg = data.error || ('Erreur HTTP ' + res.status);
      throw new Error(msg);
    }
    return data;
  }

  async function postForm(url, formData) {
    const res = await fetch(baseUrl() + url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'fetch'
      },
      body: formData
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      const msg = data.error || ('Erreur HTTP ' + res.status);
      throw new Error(msg);
    }
    return data;
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
  }

  // -----------------------
  // AJAX forms (data-ajax="1") => POST + JSON response
  // -----------------------
  function bindAjaxForms(rootEl) {
    rootEl.querySelectorAll('form[data-ajax="1"]').forEach(form => {
      if (form.__ajaxBound) return;
      form.__ajaxBound = true;

      form.addEventListener('submit', async (ev) => {
        ev.preventDefault();

        const action = form.getAttribute('action') || '';
        if (!action) return;

        const fd = new FormData(form);

        try {
          const relative = action.startsWith(baseUrl()) ? action.slice(baseUrl().length) : action;
          const data = await postForm(relative, fd);

          if (data.refresh) {
            window.location.reload();
            return;
          }

          // optionnel: tu peux afficher un toast ici plus tard
          console.log('OK');
        } catch (err) {
          alert('Erreur: ' + (err.message || err));
        }
      });
    });
  }

  // Bind initial page (in case there are ajax forms outside modal)
  bindAjaxForms(document);

  // -----------------------
  // Modal Manager (HTML modals)
  // -----------------------
  window.ModalManager = (function () {
    let bsModal = null;

    async function open(url, opts = {}) {
      const modalEl = document.getElementById('appModal');
      const contentEl = document.getElementById('appModalContent');
      if (!modalEl || !contentEl) throw new Error('ModalHost #appModal introuvable');

      const dialog = modalEl.querySelector('.modal-dialog');
      if (!dialog) throw new Error('Modal dialog introuvable');

      // Taille
      dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
      dialog.classList.add(opts.size || 'modal-lg');

      // Instance bootstrap
      bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

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
      const res = await fetch(fullUrl, {
        headers: { 'X-Requested-With': 'fetch' }
      });
      const html = await res.text();

      if (!res.ok) {
        throw new Error('Erreur chargement modal (' + res.status + ')');
      }

      contentEl.innerHTML = html;

      // Auto-bind AJAX forms inside modal
      bindAjaxForms(contentEl);
    }

    return { open };
  })();

  // -----------------------
  // Global [data-modal] handler (ONE handler only)
  // -----------------------
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-modal]');
    if (!el) return;

    // Ne pas ouvrir de modal si on clique sur des contrôles
    const t = e.target;
    if (t && (t.tagName === 'INPUT' || t.tagName === 'BUTTON' || t.tagName === 'SELECT' || t.tagName === 'TEXTAREA' || t.tagName === 'LABEL')) {
      return;
    }

    e.preventDefault();

    const url = el.getAttribute('data-modal');
    if (!url) return;

    const size = el.getAttribute('data-modal-size') || 'modal-lg';

    window.ModalManager.open(url, { size })
      .catch(err => alert('Modal error: ' + err.message));
  });

  // -----------------------
  // Existing AppClasse actions
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

    async updateObservation(payload) {
      return await postJson('/api/seances/observation', payload);
    },

    async detachPartie(payload) {
      return await postJson('/api/seances/partie/delete', payload);
    },

    async updatePoints(payload) {
      return await postJson('/api/points/update', payload);
    }
  };

  // -----------------------
  // AppClassePoints helper
  // -----------------------
  window.AppClassePoints = {
    async bump(idseance, ideleve, delta) {
      try {
        const data = await window.AppClasse.updatePoints({ idseance, ideleve, delta });
        const el = document.getElementById('pts-' + ideleve);
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
  function isoDate(d) { return d.toISOString().slice(0, 10); }
  function weekdayNow() { const x = (new Date()).getDay(); return x === 0 ? 7 : x; } // 1..7

  function nextDateForWeekday(target) {
    const d = new Date();
    const nowW = weekdayNow();
    let diff = target - nowW;
    if (diff < 0) diff += 7;
    const out = new Date(d);
    out.setDate(d.getDate() + diff);
    return isoDate(out);
  }

  function collectDaySessions(weekday) {
    const sessions = [];
    document.querySelectorAll(`td[data-weekday="${weekday}"] .class-click`).forEach(a => {
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
    sessions.forEach(s => m.set(`${s.classe_id}|${s.heured}`, s));
    return Array.from(m.values());
  }

  function openNewSeanceModal(idclasse, date, heured) {
    const url = `/modals/seances/new?idclasse=${idclasse}&date=${encodeURIComponent(date)}&heured=${encodeURIComponent(heured)}`;
    window.ModalManager.open(url, { size: 'modal-lg' })
      .catch(err => alert('Modal error: ' + err.message));
  }

  // -----------------------
  // Bulk confirmation modal (optional host)
  // -----------------------
  const Bulk = (function () {
    let bs = null;
    let payload = null;

    function ensureHost() {
      const el = document.getElementById('bulkSeancesModal');
      if (!el) return null;
      bs = bootstrap.Modal.getOrCreateInstance(el);
      return el;
    }

    async function confirmCreate(date, sessions) {
      const data = await postJson('/api/seances/create-bulk', {
        date,
        sessions: sessions.map(s => ({ classe_id: s.classe_id, heured: s.heured }))
      });

      const host = document.getElementById('bulkSeancesModal');
      if (host) {
        const rows = host.querySelectorAll('#bulkRows tr');
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
        const ok = confirm(`Créer ${sessions.length} séance(s) pour le ${date} ?`);
        if (!ok) return;
        return confirmCreate(date, sessions);
      }

      payload = { date, sessions };

      host.querySelector('#bulkDate').textContent = date;

      const tbody = host.querySelector('#bulkRows');
      tbody.innerHTML = '';
      sessions.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${escapeHtml(s.classe_label)}</td><td>${escapeHtml(s.heured)}</td><td class="text-muted">—</td>`;
        tbody.appendChild(tr);
      });

      bs.show();
    }

    function bind() {
      const host = document.getElementById('bulkSeancesModal');
      if (!host) return;

      const btn = host.querySelector('#bulkConfirm');
      if (!btn || btn.__bound) return;
      btn.__bound = true;

      btn.addEventListener('click', async () => {
        if (!payload || !payload.sessions?.length) return;

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

  // Click class => open "Nouvelle séance"
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.class-click');
    if (!a) return;

    e.preventDefault();

    const tr = a.closest('tr');
    const heured = tr?.dataset?.heured || '';
    const idclasse = parseInt(a.dataset.classeId, 10);
    if (!idclasse || !heured) return;

    const date = isoDate(new Date());
    openNewSeanceModal(idclasse, date, heured);
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

  document.addEventListener('click', async (e) => {
    const btnCreate = e.target.closest('#btnTagCreate');
    if (btnCreate) {
      e.preventDefault();

      const labelEl = document.getElementById('tagNewLabel');
      const colorEl = document.getElementById('tagNewColor');
      const err = document.getElementById('tagErr');
      const ok = document.getElementById('tagOk');

      const tag = (labelEl?.value || '').trim();
      const color = (colorEl?.value || 'secondary').trim();

      err.classList.add('d-none');
      ok.classList.add('d-none');

      if (!tag) {
        err.textContent = 'Le tag est vide.';
        err.classList.remove('d-none');
        return;
      }

      const res = await fetch(baseUrl() + '/api/tags/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf(),
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({ tag, color })
      });


      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        err.textContent = data.error || 'Erreur lors de la création du tag.';
        err.classList.remove('d-none');
        return;
      }

      // Ajouter dans la liste + cocher
      const list = document.getElementById('tagsList');
      if (list) {
        const id = Number(data.id);
        const safeTag = data.tag;     // affiché en textContent (safe)
        const safeColor = data.color;

        const label = document.createElement('label');
        label.className = 'd-flex align-items-center gap-2';

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'form-check-input js-tag-check';
        input.value = String(id);
        input.checked = true;

        const badge = document.createElement('span');
        badge.className = `badge text-bg-${safeColor || 'secondary'}`;
        badge.textContent = safeTag;

        label.appendChild(input);
        label.appendChild(badge);
        list.appendChild(label);
      }

      labelEl.value = '';
      ok.textContent = 'Tag créé.';
      ok.classList.remove('d-none');
      return;
    }

    const btnSave = e.target.closest('#btnTagSave');
    if (btnSave) {
      e.preventDefault();

      const ideleve = Number(btnSave.dataset.ideleve);
      const err = document.getElementById('tagErr');
      const ok = document.getElementById('tagOk');

      err.classList.add('d-none');
      ok.classList.add('d-none');

      const tagIds = [...document.querySelectorAll('.js-tag-check:checked')]
        .map(x => Number(x.value))
        .filter(Number.isFinite);

      const res = await fetch(baseUrl() + '/api/eleves/tags', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf(),
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({ ideleve, tags: tagIds })
      });


      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        err.textContent = data.error || 'Erreur lors de l’enregistrement.';
        err.classList.remove('d-none');
        return;
      }

      ok.textContent = 'Tags enregistrés.';
      ok.classList.remove('d-none');

      // Option: fermer la modal après 500ms
      // ModalManager.close();
    }
  });
})();
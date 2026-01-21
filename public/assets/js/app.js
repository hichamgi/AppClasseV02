(function () {
  function csrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function baseUrl() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '';
  }


  async function postJson(url, body) {
    const res = await fetch(baseUrl() + url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf()
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

  window.AppClasse = {
    async markAbsence(payload) {
      try {
        await postJson('/api/seances/absence', payload);
        alert('OK: absence mise à jour');
      } catch (e) {
        alert('Erreur: ' + e.message);
      }
    },

    async attachPartie(payload) {
      try {
        await postJson('/api/seances/partie', payload);
        alert('OK: partie attachée');
      } catch (e) {
        alert('Erreur: ' + e.message);
      }
    }
  };
})();

document.addEventListener('click', (e) => {
  const el = e.target.closest('[data-modal]');
  if (!el) return;

  e.preventDefault();

  const url = el.getAttribute('data-modal');
  const size = el.getAttribute('data-modal-size') || 'modal-lg';

  if (!url) return;

  window.ModalManager.open(url, { size })
    .catch(err => alert('Modal error: ' + err.message));
});

window.ModalManager = (function () {
  let bsModal = null;

  function baseUrl() {
    const meta = document.querySelector('meta[name="base-url"]');
    let b = meta ? (meta.getAttribute('content') || '') : '';
    if (b.length > 1 && b.endsWith('/')) b = b.slice(0, -1);
    return b;
  }

  async function open(url, opts = {}) {
    const modalEl = document.getElementById('appModal');
    const contentEl = document.getElementById('appModalContent');
    if (!modalEl || !contentEl) throw new Error('ModalHost #appModal introuvable');

    // taille
    const dialog = modalEl.querySelector('.modal-dialog');
    dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
    dialog.classList.add(opts.size || 'modal-lg');

    if (!bsModal) bsModal = new bootstrap.Modal(modalEl);

    contentEl.innerHTML = `
      <div class="modal-header">
        <h5 class="modal-title">Chargement...</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body"><div class="text-muted">Veuillez patienter.</div></div>
    `;
    bsModal.show();

    // IMPORTANT: url doit être relative (ex: "/modals/.."), baseUrl() ajoute /AppClasseV02
    const res = await fetch(baseUrl() + url, { headers: { 'X-Requested-With': 'fetch' } });
    const html = await res.text();

    if (!res.ok) throw new Error('Erreur chargement modal (' + res.status + '): ' + html.slice(0, 120));
    contentEl.innerHTML = html;
  }

  return { open };
})();

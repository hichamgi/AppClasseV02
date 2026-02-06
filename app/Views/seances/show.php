<?php

use App\Helpers\DateHelper;
?>
<h1 class="h4 mb-3">
  Séance — <?= $this->e($seance['classe']) ?>
  <span class="text-muted">
    (<?= $this->e(DateHelper::toHuman($seance['date'])) ?>, <?= $this->e($seance['heured']) ?>)
  </span>
</h1>

<div class="row g-3">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header fw-semibold">Absences</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead>
            <tr>
              <th style="width:70px;">N°</th>
              <th>Nom (FR)</th>
              <th>الاسم (AR)</th>
              <th style="width:90px;" colspan="3">Points</th>
              <th></th>
              <th style="width:110px;">Absent</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($eleves as $e): ?>
              <?php $prevAbsent = ((int)($e['prev_absent'] ?? 0) === 1); ?>

              <tr class="<?= $prevAbsent ? 'table-danger' : '' ?>">
                <td><?= (int)$e['numero'] ?></td>

                <!-- Nom FR cliquable => modal -->
                <td>
                  <a href="#"
                    class="text-decoration-none fw-semibold js-modal"
                    data-modal="/modals/eleves/show?ideleve=<?= (int)$e['id'] ?>&idclasse=<?= (int)$seance['idclasse'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal-size="modal-xl"
                    onclick="event.preventDefault();">
                    <?= $this->e($e['nom'] . ' ' . $e['prenom']) ?>
                  </a>
                </td>

                <!-- Nom AR cliquable => modal -->
                <td dir="rtl">
                  <a href="#"
                    class="text-decoration-none fw-semibold js-modal"
                    data-modal="/modals/eleves/show?ideleve=<?= (int)$e['id'] ?>&idclasse=<?= (int)$seance['idclasse'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal-size="modal-xl"
                    onclick="event.preventDefault();">
                    <?= $this->e(($e['nomar'] ?? '') . ' ' . ($e['prenomar'] ?? '')) ?>
                  </a>
                </td>

                <!-- Points -->
                <td class="text-nowrap">
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, -1)">
                    <i class="bi bi-dash"></i>
                  </button>
                </td>
                <td class="text-nowrap">
                  <span id="pts-<?= (int)$e['id'] ?>" class="mx-2 fw-semibold">
                    <?= (int)$e['points'] ?>
                  </span>
                </td>
                <td class="text-nowrap">
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, +1)">
                    <i class="bi bi-plus"></i>
                  </button>
                </td>

                <td>
                  <a class="btn btn-sm btn-outline-secondary"
                    href="/modals/eleves/tags?ideleve=<?= (int)$e['id'] ?>"
                    data-modal='/modals/eleves/tags?ideleve=<?= (int)$e['id'] ?>'
                    data-size="modal-lg">
                    Tags
                  </a>
                  <a class="btn btn-sm btn-outline-secondary"
                    href="/modals/eleves/notebook?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal="/modals/eleves/notebook?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-size="modal-lg">
                    Cahier
                  </a>
                  <!-- Absences année cliquable => modal (NE PAS stopPropagation ici) -->
                  <a href="#"
                    class="ms-2 js-modal small text-decoration-none"
                    data-modal="/modals/absences/list?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal-size="modal-lg"
                    onclick="event.preventDefault();">
                    Abs: <?= (int)($e['abs_year'] ?? 0) ?>
                  </a>
                </td>

                <!-- Absence checkbox -->
                <td class="text-nowrap">
                  <input type="checkbox"
                    class="form-check-input js-abs"
                    data-idseance="<?= (int)$seance['id'] ?>"
                    data-ideleve="<?= (int)$e['id'] ?>"
                    <?= ((int)$e['absent'] === 1) ? 'checked' : '' ?>>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="small text-muted mb-2">
          Les élèves en <span class="badge text-bg-danger">rouge</span> étaient absents à la séance précédente.
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-12">
    <div class="card">
      <div class="card-header fw-semibold">Avancement (parties)</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead>
            <tr>
              <th>Partie</th>
              <th style="width:120px;">Dernière date</th>
              <th style="width:80px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($parties as $p): ?>
              <?php
              $niv    = (int)($p['niv'] ?? 0);
              $done   = !empty($p['last_date']);
              $linked = ((int)$p['linked_to_current'] === 1);
              $devoir = (int)($p['devoir'] ?? 0);
              ?>
              <tr
                class="js-partie-row <?= $linked ? 'table-success' : ($devoir === 1 ? 'table-danger' : '') ?>"
                data-idpartie="<?= (int)$p['id'] ?>"
                data-idmodule="<?= (int)$p['idmodule'] ?>"
                data-linked="<?= $linked ? 1 : 0 ?>"
                data-devoir="<?= (int)$devoir ?>"
                data-niv="<?= (int)$niv ?>">
                <?php if ($niv > 1): ?>
                  <td><?= $this->e(($p['num'] ? $p['num'] . ' - ' : '') . $p['partie']) ?></td>
                  <td><?= $done ? $this->e($p['last_date']) : '<span class="text-muted">—</span>' ?></td>
                  <td class="text-end">
                    <?php if ($linked): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger js-del-partie"
                        data-idseance="<?= (int)$seance['id'] ?>"
                        data-idpartie="<?= (int)$p['id'] ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    <?php else: ?>
                      <button type="button" class="btn btn-sm btn-outline-primary js-add-partie"
                        data-idseance="<?= (int)$seance['id'] ?>"
                        data-idpartie="<?= (int)$p['id'] ?>">
                        <i class="bi bi-plus-lg"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                <?php else: ?>
                  <td colspan="3">
                    <?php if ($devoir === 0): ?>
                      <div class="text-muted"><?= $this->e($p['abrev'] . ' - ' . $p['module']) ?></div>
                    <?php endif; ?>
                    <?= $this->e($p['partie']) ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<br>

<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-3">
      <div>
        <div class="fw-semibold">Observation</div>
        <div id="obsText" class="<?= empty($seance['observation']) ? 'text-muted' : '' ?>">
          <?= empty($seance['observation']) ? '—' : $this->e($seance['observation']) ?>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-secondary" id="btnEditObs">Modifier</button>
    </div>

    <div class="mt-3 d-none" id="obsEdit">
      <textarea class="form-control" id="obsInput" rows="3"><?= $this->e((string)($seance['observation'] ?? '')) ?></textarea>
      <div class="mt-2 d-flex gap-2">
        <button class="btn btn-sm btn-primary" id="btnSaveObs">Enregistrer</button>
        <button class="btn btn-sm btn-outline-secondary" id="btnCancelObs">Annuler</button>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    'use strict';

    // -----------------------------
    // Helpers
    // -----------------------------
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    const toInt = (v, def = 0) => {
      const n = parseInt(v, 10);
      return Number.isFinite(n) ? n : def;
    };

    const setBusy = (el, busy = true) => {
      if (!el) return;
      el.disabled = !!busy;
      el.dataset.busy = busy ? '1' : '0';
    };

    const isBusy = (el) => el?.dataset?.busy === '1';

    const showError = (e, fallback = 'Erreur') => {
      const msg = (e && e.message) ? e.message : fallback;
      alert('Erreur: ' + msg);
    };

    // -----------------------------
    // Contexte séance
    // -----------------------------
    const idSeance = <?= (int)$seance['id'] ?>;
    const anchorKey = `ac.seances.${idSeance}.anchorPartie`;

    const clearAnchor = () => localStorage.removeItem(anchorKey);
    const setAnchor = (idpartie) => localStorage.setItem(anchorKey, String(idpartie));
    const getAnchor = () => toInt(localStorage.getItem(anchorKey) || '0');

    // -----------------------------
    // Absences: délégation change
    // -----------------------------
    document.addEventListener('change', async (ev) => {
      const cb = ev.target.closest('.js-abs');
      if (!cb) return;

      const payload = {
        idseance: toInt(cb.dataset.idseance),
        ideleve: toInt(cb.dataset.ideleve),
        absent: cb.checked ? 1 : 0
      };

      setBusy(cb, true);
      try {
        await window.AppClasse.markAbsence(payload);
      } catch (e) {
        cb.checked = !cb.checked; // revert
        showError(e);
      } finally {
        setBusy(cb, false);
      }
    });

    // -----------------------------
    // Observation UI
    // -----------------------------
    const btnEdit = $('#btnEditObs');
    const btnSave = $('#btnSaveObs');
    const btnCancel = $('#btnCancelObs');
    const obsText = $('#obsText');
    const obsEdit = $('#obsEdit');
    const obsInput = $('#obsInput');

    const openObsEditor = () => {
      if (!obsEdit) return;
      obsEdit.classList.remove('d-none');
      if (obsInput && obsText) {
        const current = (obsText.textContent || '').trim();
        obsInput.value = current === '—' ? '' : current;
        obsInput.focus();
      }
    };

    const closeObsEditor = () => {
      if (!obsEdit) return;
      obsEdit.classList.add('d-none');
    };

    btnEdit?.addEventListener('click', openObsEditor);
    btnCancel?.addEventListener('click', closeObsEditor);

    btnSave?.addEventListener('click', async () => {
      if (!btnSave || !obsInput || !obsText) return;
      if (isBusy(btnSave)) return;

      setBusy(btnSave, true);
      try {
        const val = (obsInput.value || '').trim();

        await window.AppClasse.updateObservation({
          idseance: idSeance,
          observation: val
        });

        obsText.textContent = val !== '' ? val : '—';
        obsText.classList.toggle('text-muted', val === '');
        closeObsEditor();
      } catch (e) {
        showError(e);
      } finally {
        setBusy(btnSave, false);
      }
    });

    // -----------------------------
    // Parties: ordre DOM (déjà trié SQL)
    // -----------------------------
    const getRowsOrdered = () => $$('tr.js-partie-row');
    const findIndexByPartieId = (rows, idpartie) =>
      rows.findIndex(r => toInt(r.dataset.idpartie) === idpartie);

    const getRowByPartieId = (rows, idpartie) =>
      rows.find(r => toInt(r.dataset.idpartie) === idpartie) || null;

    const isLinked = (row) => toInt(row?.dataset?.linked || '0') === 1;
    const isDevoir = (row) => toInt(row?.dataset?.devoir || '0') === 1;
    const isNiv1 = (row) => toInt(row?.dataset?.niv || '0') === 1;

    // sélectionnable dans le "range" uniquement
    const isSelectableForRange = (row) => row && !isDevoir(row) && !isNiv1(row);

    const attachOne = async (idseance, idpartie) => {
      await window.AppClasse.attachPartie({
        idseance,
        idpartie
      });
    };

    const detachOne = async (idseance, idpartie) => {
      await window.AppClasse.detachPartie({
        idseance,
        idpartie
      });
    };

    const lockAllPartieButtons = (locked) => {
      $$('.js-add-partie, .js-del-partie').forEach(b => setBusy(b, locked));
    };

    // -----------------------------
    // Click handler: Add/Del parties (délégation)
    // -----------------------------
    document.addEventListener('click', async (ev) => {
      const addBtn = ev.target.closest('.js-add-partie');
      const delBtn = ev.target.closest('.js-del-partie');

      // ---- DELETE PARTIE ----
      if (delBtn) {
        ev.preventDefault();
        if (isBusy(delBtn)) return;

        const idseance = toInt(delBtn.dataset.idseance, idSeance);

        let idpartie = toInt(delBtn.dataset.idpartie, 0);
        if (!idpartie) {
          const tr = delBtn.closest('tr');
          if (tr) idpartie = toInt(tr.dataset.idpartie, 0);
        }
        if (!idpartie) {
          alert("Suppression impossible: idpartie manquant.");
          return;
        }

        setBusy(delBtn, true);
        try {
          await detachOne(idseance, idpartie);

          // (1) Fix: si l’ancre était cette partie, on l’efface
          if (getAnchor() === idpartie) clearAnchor();

          window.location.reload();
        } catch (e) {
          showError(e);
          setBusy(delBtn, false);
        }
        return;
      }

      // ---- ADD PARTIE ----
      if (!addBtn) return;
      ev.preventDefault();
      if (isBusy(addBtn)) return;

      const idseance = toInt(addBtn.dataset.idseance);
      const clicked = toInt(addBtn.dataset.idpartie);

      const rows = getRowsOrdered();
      const clickedRow = getRowByPartieId(rows, clicked);

      // sécurité
      if (!clickedRow) {
        setBusy(addBtn, true);
        try {
          await attachOne(idseance, clicked);
          setAnchor(clicked);
          window.location.reload();
        } catch (e) {
          showError(e);
          setBusy(addBtn, false);
        }
        return;
      }

      // (Règle) niv==1 : jamais sélectionnable (même en clic simple)
      if (isNiv1(clickedRow)) {
        return;
      }

      // (Règle) devoir==1 : toujours une par une (pas de "range")
      if (isDevoir(clickedRow)) {
        setBusy(addBtn, true);
        try {
          await attachOne(idseance, clicked);
          setAnchor(clicked);
          window.location.reload();
        } catch (e) {
          showError(e);
          setBusy(addBtn, false);
        }
        return;
      }

      // Lire ancre + validation (si elle n'est plus linked / ou interdite)
      let prev = getAnchor();
      if (prev) {
        const prevRow = getRowByPartieId(rows, prev);

        // (1) Fix: si l’ancre n’est plus sélectionnée, on l’efface
        if (!prevRow || !isLinked(prevRow)) {
          clearAnchor();
          prev = 0;
        } else if (isDevoir(prevRow) || isNiv1(prevRow)) {
          // ancre interdite => reset
          clearAnchor();
          prev = 0;
        }
      }

      // Pas d’ancre => ajout simple
      if (!prev || prev === clicked) {
        setBusy(addBtn, true);
        try {
          await attachOne(idseance, clicked);
          setAnchor(clicked);
          window.location.reload();
        } catch (e) {
          showError(e);
          setBusy(addBtn, false);
        }
        return;
      }

      const ia = findIndexByPartieId(rows, prev);
      const ib = findIndexByPartieId(rows, clicked);

      // Fallback si indices introuvables
      if (ia < 0 || ib < 0) {
        setBusy(addBtn, true);
        try {
          await attachOne(idseance, clicked);
          setAnchor(clicked);
          window.location.reload();
        } catch (e) {
          showError(e);
          setBusy(addBtn, false);
        }
        return;
      }

      const lo = Math.min(ia, ib);
      const hi = Math.max(ia, ib);
      const hasGap = (hi - lo) > 1;

      let doRange = false;
      if (hasGap) {
        doRange = confirm("Voulez-vous sélectionner aussi toutes les parties entre les deux ?");
      }

      try {
        lockAllPartieButtons(true);

        if (doRange) {
          for (let i = lo; i <= hi; i++) {
            const r = rows[i];

            // (Règles) devoir==1 & niv==1 : jamais en range
            if (!isSelectableForRange(r)) continue;

            // déjà liée => skip
            if (isLinked(r)) continue;

            const pid = toInt(r.dataset.idpartie);
            if (pid > 0) await attachOne(idseance, pid);
          }
        } else {
          await attachOne(idseance, clicked);
        }

        setAnchor(clicked);
        window.location.reload();

      } catch (e) {
        showError(e);
        lockAllPartieButtons(false);
      }
    });

  })();
</script>
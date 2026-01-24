<h1 class="h4 mb-3">
  Séance — <?= htmlspecialchars($seance['classe'], ENT_QUOTES, 'UTF-8') ?>
  <span class="text-muted">
    (<?= htmlspecialchars($seance['date'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($seance['heured'], ENT_QUOTES, 'UTF-8') ?>)
  </span>
</h1>

<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-3">
      <div>
        <div class="fw-semibold">Observation</div>
        <div id="obsText" class="<?= empty($seance['observation']) ? 'text-muted' : '' ?>">
          <?= empty($seance['observation']) ? '—' : htmlspecialchars($seance['observation'], ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-secondary" id="btnEditObs">Modifier</button>
    </div>

    <div class="mt-3 d-none" id="obsEdit">
      <textarea class="form-control" id="obsInput" rows="3"><?= htmlspecialchars((string)($seance['observation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      <div class="mt-2 d-flex gap-2">
        <button class="btn btn-sm btn-primary" id="btnSaveObs">Enregistrer</button>
        <button class="btn btn-sm btn-outline-secondary" id="btnCancelObs">Annuler</button>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header fw-semibold">Absences</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead>
            <tr>
              <th style="width:70px;">N°</th>
              <th>Nom (FR)</th>
              <th>الاسم (AR)</th>
              <th style="width:90px;">Points</th>
              <th style="width:110px;">Absent</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($eleves as $e): ?>
            <?php
              $prevAbsent = ((int)($e['prev_absent'] ?? 0) === 1);
              $modalUrl = "/modals/eleves/show?ideleve=".(int)$e['id']
                        . "&idclasse=".(int)$seance['idclasse']
                        . "&idannee=".(int)$seance['idannee'];
            ?>
            <tr class="<?= $prevAbsent ? 'table-danger' : '' ?>">
              <td><?= (int)$e['numero'] ?></td>

              <td>
                <a href="#"
                  class="text-decoration-none fw-semibold js-modal"
                  data-modal="<?= htmlspecialchars($modalUrl, ENT_QUOTES, 'UTF-8') ?>"
                  data-modal-size="modal-xl">
                  <?= htmlspecialchars(($e['nom'] ?? '').' '.($e['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
              </td>

              <td dir="rtl">
                <a href="#"
                  class="text-decoration-none js-modal"
                  data-modal="<?= htmlspecialchars($modalUrl, ENT_QUOTES, 'UTF-8') ?>"
                  data-modal-size="modal-xl">
                  <?= htmlspecialchars(($e['nomar'] ?? '').' '.($e['prenomar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
              </td>

              <td class="text-nowrap">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        onclick="event.stopPropagation(); AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, -1)">
                  <i class="bi bi-dash"></i>
                </button>

                <span id="pts-<?= (int)$e['id'] ?>" class="mx-2 fw-semibold">
                  <?= (int)$e['points'] ?>
                </span>

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        onclick="event.stopPropagation(); AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, +1)">
                  <i class="bi bi-plus"></i>
                </button>
              </td>
              <td>
                <input type="checkbox"
                      class="form-check-input js-abs"
                      data-idseance="<?= (int)$seance['id'] ?>"
                      data-ideleve="<?= (int)$e['id'] ?>"
                      <?= ((int)$e['absent'] === 1) ? 'checked' : '' ?>
                      onclick="event.stopPropagation()">
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

  <div class="col-lg-6">
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
                $done = !empty($p['last_date']);
                $linked = ((int)$p['linked_to_current'] === 1);
                $devoir = (int)($p['devoir'] ?? 0);
              ?>
              <tr class="<?= $linked ? 'table-success' : ($devoir === 1 ? 'table-warning' : '') ?>">
                <td>
                  <div class="small text-muted"><?= htmlspecialchars($p['abrev'] ?: $p['module'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?= htmlspecialchars(($p['num'] ? $p['num'].' - ' : '').$p['partie'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td><?= $done ? htmlspecialchars($p['last_date'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end">
                    <?php
                        $niv = (int)($p['niv'] ?? 0);
                        $devoir = (int)($p['devoir'] ?? 0);
                    ?>
                    <?php if ($linked): ?>
                        <button class="btn btn-sm btn-outline-danger js-del-partie"
                            title="Retirer cette partie de la séance"
                            data-idseance="<?= (int)$seance['id'] ?>"
                            data-idpartie="<?= (int)$p['id'] ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php elseif ($niv < 2): ?>
                        <span class="text-muted">—</span>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-primary js-add-partie"
                                data-idseance="<?= (int)$seance['id'] ?>"
                                data-idpartie="<?= (int)$p['id'] ?>">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  // Absences -> /api/seances/absence (JSON)
  document.querySelectorAll('.js-abs').forEach(cb => {
    cb.addEventListener('change', async () => {
      const payload = {
        idseance: parseInt(cb.dataset.idseance, 10),
        ideleve: parseInt(cb.dataset.ideleve, 10),
        absent: cb.checked ? 1 : 0
      };
      try {
        await window.AppClasse.markAbsence(payload);
      } catch(e) {}
    });
  });

  // Parties -> /api/seances/partie (JSON)
  document.querySelectorAll('.js-add-partie').forEach(btn => {
    btn.addEventListener('click', async () => {
      const payload = {
        idseance: parseInt(btn.dataset.idseance, 10),
        idpartie: parseInt(btn.dataset.idpartie, 10)
      };
      try {
        await window.AppClasse.attachPartie(payload);
        window.location.reload();
      } catch(e) {}
    });
  });

  // Observation UI (on ajoutera l’endpoint)
  const btnEdit = document.getElementById('btnEditObs');
  const btnSave = document.getElementById('btnSaveObs');
  const btnCancel = document.getElementById('btnCancelObs');
  const obsText = document.getElementById('obsText');
  const obsEdit = document.getElementById('obsEdit');
  const obsInput = document.getElementById('obsInput');

  btnEdit.addEventListener('click', () => obsEdit.classList.remove('d-none'));
  btnCancel.addEventListener('click', () => obsEdit.classList.add('d-none'));

  btnSave.addEventListener('click', async () => {
    // TODO: créer /api/seances/observation
    btnSave.addEventListener('click', async () => {
        try {
            await window.AppClasse.updateObservation({
            idseance: <?= (int)$seance['id'] ?>,
            observation: obsInput.value || ''
            });

            // Mise à jour UI sans recharger
            const val = (obsInput.value || '').trim();
            obsText.textContent = val !== '' ? val : '—';
            obsText.classList.toggle('text-muted', val === '');

            obsEdit.classList.add('d-none');
        } catch (e) {
            alert('Erreur: ' + e.message);
        }
        });
  });
})();

document.querySelectorAll('.js-del-partie').forEach(btn => {
  btn.addEventListener('click', async () => {
    const payload = {
      idseance: parseInt(btn.dataset.idseance, 10),
      idpartie: parseInt(btn.dataset.idpartie, 10)
    };

    try {
      await window.AppClasse.detachPartie(payload);
      window.location.reload();
    } catch (e) {
      alert('Erreur: ' + e.message);
    }
  });
});
</script>

<h1 class="h4 mb-3">
  Séance — <?= $this->e($seance['classe']) ?>
  <span class="text-muted">
    (<?= $this->e($seance['date']) ?>, <?= $this->e($seance['heured']) ?>)
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
              <th></th>
              <th style="width:90px;">Points</th>
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
                    <?= $this->e($e['nom'].' '.$e['prenom']) ?>
                  </a>
                </td>

                <!-- Nom AR cliquable => modal -->
                <td dir="rtl">
                  <a href="#"
                    class="text-decoration-none fw-semibold js-modal"
                    data-modal="/modals/eleves/show?ideleve=<?= (int)$e['id'] ?>&idclasse=<?= (int)$seance['idclasse'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal-size="modal-xl"
                    onclick="event.preventDefault();">
                    <?= $this->e(($e['nomar'] ?? '').' '.($e['prenomar'] ?? '')) ?>
                  </a>
                </td>
                <td>
                  <a class="btn btn-sm btn-outline-secondary"
                    href="/modals/eleves/tags?ideleve=<?= (int)$e['id'] ?>"
                    data-modal='/modals/eleves/tags?ideleve=<?= (int)$e['id'] ?>'
                    data-size="lg">
                    Tags
                  </a>
                  <a class="btn btn-sm btn-outline-secondary"
                    href="/modals/eleves/notebook?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal="/modals/eleves/notebook?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-size="lg">
                    Cahier
                  </a>
                </td>

                <!-- Points -->
                <td class="text-nowrap">
                  <button type="button" class="btn btn-sm btn-outline-secondary"
                          onclick="AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, -1)">
                    <i class="bi bi-dash"></i>
                  </button>

                  <span id="pts-<?= (int)$e['id'] ?>" class="mx-2 fw-semibold">
                    <?= (int)$e['points'] ?>
                  </span>

                  <button type="button" class="btn btn-sm btn-outline-secondary"
                          onclick="AppClassePoints.bump(<?= (int)$seance['id'] ?>, <?= (int)$e['id'] ?>, +1)">
                    <i class="bi bi-plus"></i>
                  </button>
                </td>

                <!-- Absence checkbox -->
                <td class="text-nowrap">
                  <input type="checkbox"
                        class="form-check-input js-abs"
                        data-idseance="<?= (int)$seance['id'] ?>"
                        data-ideleve="<?= (int)$e['id'] ?>"
                        <?= ((int)$e['absent'] === 1) ? 'checked' : '' ?>>

                  <!-- Absences année cliquable => modal (NE PAS stopPropagation ici) -->
                  <a href="#"
                    class="ms-2 js-modal small text-decoration-none"
                    data-modal="/modals/absences/list?ideleve=<?= (int)$e['id'] ?>&idannee=<?= (int)$seance['idannee'] ?>"
                    data-modal-size="modal-lg"
                    onclick="event.preventDefault();">
                    Abs: <?= (int)($e['abs_year'] ?? 0) ?>
                  </a>
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
                $niv = (int)($p['niv'] ?? 0);
                $done = !empty($p['last_date']);
                $linked = ((int)$p['linked_to_current'] === 1);
                $devoir = (int)($p['devoir'] ?? 0);
              ?>
              <tr class="<?= $linked ? 'table-success' : ($devoir === 1 ? 'table-danger' : '') ?>">
                <?php if ($niv>1): ?>
                  <td><?= $this->e(($p['num'] ? $p['num'].' - ' : '').$p['partie']) ?></td>
                  <td><?= $done ? $this->e($p['last_date']) : '<span class="text-muted">—</span>' ?></td>
                  <td class="text-end">
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
                <?php else: ?>
                  <td colspan="3">
                    <?php if($devoir === 0): ?>
                      <div class="text-muted"><?= $this->e($p['abrev'].' - '.$p['module']) ?></div>
                    <?php endif; ?>
                    <?= $this->e($p['partie'])?>
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

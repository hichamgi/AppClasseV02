<div class="modal-header">
  <h5 class="modal-title">Élève — fiche complète</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>

<div class="modal-body">
  <div class="row g-3">

    <div class="col-md-6">
      <div class="fw-semibold">Identité</div>
      <div class="small text-muted">Le prompt reste anonyme (sans nom ni NumeroSGS).</div>

      <div><?= htmlspecialchars(trim(($eleve['nom'] ?? '') . ' ' . ($eleve['prenom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
      <div dir="rtl"><?= htmlspecialchars(trim(($eleve['nomar'] ?? '') . ' ' . ($eleve['prenomar'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="col-md-6">
      <div class="fw-semibold">Présence</div>
      <div>Présent: <?= (int)($absenceStats['total_present'] ?? 0) ?></div>
      <div>Absent: <?= (int)($absenceStats['total_absent'] ?? 0) ?></div>
      <div>Total séances: <?= (int)($absenceStats['total_seances'] ?? 0) ?></div>
    </div>

    <div class="col-12">
      <div class="fw-semibold mb-1">Historique classes / années</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>Année</th>
              <th>Classe</th>
              <th>N°</th>
              <th>Départ</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="4" class="text-muted">—</td></tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td><?= htmlspecialchars((string)($h['annee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string)($h['classe'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (int)($h['numero'] ?? 0) ?></td>
                  <td><?= ((int)($h['depart'] ?? 0) === 1) ? 'Oui' : 'Non' ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-12">
      <div class="fw-semibold mb-1">Tags</div>
      <?php if (empty($tags)): ?>
        <div class="text-muted">—</div>
      <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($tags as $t): ?>
            <?php
              $color = (string)($t['color'] ?? '#6c757d');
              $tag   = (string)($t['tag'] ?? '');
            ?>
            <span class="badge"
                  style="background:<?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>;">
              <?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>
            </span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-12">
      <div class="fw-semibold mb-2">Dossiers par année</div>

      <?php if (empty($byYear)): ?>
        <div class="text-muted">Aucun dossier trouvé.</div>
      <?php else: ?>
        <div class="accordion" id="accYears">
          <?php $i = 0; foreach ($byYear as $y => $pack): $i++; ?>
            <?php
              $isOpen = ($i === 1);
              $d     = $pack['dossier'] ?? null;
              $notes = $pack['notes'] ?? [];
              $nb    = $pack['notebook'] ?? [];
              $yearLabel = (string)($pack['annee'] ?? ('Année ' . $y));
            ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="h<?= (int)$y ?>">
                <button class="accordion-button <?= $isOpen ? '' : 'collapsed' ?>"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#c<?= (int)$y ?>"
                        aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                        aria-controls="c<?= (int)$y ?>">
                  <?= htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8') ?>
                </button>
              </h2>

              <div id="c<?= (int)$y ?>"
                   class="accordion-collapse collapse <?= $isOpen ? 'show' : '' ?>"
                   aria-labelledby="h<?= (int)$y ?>"
                   data-bs-parent="#accYears">
                <div class="accordion-body">

                  <?php if ($d): ?>
                    <div class="row g-2 mb-3">
                      <div class="col-md-3">
                        <div class="fw-semibold">Points</div>
                        <?= (int)($d['points'] ?? 0) ?>
                      </div>
                      <div class="col-md-3">
                        <div class="fw-semibold">Participation</div>
                        <?= (int)($d['participation'] ?? 0) ?>
                      </div>
                      <div class="col-md-6">
                        <div class="fw-semibold">Observations</div>
                        <?php if (!empty($d['obs1'])): ?>
                          <div class="small"><?= htmlspecialchars((string)$d['obs1'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php else: ?>
                          <div class="small text-muted">—</div>
                        <?php endif; ?>

                        <?php if (!empty($d['obs2'])): ?>
                          <div class="small"><?= htmlspecialchars((string)$d['obs2'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php else: ?>
                          <div class="small text-muted">—</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="fw-semibold mb-1">Notebook scores</div>
                  <?php if (empty($nb)): ?>
                    <div class="text-muted mb-3">—</div>
                  <?php else: ?>
                    <div class="table-responsive mb-3">
                      <table class="table table-sm align-middle mb-0">
                        <thead>
                          <tr><th>Module</th><th>Score</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach ($nb as $r): ?>
                            <?php $mod = (string)(($r['abrev'] ?? '') ?: ($r['module'] ?? '')); ?>
                            <tr>
                              <td><?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?></td>
                              <td><?= htmlspecialchars((string)($r['score'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>

                  <div class="fw-semibold mb-1">Notes</div>
                  <?php if (empty($notes)): ?>
                    <div class="text-muted">—</div>
                  <?php else: ?>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                        <thead>
                          <tr>
                            <th>Module</th>
                            <th>Évaluation</th>
                            <th>Note</th>
                            <th>Absent</th>
                            <th>Obs</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($notes as $n): ?>
                            <?php
                              $mod = (string)(($n['abrev'] ?? '') .' : '. ($n['module'] ?? ''));
                              $eval = (string)($n['libellefr'] ?? '');
                              $note = $n['note'] ?? null;
                              $absVal = $n['absent']; // null | 0 | 1
                              $absText = ($absVal === null) ? '—' : (((int)$absVal === 1) ? 'Oui' : 'Non');
                            ?>
                            <tr>
                              <td><?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?></td>
                              <td><?= htmlspecialchars($eval, ENT_QUOTES, 'UTF-8') ?></td>
                              <td>
                                <?php if ($note !== null): ?>
                                  <?= htmlspecialchars((string)$note, ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                  <span class="text-muted">—</span>
                                <?php endif; ?>
                              </td>
                              <td><?= htmlspecialchars($absText, ENT_QUOTES, 'UTF-8') ?></td>
                              <td>
                                <?php if (!empty($n['observation'])): ?>
                                  <?= htmlspecialchars((string)$n['observation'], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                  <span class="text-muted">—</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-12">
      <hr class="my-3">
      <div class="fw-semibold mb-1">Prompt (anonyme) – Remarques dossier / parents</div>
      <textarea class="form-control" rows="9" readonly><?= htmlspecialchars((string)($prompt ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      <div class="small text-muted mt-1">Le prompt ne contient ni nom, ni NumeroSGS, ni identifiant.</div>
    </div>

  </div>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
</div>